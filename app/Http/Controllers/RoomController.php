<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomException;
use App\Services\DailyService;
use App\Services\GroupService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(GroupService $groups)
    {
        $from = now();
        $to   = now()->addMonths(3);

        $upcomingQuery = Room::whereNotNull('scheduled_at')->with(['exceptions', 'host']);

        $pastQuery = Room::whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderByDesc('scheduled_at');

        if (auth()->check()) {
            $groups->scopeAccessible($upcomingQuery, auth()->user());
            $groups->scopeAccessible($pastQuery, auth()->user());
        } else {
            $upcomingQuery->whereNull('group_id');
            $pastQuery->whereNull('group_id');
        }

        $upcoming = $upcomingQuery->get()
            ->flatMap(fn ($room) => $room->occurrences($from, $to))
            ->sortBy('start')
            ->values();

        $past = $pastQuery->get();

        return view('rooms.index', compact('upcoming', 'past'));
    }

    public function cancelOccurrence(Request $request, Room $room)
    {
        abort_if(auth()->user()->role !== 'admin', 403);

        $request->validate(['date' => 'required|date_format:Y-m-d']);

        $date = Carbon::parse($request->date);

        if (! $this->isValidOccurrenceDate($room, $date)) {
            return back()->withErrors(['date' => 'That date is not a valid occurrence of this session.']);
        }

        RoomException::updateOrCreate(
            ['room_id' => $room->id, 'date' => $date->toDateString()],
            ['status' => 'cancelled']
        );

        return back()->with('success', 'Session occurrence cancelled.');
    }

    private function isValidOccurrenceDate(Room $room, Carbon $date): bool
    {
        $scheduled = $room->scheduled_at->copy()->startOfDay();
        $target    = $date->copy()->startOfDay();

        if ($target->lt($scheduled)) {
            return false;
        }

        if ($room->recurs_until && $target->gt(Carbon::instance($room->recurs_until)->endOfDay())) {
            return false;
        }

        $recurrence = $room->recurrence ?? 'none';

        switch ($recurrence) {
            case 'none':
                return $scheduled->isSameDay($target);
            case 'daily':
                return true;
            case 'weekdays':
                return $target->isWeekday();
            case 'weekly':
                return $scheduled->dayOfWeek === $target->dayOfWeek;
            case 'biweekly':
                return $scheduled->diffInDays($target) % 14 === 0;
            default:
                return false;
        }
    }

    public function show(string $roomName, DailyService $daily, GroupService $groups)
    {
        $room = Room::where('daily_room_name', $roomName)->firstOrFail();

        abort_unless($groups->canAccessContent(auth()->user(), 'room', $room->id), 403);

        // Public demo has no Daily.co account — show a placeholder instead of joining.
        if (config('app.demo_user_email')) {
            return view('rooms.demo-disabled', compact('room'));
        }

        // Self-heal rooms where Daily.co URL was never stored
        if (! $room->daily_room_url) {
            $data = $daily->getRoom($roomName);

            if ($data && isset($data['url'])) {
                $room->update(['daily_room_url' => $data['url']]);
                $room->refresh();
            }
        }

        $isHost = auth()->id() === $room->host_id;
        $token  = $daily->getMeetingToken($roomName, $isHost);

        return view('rooms.show', compact('room', 'token'));
    }
}
