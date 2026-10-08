<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rooms')->where('recurrence', 'weekly_friday')->update(['recurrence' => 'weekly']);
        DB::table('rooms')->where('recurrence', 'biweekly_friday')->update(['recurrence' => 'biweekly']);
    }

    public function down(): void
    {
        DB::table('rooms')->where('recurrence', 'weekly')->update(['recurrence' => 'weekly_friday']);
        DB::table('rooms')->where('recurrence', 'biweekly')->update(['recurrence' => 'biweekly_friday']);
    }
};
