<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoActivitySeeder extends Seeder
{
    /**
     * Fake members, a group, live sessions, chat and an announcement
     * so the public demo looks like an active community.
     */
    public function run(): void
    {
        $demo = User::where('email', 'demo@example.com')->firstOrFail();

        $names = [
            'Morgan Hayes', 'Priya Nair', 'Daniel Okafor', 'Sofia Marquez',
            'Ethan Brooks', 'Hannah Lee', 'Marcus Bennett', 'Chloe Dubois',
            'Ryan Castillo', 'Aisha Rahman', 'Tom Whitaker', 'Grace Kim',
        ];

        $members = collect($names)->map(fn ($name, $i) => User::factory()->create([
            'full_name'    => $name,
            'email'        => 'member' . ($i + 1) . '@example.com',
            'last_seen_at' => now()->subMinutes(rand(1, 600)),
        ]));

        // Group
        $group = Group::create([
            'name'        => 'The Collective',
            'slug'        => 'the-collective',
            'description' => 'Members of the Leveraged Coach Collective.',
            'is_active'   => true,
        ]);
        $group->members()->attach($members->pluck('id')->push($demo->id), ['assigned_at' => now()]);

        // Live sessions (upcoming, relative to seed time)
        $host = $members[0];
        $sessions = [
            ['Office Hours', 'Bring your questions — hot seats and Q&A.', 2, 16, 'room-thumbnails/01KTPVZ4WABZEWAPXF1A7QSV7R.png'],
            ['Collective Live Session', 'Weekly working session for Collective members.', 2, 23, 'room-thumbnails/01KTPVWXA2GEXCKM7S2PG9M5N7.jpg'],
        ];
        foreach ($sessions as $i => [$title, $description, $days, $hourUtc, $thumbnail]) {
            $start = now()->addDays($days)->setTime($hourUtc, 0);
            Room::create([
                'title'            => $title,
                'description'      => $description,
                'daily_room_name'  => 'demo-room-' . ($i + 1),
                'daily_room_url'   => 'https://example.daily.co/demo-room-' . ($i + 1),
                'scheduled_at'     => $start,
                'scheduled_end_at' => $start->copy()->addHour(),
                'host_id'          => $host->id,
                'recurrence'       => 'weekly',
                'enable_recording' => false,
                'thumbnail'        => $thumbnail,
            ]);
        }

        // Group chat
        $chat = Conversation::create();
        $chat->participants()->attach([$demo->id, $members[0]->id, $members[1]->id, $members[2]->id, $members[3]->id]);

        $lines = [
            [$members[0], 'Welcome to the group! Drop your channel link and one goal for this month.'],
            [$members[1], 'Goal: 2 videos a week for 30 days. Channel is all about meal-prep for busy parents.'],
            [$members[2], 'Just finished the Pillars & Purpose module. Rewrote my channel purpose three times 😅'],
            [$members[3], 'Same here. The "12 Content Variations" lesson unlocked a month of ideas for me.'],
            [$members[0], 'Love it. Bring your drafts to Thursday\'s call and we\'ll workshop them live.'],
            [$members[1], 'See you there 🙌'],
        ];
        $messages = [];
        foreach ($lines as $i => [$sender, $body]) {
            $message = new Message(['conversation_id' => $chat->id, 'sender_id' => $sender->id, 'body' => $body]);
            $message->created_at = now()->subHours(count($lines) - $i);
            $message->updated_at = $message->created_at;
            $message->save();
            $messages[] = $message;
        }
        $messages[1]->reactions()->create(['user_id' => $members[0]->id, 'emoji' => '🎉']);
        $messages[3]->reactions()->create(['user_id' => $members[2]->id, 'emoji' => '👍']);
        $messages[3]->reactions()->create(['user_id' => $members[1]->id, 'emoji' => '👍']);
        $messages[4]->reactions()->create(['user_id' => $members[3]->id, 'emoji' => '❤️']);

        // Announcement
        Announcement::create([
            'title' => 'Welcome to the Leveraged Coach demo',
            'body'  => 'This is a live demo with sample data. Explore the courses, live sessions and member chat.',
            'type'  => 'manual',
            'icon'  => Announcement::iconForType('manual'),
        ]);
    }
}
