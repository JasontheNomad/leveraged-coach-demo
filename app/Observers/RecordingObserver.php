<?php

namespace App\Observers;

use App\Models\Announcement;
use App\Models\Recording;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RecordingObserver
{
    public function updated(Recording $recording): void
    {
        if ($recording->wasChanged('status') && $recording->status === 'ready') {
            $announcement = Announcement::create([
                'title'            => 'New recording added: ' . $recording->title,
                'body'             => null,
                'type'             => 'recording',
                'icon'             => Announcement::iconForType('recording'),
                'visibility_roles' => null,
            ]);

            // Mirror the recording's group gating onto the announcement so a
            // group-restricted recording does not broadcast its title to all
            // users. No groups → leave unattached → visible to all (unchanged).
            $groupIds = DB::table('content_groups')
                ->where('content_type', 'recording')
                ->where('content_id', $recording->id)
                ->pluck('group_id');

            if ($groupIds->isNotEmpty()) {
                $announcement->groups()->attach($groupIds);
            }
        }
    }

    public function deleted(Recording $recording): void
    {
        if (!$recording->r2_url) {
            return;
        }

        $r2Url   = config('filesystems.disks.r2.url');
        $r2Path  = $r2Url
            ? ltrim(str_replace($r2Url, '', $recording->r2_url), '/')
            : null;

        if (!$r2Path) {
            return;
        }

        try {
            if (Storage::disk('r2')->delete($r2Path)) {
                Log::info('R2 recording file deleted', ['path' => $r2Path]);
            } else {
                Log::warning('R2 recording file not found or delete returned false', ['path' => $r2Path]);
            }
        } catch (\Throwable $e) {
            Log::error('R2 recording file deletion failed', [
                'path'    => $r2Path,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
