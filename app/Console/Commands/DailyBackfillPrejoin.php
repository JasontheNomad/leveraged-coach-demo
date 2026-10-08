<?php

namespace App\Console\Commands;

use App\Models\Room;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DailyBackfillPrejoin extends Command
{
    protected $signature = 'daily:backfill-prejoin {--dry-run}';
    protected $description = 'Set enable_prejoin_ui=true on all existing Daily rooms';

    public function handle(): int
    {
        $rooms = Room::whereNotNull('daily_room_name')->get();
        $this->info("Found {$rooms->count()} rooms with a Daily room name.");

        $ok = 0; $fail = 0;

        foreach ($rooms as $room) {
            $name = $room->daily_room_name;

            if ($this->option('dry-run')) {
                $this->line("[dry-run] would update: {$name}");
                continue;
            }

            $response = Http::withToken(config('services.daily.api_key'))
                ->acceptJson()
                ->post("https://api.daily.co/v1/rooms/{$name}", [
                    'properties' => ['enable_prejoin_ui' => true],
                ]);

            if ($response->failed()) {
                $this->error("FAIL {$name}: {$response->status()} {$response->body()}");
                $fail++;
                continue;
            }

            $this->info("ok: {$name}");
            $ok++;
        }

        $this->line("Done. updated={$ok} failed={$fail}");
        return self::SUCCESS;
    }
}
