<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use App\Services\DailyService;
use App\Services\GroupService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;

    /** Holds group_ids stripped before Eloquent insert */
    protected array $pendingGroupIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Capture group_ids before stripping
        $this->pendingGroupIds = $data['group_ids'] ?? [];
        unset($data['group_ids']);

        // Admin enters times in Phoenix; store as UTC
        foreach (['scheduled_at', 'scheduled_end_at'] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = Carbon::parse($data[$field], 'America/Phoenix')->utc()->format('Y-m-d H:i:s');
            }
        }

        // Strip virtual date/time split fields
        unset($data['scheduled_date'], $data['scheduled_time'], $data['end_date'], $data['end_time']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $room = $this->record;

        // Sync group assignments
        if (! empty($this->pendingGroupIds)) {
            app(GroupService::class)->syncContentGroups(
                'room',
                $room->id,
                $this->pendingGroupIds,
            );
        }

        // Create Daily.co room
        $daily  = app(DailyService::class);
        $result = $daily->createRoom($room->daily_room_name, (bool) $room->enable_recording);

        if ($result && isset($result['url'])) {
            $room->update([
                'daily_room_url'  => $result['url'],
                'daily_room_name' => $result['name'],
            ]);

            Notification::make()
                ->title('Room created on Daily.co')
                ->success()
                ->send();
        } else {
            Log::warning('Daily.co room not created for room id=' . $room->id);

            Notification::make()
                ->title('Room saved but Daily.co call failed')
                ->body('Check logs. You can retry by editing the room.')
                ->warning()
                ->send();
        }
    }
}
