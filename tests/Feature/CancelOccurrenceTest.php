<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    private function makeRoom(User $host): array
    {
        $scheduled = Carbon::now()->startOfDay();

        $room = Room::create([
            'title'           => 'Weekly Test Session',
            'daily_room_name' => null,
            'daily_room_url'  => null,
            'scheduled_at'    => $scheduled,
            'host_id'         => $host->id,
            'recurrence'      => 'weekly',
        ]);

        $date = $scheduled->copy()->addWeek()->toDateString();

        return [$room, $date];
    }

    public function test_non_admin_cannot_cancel_occurrence(): void
    {
        $member = User::factory()->create(['role' => 'member', 'full_name' => 'Test Member']);
        [$room, $date] = $this->makeRoom($member);

        $response = $this->actingAs($member)
            ->post(route('live-sessions.cancel-occurrence', ['room' => $room->id]), [
                'date' => $date,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('room_exceptions', [
            'room_id' => $room->id,
            'date'    => $date,
        ]);
    }

    public function test_admin_can_cancel_occurrence(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'full_name' => 'Test Admin']);
        [$room, $date] = $this->makeRoom($admin);

        $response = $this->actingAs($admin)
            ->post(route('live-sessions.cancel-occurrence', ['room' => $room->id]), [
                'date' => $date,
            ]);

        $response->assertRedirect();

        $this->assertTrue(
            RoomException::where('room_id', $room->id)
                ->whereDate('date', $date)
                ->where('status', 'cancelled')
                ->exists()
        );
    }
}
