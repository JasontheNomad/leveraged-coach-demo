<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
        $this->message->loadMissing('sender', 'attachments');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastWith(): array
    {
        $sender = $this->message->sender;

        return [
            'id'              => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id'       => $this->message->sender_id,
            'sender'          => $sender->full_name,
            'avatar'          => $sender->avatar_url,
            'body'            => $this->message->body,
            'created_at'      => $this->message->created_at->format('g:i A'),
            'attachments'     => $this->message->attachments->map(fn ($a) => [
                'url'       => $a->url(),
                'filename'  => $a->filename,
                'mime_type' => $a->mime_type,
                'size'      => $a->size,
                'is_image'  => $a->isImage(),
            ])->values()->all(),
        ];
    }
}
