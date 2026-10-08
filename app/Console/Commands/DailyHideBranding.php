<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DailyHideBranding extends Command
{
    protected $signature = 'daily:hide-branding';
    protected $description = 'Set Daily.co domain property hide_daily_branding=true';

    public function handle(): int
    {
        $response = Http::withToken(config('services.daily.api_key'))
            ->post('https://api.daily.co/v1/', [
                'properties' => ['hide_daily_branding' => true],
            ]);

        if ($response->failed()) {
            $this->error('Failed: '.$response->status());
            $this->line($response->body());
            return self::FAILURE;
        }

        $this->info('hide_daily_branding set. Response:');
        $this->line($response->body());
        return self::SUCCESS;
    }
}
