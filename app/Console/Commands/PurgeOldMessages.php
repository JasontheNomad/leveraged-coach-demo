<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\MessageAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeOldMessages extends Command
{
    protected $signature   = 'messages:purge';
    protected $description = 'Delete messages older than 90 days and empty conversations';

    public function handle(): int
    {
        $cutoff = now()->subDays(90);

        // Collect attachment paths grouped by disk before deleting records
        $byDisk = MessageAttachment::whereHas('message', fn ($q) => $q->where('created_at', '<', $cutoff))
            ->get(['disk', 'path'])
            ->groupBy('disk');

        // Delete files from each disk
        $deletedFiles = 0;
        foreach ($byDisk as $disk => $attachments) {
            Storage::disk($disk)->delete($attachments->pluck('path')->all());
            $deletedFiles += $attachments->count();
        }

        // Delete messages (cascades to message_attachments via FK)
        $deletedMessages = DB::table('messages')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $deletedConversations = Conversation::whereDoesntHave('messages')->delete();

        $this->info("Purged {$deletedMessages} messages, {$deletedFiles} files, and {$deletedConversations} empty conversations.");

        return self::SUCCESS;
    }
}
