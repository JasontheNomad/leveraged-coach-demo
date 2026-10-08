<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Demo visitor account — DEMO_USER_EMAIL auto-logs visitors in as this member.
        User::factory()->create([
            'full_name' => 'Demo Member',
            'email' => 'demo@example.com',
        ]);

        $this->call([CourseSeeder::class, DemoActivitySeeder::class]);
    }
}
