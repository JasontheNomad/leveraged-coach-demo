<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\NewMessageReceived;
use App\Events\ReactionToggled;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\Reaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    const ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
    ];

    public function store(Request $request, Conversation $conversation)
    {
        $user = auth()->user();

        abort_unless($conversation->participants->contains($user->id), 403);

        $request->validate([
            'body'          => ['nullable', 'string', 'max:5000'],
            'files'         => ['nullable', 'array', 'max:5'],
            'files.*'       => [
                'file',
                'max:25600', // 25 MB in KB
                function ($attribute, $value, $fail) {
                    if (!in_array($value->getMimeType(), self::ALLOWED_MIME_TYPES)) {
                        $fail('File type not allowed.');
                    }
                    // Images capped at 10 MB
                    if (str_starts_with($value->getMimeType(), 'image/') && $value->getSize() > 10 * 1024 * 1024) {
                        $fail('Images may not exceed 10 MB.');
                    }
                },
            ],
        ]);

        abort_if(empty($request->body) && empty($request->file('files')), 422, 'Message must have a body or at least one attachment.');

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body'      => $request->body ?: null,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $year     = now()->format('Y');
                $month    = now()->format('m');
                $dir      = "message-attachments/{$year}/{$month}";
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path     = "{$dir}/{$filename}";

                // Stream the upload to storage instead of buffering the whole
                // file (up to 25MB x5) into PHP memory via file_get_contents.
                Storage::disk('r2-private')->putFileAs($dir, $file, $filename, 'private');

                MessageAttachment::create([
                    'message_id' => $message->id,
                    'disk'       => 'r2-private',
                    'path'       => $path,
                    'filename'   => $file->getClientOriginalName(),
                    'mime_type'  => $file->getMimeType(),
                    'size'       => $file->getSize(),
                ]);
            }

            $message->load('attachments');
        }

        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        broadcast(new MessageSent($message))->toOthers();

        $conversation->participants()
            ->where('users.id', '!=', $user->id)
            ->get()
            ->each(fn ($recipient) => broadcast(new NewMessageReceived($recipient, $message)));

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    public function react(Request $request, Message $message)
    {
        $user = auth()->user();

        abort_unless($message->conversation->participants->contains($user->id), 403);

        $validated = $request->validate([
            'emoji' => ['required', 'string', 'in:👍,❤️,😂,🎉'],
        ]);

        $existing = Reaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $validated['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
            $action = 'removed';
        } else {
            Reaction::create([
                'message_id' => $message->id,
                'user_id'    => $user->id,
                'emoji'      => $validated['emoji'],
            ]);
            $action = 'added';
        }

        broadcast(new ReactionToggled($message, $validated['emoji'], $user->id, $action))->toOthers();

        return response()->json(['action' => $action, 'emoji' => $validated['emoji']]);
    }
}
