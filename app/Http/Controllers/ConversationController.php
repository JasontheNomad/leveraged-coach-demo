<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\NewMessageReceived;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $conversations = $user->conversations()
            ->with(['participants', 'latestMessage.sender'])
            ->get()
            ->sort(function ($a, $b) use ($user) {
                $aUnread = $a->unreadCountFor($user) > 0 ? 1 : 0;
                $bUnread = $b->unreadCountFor($user) > 0 ? 1 : 0;
                if ($aUnread !== $bUnread) return $bUnread - $aUnread;
                $aTime = optional($a->latestMessage)->created_at?->timestamp ?? 0;
                $bTime = optional($b->latestMessage)->created_at?->timestamp ?? 0;
                return $bTime - $aTime;
            })
            ->values();

        $to            = request('to');
        $messageTarget = $to ? User::find($to) : null;

        // Targeting a specific user: open their existing 1-on-1 thread if one
        // exists; otherwise fall through to the seeded compose modal.
        if ($messageTarget) {
            $existing = $user->conversations()
                ->whereHas('participants', fn ($q) => $q->where('users.id', $messageTarget->id))
                ->get()
                ->first(fn ($c) => $c->participants->count() === 2);

            if ($existing) {
                return redirect()->route('messages.show', $existing);
            }

            return view('messages.index', compact('conversations', 'user', 'messageTarget'));
        }

        if ($conversations->isNotEmpty()) {
            return redirect()->route('messages.show', $conversations->first());
        }

        return view('messages.index', compact('conversations', 'user', 'messageTarget'));
    }

    public function show(Conversation $conversation)
    {
        $user = auth()->user();

        abort_unless($conversation->participants->contains($user->id), 403);

        $conversation->load(['messages.sender', 'messages.attachments', 'messages.reactions', 'participants']);

        // Mark as read
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        $conversations = $user->conversations()
            ->with(['participants', 'latestMessage.sender'])
            ->get()
            ->sort(function ($a, $b) use ($user) {
                $aUnread = $a->unreadCountFor($user) > 0 ? 1 : 0;
                $bUnread = $b->unreadCountFor($user) > 0 ? 1 : 0;
                if ($aUnread !== $bUnread) return $bUnread - $aUnread;
                $aTime = optional($a->latestMessage)->created_at?->timestamp ?? 0;
                $bTime = optional($b->latestMessage)->created_at?->timestamp ?? 0;
                return $bTime - $aTime;
            })
            ->values();

        $members = User::where('id', '!=', $user->id)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'avatar_url']);

        $groups = $user->role === 'admin' ? Group::orderBy('name')->get(['id', 'name']) : collect();

        return view('messages.show', compact('conversation', 'conversations', 'user', 'members', 'groups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'participant_ids'   => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['exists:users,id'],
            'body'              => ['required', 'string', 'max:5000'],
        ]);

        $user = auth()->user();
        $participantIds = collect($request->participant_ids)
            ->map('intval')
            ->reject(fn ($id) => $id === $user->id)
            ->unique()
            ->values();

        abort_if($participantIds->isEmpty(), 422, 'No valid recipients.');

        // For DMs (1 other person), reuse existing conversation if one exists
        if ($participantIds->count() === 1) {
            $otherId = $participantIds->first();

            $existing = $user->conversations()
                ->whereHas('participants', fn ($q) => $q->where('users.id', $otherId))
                ->get()
                ->first(fn ($c) => $c->participants->count() === 2);

            if ($existing) {
                // Add the message and redirect to existing thread. Mirror
                // MessageController::store so real-time delivery + unread
                // badges fire (bump sender read-state, broadcast events).
                $message = $existing->messages()->create([
                    'sender_id' => $user->id,
                    'body'      => $request->body,
                ]);

                $existing->participants()->updateExistingPivot($user->id, [
                    'last_read_at' => now(),
                ]);

                broadcast(new MessageSent($message))->toOthers();

                $existing->participants()
                    ->where('users.id', '!=', $user->id)
                    ->get()
                    ->each(fn ($recipient) => broadcast(new NewMessageReceived($recipient, $message)));

                return redirect()->route('messages.show', $existing);
            }
        }

        ['conversation' => $conversation, 'message' => $message] = DB::transaction(function () use ($user, $participantIds, $request) {
            $conversation = Conversation::create();

            $allIds = $participantIds->push($user->id)->unique()->values();
            $now    = now();

            foreach ($allIds as $id) {
                $conversation->participants()->attach($id, [
                    'last_read_at' => $id === $user->id ? $now : null,
                ]);
            }

            $message = $conversation->messages()->create([
                'sender_id' => $user->id,
                'body'      => $request->body,
            ]);

            return ['conversation' => $conversation, 'message' => $message];
        });

        // Broadcast after commit so a rolled-back transaction emits nothing.
        broadcast(new MessageSent($message))->toOthers();

        $conversation->participants()
            ->where('users.id', '!=', $user->id)
            ->get()
            ->each(fn ($recipient) => broadcast(new NewMessageReceived($recipient, $message)));

        return redirect()->route('messages.show', $conversation);
    }

    public function markRead(Conversation $conversation)
    {
        $user = auth()->user();
        abort_unless($conversation->participants->contains($user->id), 403);

        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    // AJAX: search members by name
    public function searchMembers(Request $request)
    {
        $q     = $request->input('q', '');
        $users = User::where('id', '!=', auth()->id())
            ->where(fn ($query) => $query
                ->where('full_name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
            )
            ->orderBy('full_name')
            ->limit(20)
            ->get(['id', 'full_name', 'email', 'avatar_url']);

        return response()->json($users->map(fn ($u) => [
            'id'   => $u->id,
            'name' => $u->full_name,
            'avatar_url' => $u->avatar_url,
        ]));
    }

    // AJAX: get all member IDs in a group
    public function groupMembers(Request $request)
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        $group = Group::findOrFail($request->input('group_id'));

        $members = $group->users()
            ->where('users.id', '!=', auth()->id())
            ->get(['users.id', 'users.full_name']);

        return response()->json($members->map(fn ($u) => [
            'id'   => $u->id,
            'name' => $u->full_name,
        ]));
    }
}
