<?php

namespace App\Observers;

use App\Models\Announcement;
use App\Models\Room;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RoomObserver
{
    public function updated(Room $room): void
    {
        // Only announce a schedule change if scheduled_at already existed (not a first-time set)
        if ($room->wasChanged('scheduled_at') && $room->getOriginal('scheduled_at') && $room->scheduled_at) {
            Announcement::create([
                'title'            => 'Schedule change: ' . $room->title,
                'body'             => 'Rescheduled to ' . $room->scheduled_at->format('M j, Y \a\t g:i A'),
                'type'             => 'schedule',
                'icon'             => Announcement::iconForType('schedule'),
                'visibility_roles' => null,
            ]);
        }
    }

    public function updating(Room $room): void
    {
        if ($room->isDirty('thumbnail') && $room->getOriginal('thumbnail')) {
            Storage::disk('r2')->delete($room->getOriginal('thumbnail'));
        }
    }

    public function deleted(Room $room): void
    {
        if ($room->thumbnail) {
            Storage::disk('r2')->delete($room->thumbnail);
        }

        if (!$room->daily_room_name) {
            return;
        }

        try {
            $response = Http::withToken(config('services.daily.api_key'))
                ->acceptJson()
                ->delete("https://api.daily.co/v1/rooms/{$room->daily_room_name}");

            if ($response->successful()) {
                Log::info('Daily.co room deleted', ['room' => $room->daily_room_name]);
            } else {
                Log::error('Daily.co room deletion failed', [
                    'room'   => $room->daily_room_name,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Daily.co room deletion exception', [
                'room'    => $room->daily_room_name,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
