<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Events\NewMessageReceived;
use App\Models\Conversation;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Guards the streamed-upload change in MessageController::store (putFileAs
 * instead of put(file_get_contents)). The streamed write must produce an
 * identical stored result: file at the message-attachments/{Y}/{m}/<uuid>.<ext>
 * key on the r2-private disk, with the same attachment DB record (disk, path,
 * filename, mime_type, size).
 */
class MessageAttachmentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_attachment_is_streamed_to_storage_with_intact_record(): void
    {
        Storage::fake('r2-private');
        Event::fake([MessageSent::class, NewMessageReceived::class]);

        $sender    = User::factory()->create(['role' => 'member']);
        $recipient = User::factory()->create(['role' => 'member']);
        $conversation = Conversation::create();
        $conversation->participants()->attach([$sender->id, $recipient->id]);

        $file = UploadedFile::fake()->image('photo.png', 32, 32); // real PNG -> image/png (allowed)

        $response = $this->actingAs($sender)->post(
            route('messages.send', $conversation),
            ['files' => [$file]]
        );
        $response->assertRedirect();

        // Exactly one attachment row, with the expected persisted fields.
        $attachment = MessageAttachment::sole();
        $this->assertSame('r2-private', $attachment->disk);
        $this->assertSame('photo.png', $attachment->filename);
        $this->assertSame('image/png', $attachment->mime_type);
        $this->assertGreaterThan(0, $attachment->size);

        // Path keeps the server-controlled key shape: message-attachments/Y/m/<uuid>.png
        $this->assertMatchesRegularExpression(
            '#^message-attachments/\d{4}/\d{2}/[0-9a-f-]{36}\.png$#',
            $attachment->path
        );

        // File actually landed on the disk at that key, and is the right size.
        Storage::disk('r2-private')->assertExists($attachment->path);
        $this->assertSame(
            $file->getSize(),
            Storage::disk('r2-private')->size($attachment->path)
        );
    }
}
