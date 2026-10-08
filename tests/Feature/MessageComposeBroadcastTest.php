<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Events\NewMessageReceived;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Guards the compose-into-existing-thread fix in ConversationController::store:
 * sending a DM via the compose modal to a user you already have a thread with
 * must fire MessageSent + NewMessageReceived and bump the sender's read-state,
 * matching MessageController::store. Also smoke-tests the new-thread compose
 * and the normal reply path for no regression.
 */
class MessageComposeBroadcastTest extends TestCase
{
    use RefreshDatabase;

    /** A 2-person DM conversation between the given users. */
    private function dmBetween(User $a, User $b): Conversation
    {
        $conversation = Conversation::create();
        $conversation->participants()->attach([$a->id, $b->id]);

        return $conversation;
    }

    public function test_compose_into_existing_thread_broadcasts_and_bumps_read_state(): void
    {
        Event::fake([MessageSent::class, NewMessageReceived::class]);

        $sender    = User::factory()->create(['role' => 'member']);
        $recipient = User::factory()->create(['role' => 'member']);
        $existing  = $this->dmBetween($sender, $recipient);

        $response = $this->actingAs($sender)->post(route('messages.store'), [
            'participant_ids' => [$recipient->id],
            'body'            => 'hello again',
        ]);

        // Re-used the existing thread (no new conversation), redirected to it.
        $response->assertRedirect(route('messages.show', $existing));
        $this->assertSame(1, Conversation::count());
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $existing->id,
            'sender_id'       => $sender->id,
            'body'            => 'hello again',
        ]);

        // Real-time delivery + unread badge events fired.
        Event::assertDispatched(MessageSent::class);
        Event::assertDispatched(
            NewMessageReceived::class,
            fn (NewMessageReceived $e) => $e->recipient->id === $recipient->id
        );

        // Sender's read-state was bumped.
        $this->assertNotNull(
            $existing->participants()->where('users.id', $sender->id)->first()->pivot->last_read_at
        );
    }

    public function test_compose_new_thread_creates_conversation_and_broadcasts(): void
    {
        Event::fake([MessageSent::class, NewMessageReceived::class]);

        $sender    = User::factory()->create(['role' => 'member']);
        $recipient = User::factory()->create(['role' => 'member']); // no prior thread

        $response = $this->actingAs($sender)->post(route('messages.store'), [
            'participant_ids' => [$recipient->id],
            'body'            => 'first message',
        ]);

        $this->assertSame(1, Conversation::count());
        $conversation = Conversation::first();
        $response->assertRedirect(route('messages.show', $conversation));
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id'       => $sender->id,
            'body'            => 'first message',
        ]);

        // New-thread path must also fire the real-time unread-badge event.
        Event::assertDispatched(MessageSent::class);
        Event::assertDispatched(
            NewMessageReceived::class,
            fn (NewMessageReceived $e) => $e->recipient->id === $recipient->id
        );
    }

    public function test_normal_reply_still_broadcasts(): void
    {
        Event::fake([MessageSent::class, NewMessageReceived::class]);

        $sender    = User::factory()->create(['role' => 'member']);
        $recipient = User::factory()->create(['role' => 'member']);
        $conversation = $this->dmBetween($sender, $recipient);

        $this->actingAs($sender)
            ->post(route('messages.send', $conversation), ['body' => 'a reply'])
            ->assertRedirect();

        Event::assertDispatched(MessageSent::class);
        Event::assertDispatched(
            NewMessageReceived::class,
            fn (NewMessageReceived $e) => $e->recipient->id === $recipient->id
        );
    }
}
