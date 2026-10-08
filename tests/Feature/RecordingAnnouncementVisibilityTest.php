<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Group;
use App\Models\Recording;
use App\Models\Room;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the info-leak fix in App\Observers\RecordingObserver: the announcement
 * created when a recording flips to "ready" must inherit the recording's
 * content_groups gating, so a group-restricted recording does not broadcast its
 * title to users outside that group. Ungated recordings stay visible to all.
 */
class RecordingAnnouncementVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private int $dailyIdSeq = 0;

    private function makeRoom(User $host): Room
    {
        return Room::create([
            'title'   => 'Test Room',
            'host_id' => $host->id,
        ]);
    }

    /** Create a recording in the non-ready "processing" state. */
    private function makeProcessingRecording(Room $room): Recording
    {
        return Recording::create([
            'room_id'            => $room->id,
            'daily_recording_id' => 'dr-' . (++$this->dailyIdSeq),
            'title'              => 'Weekly Session Replay',
            'status'             => 'processing',
        ]);
    }

    /** Flip status processing -> ready to fire the observer's updated() event. */
    private function flipReady(Recording $recording): void
    {
        $recording->update(['status' => 'ready']);
    }

    private function latestRecordingAnnouncement(): Announcement
    {
        return Announcement::where('type', 'recording')->latest('id')->firstOrFail();
    }

    public function test_gated_recording_announcement_is_restricted_to_its_group(): void
    {
        $host    = User::factory()->create(['role' => 'admin']);
        $group   = Group::create(['name' => 'VIP', 'slug' => 'vip']);
        $member  = User::factory()->create(['role' => 'member']);
        $outsider = User::factory()->create(['role' => 'member']);
        $member->groups()->attach($group->id);

        $recording = $this->makeProcessingRecording($this->makeRoom($host));
        app(GroupService::class)->syncContentGroups('recording', $recording->id, [$group->id]);

        $this->flipReady($recording);

        $announcement = $this->latestRecordingAnnouncement();

        // Announcement inherited the recording's group.
        $this->assertEqualsCanonicalizing(
            [$group->id],
            $announcement->groups()->pluck('groups.id')->all()
        );

        // Visible to the in-group member, hidden from the outsider.
        $this->assertTrue(
            Announcement::visibleTo($member)->where('announcements.id', $announcement->id)->exists(),
            'In-group member should see the gated recording announcement.'
        );
        $this->assertFalse(
            Announcement::visibleTo($outsider)->where('announcements.id', $announcement->id)->exists(),
            'Outsider must NOT see the gated recording announcement (leak).'
        );
    }

    public function test_ungated_recording_announcement_is_visible_to_all(): void
    {
        $host   = User::factory()->create(['role' => 'admin']);
        $anyone = User::factory()->create(['role' => 'member']); // no groups

        $recording = $this->makeProcessingRecording($this->makeRoom($host));
        // No syncContentGroups: recording is ungated.

        $this->flipReady($recording);

        $announcement = $this->latestRecordingAnnouncement();

        $this->assertSame([], $announcement->groups()->pluck('groups.id')->all());

        $this->assertTrue(
            Announcement::visibleTo($anyone)->where('announcements.id', $announcement->id)->exists(),
            'Ungated recording announcement should be visible to everyone.'
        );
    }
}
