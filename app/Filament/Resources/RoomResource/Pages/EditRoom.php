<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use App\Services\DailyService;
use App\Services\GroupService;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EditRoom extends EditRecord
{
    protected static string $resource = RoomResource::class;

    /** Holds group_ids stripped before Eloquent update */
    protected array $pendingGroupIds = [];

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /**
     * Populate virtual split-date fields and group_ids when form loads.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $room = $this->record;

        // Stored as UTC; show admin Phoenix wall-clock
        if ($room->scheduled_at) {
            $local = $room->scheduled_at->copy()->setTimezone('America/Phoenix');
            $data['scheduled_date'] = $local->format('Y-m-d');
            $data['scheduled_time'] = $local->format('H:i');
        }

        if ($room->scheduled_end_at) {
            $local = $room->scheduled_end_at->copy()->setTimezone('America/Phoenix');
            $data['end_date'] = $local->format('Y-m-d');
            $data['end_time'] = $local->format('H:i');
        }

        $data['group_ids'] = DB::table('content_groups')
            ->where('content_type', 'room')
            ->where('content_id', $room->id)
            ->pluck('group_id')
            ->toArray();

        return $data;
    }

    /**
     * Strip all virtual fields before Eloquent save.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingGroupIds = $data['group_ids'] ?? [];
        unset($data['group_ids']);

        // Admin enters times in Phoenix; store as UTC
        foreach (['scheduled_at', 'scheduled_end_at'] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = Carbon::parse($data[$field], 'America/Phoenix')->utc()->format('Y-m-d H:i:s');
            }
        }

        unset($data['scheduled_date'], $data['scheduled_time'], $data['end_date'], $data['end_time']);
        return $data;
    }

    /**
     * Sync content_groups after the record is saved.
     */
    protected function afterSave(): void
    {
        app(GroupService::class)->syncContentGroups(
            'room',
            $this->record->id,
            $this->pendingGroupIds,
        );

        // Push a recording-toggle change to Daily.co. Daily's valid enable_recording
        // values are: cloud, cloud-audio-only, local, raw-tracks, <not set>.
        // There is no verified value to DISABLE recording on an existing room, so we
        // only sync the enable (true -> 'cloud') case and leave a TODO for disable.
        if ($this->record->wasChanged('enable_recording') && $this->record->daily_room_name) {
            if ($this->record->enable_recording) {
                try {
                    app(DailyService::class)->updateRoom(
                        $this->record->daily_room_name,
                        ['enable_recording' => 'cloud'],
                    );
                } catch (\Throwable $e) {
                    Log::warning('EditRoom: Daily.co recording enable sync failed', [
                        'room_id' => $this->record->id,
                        'message' => $e->getMessage(),
                    ]);
                    Notification::make()
                        ->title('Recording enabled locally but not synced to Daily.co')
                        ->body('Check logs. The room may need recreating to apply the change.')
                        ->warning()
                        ->send();
                }
            } else {
                // TODO: Daily.co exposes no verified value to disable recording on an
                // existing room (no 'off'/false; <not set> = property absent on create).
                // Disabling likely requires recreating the room. Not synced here.
                Log::warning('EditRoom: recording disabled in DB but NOT synced to Daily.co — no verified disable value', [
                    'room_id'         => $this->record->id,
                    'daily_room_name' => $this->record->daily_room_name,
                ]);
                Notification::make()
                    ->title('Recording disabled locally — not synced to Daily.co')
                    ->body('Daily.co has no API value to disable recording on an existing room. Recreate the room to fully disable cloud recording.')
                    ->warning()
                    ->send();
            }
        }
    }
}
