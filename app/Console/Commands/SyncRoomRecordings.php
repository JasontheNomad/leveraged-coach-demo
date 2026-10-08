<?php

namespace App\Console\Commands;

use App\Models\Recording;
use App\Models\Room;
use App\Services\DailyService;
use App\Services\GroupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SyncRoomRecordings extends Command
{
    protected $signature = 'sync:recordings {--refresh : Update thumbnail_url for existing recordings without re-downloading}';
    protected $description = 'Sync Daily.co cloud recordings to Cloudflare R2';

    public function handle(DailyService $daily, GroupService $groupService): int
    {
        if ($this->option('refresh')) {
            return $this->refreshThumbnails($daily, $groupService);
        }

        $rooms = Room::where('enable_recording', true)
            ->whereNotNull('daily_room_name')
            ->get();

        $this->info("Syncing recordings for {$rooms->count()} room(s)…");

        foreach ($rooms as $room) {
            $this->line("  Room: {$room->title} ({$room->daily_room_name})");

            $recordings = $daily->getRecordings($room->daily_room_name);

            if (empty($recordings)) {
                $this->line('    No recordings found.');
                continue;
            }

            foreach ($recordings as $rec) {
                $dailyId = $rec['id'] ?? null;

                if (!$dailyId) {
                    continue;
                }

                if (Recording::where('daily_recording_id', $dailyId)->exists()) {
                    $this->line("    Skipping {$dailyId} — already synced.");
                    continue;
                }

                $this->line("    Processing {$dailyId}…");

                $downloadUrl = $daily->getRecordingDownloadLink($dailyId);

                if (!$downloadUrl) {
                    Log::warning("SyncRoomRecordings: no download link for {$dailyId}");
                    $this->warn("    No download link for {$dailyId}, skipping.");
                    continue;
                }

                $r2Path  = "recordings/{$room->daily_room_name}/{$dailyId}.mp4";
                $r2Url   = null;
                $status  = 'failed';
                $tmpPath = tempnam(sys_get_temp_dir(), 'rec_');

                try {
                    Http::sink($tmpPath)->timeout(600)->get($downloadUrl);

                    $stream = fopen($tmpPath, 'r');
                    Storage::disk('r2')->put($r2Path, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }

                    $r2Url  = Storage::disk('r2')->url($r2Path);
                    $status = 'ready';
                    $this->info("    Uploaded to R2: {$r2Url}");

                    if ($daily->deleteRecording($dailyId)) {
                        $this->line("    Deleted from Daily.co: {$dailyId}");
                    } else {
                        $this->warn("    Could not delete from Daily.co: {$dailyId}");
                    }
                } catch (\Throwable $e) {
                    Log::error("SyncRoomRecordings upload failed for {$dailyId}", [
                        'message' => $e->getMessage(),
                    ]);
                    $this->error("    Upload failed: {$e->getMessage()}");
                } finally {
                    @unlink($tmpPath);
                }

                $recordedAt = isset($rec['start_ts'])
                    ? \Carbon\Carbon::createFromTimestamp($rec['start_ts'])
                    : null;

                $title = $room->title . ($recordedAt ? ' - ' . $recordedAt->format('M j, Y') : '');

                $recording = Recording::create([
                    'room_id'             => $room->id,
                    'daily_recording_id'  => $dailyId,
                    'title'               => $title,
                    'duration_seconds'    => $rec['duration'] ?? null,
                    'r2_url'              => $r2Url,
                    'thumbnail_url'       => $rec['thumbnail_url'] ?? null,
                    'download_url'        => $downloadUrl,
                    'status'              => $status,
                    'recorded_at'         => $recordedAt,
                ]);

                // Inherit all group assignments from the parent room
                $groupService->inheritGroups('room', $room->id, 'recording', $recording->id);
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function refreshThumbnails(DailyService $daily, GroupService $groupService): int
    {
        $recordings = Recording::whereNull('thumbnail_url')
            ->with('room')
            ->get();

        $this->info("Refreshing thumbnails for {$recordings->count()} recording(s) missing thumbnail_url…");

        // Group by room to avoid redundant API calls
        $byRoom = $recordings->groupBy('room_id');

        foreach ($byRoom as $roomId => $roomRecordings) {
            $room = $roomRecordings->first()->room;

            if (! $room?->daily_room_name) {
                $this->warn("  Skipping room_id={$roomId} — no daily_room_name.");
                continue;
            }

            $this->line("  Room: {$room->title} ({$room->daily_room_name})");

            $apiRecordings = $daily->getRecordings($room->daily_room_name);

            if (empty($apiRecordings)) {
                $this->line('    No recordings returned from Daily.co.');
                continue;
            }

            $apiById = collect($apiRecordings)->keyBy('id');

            foreach ($roomRecordings as $recording) {
                $rec = $apiById->get($recording->daily_recording_id);

                if (! $rec) {
                    $this->warn("    {$recording->daily_recording_id} — not found on Daily.co, skipping.");
                    continue;
                }

                $thumbnailUrl = $rec['thumbnail_url'] ?? null;

                if (! $thumbnailUrl) {
                    $this->line("    {$recording->daily_recording_id} — no thumbnail_url in API response.");
                    continue;
                }

                $recording->update(['thumbnail_url' => $thumbnailUrl]);
                $this->info("    Updated thumbnail for: {$recording->title}");
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
