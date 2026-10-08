<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $isMysql = DB::getDriverName() === 'mysql';

        if ($isMysql) {
            // Expand enum to include both old and new values before updating data
            DB::statement("ALTER TABLE courses MODIFY COLUMN price_type ENUM('free','pro','premium','collective','leveraged_coach') NOT NULL DEFAULT 'free'");
            DB::statement("ALTER TABLE rooms MODIFY COLUMN required_plan ENUM('free','pro','premium','collective','leveraged_coach') NOT NULL DEFAULT 'free'");
        }

        DB::table('courses')->where('price_type', 'pro')->update(['price_type' => 'collective']);
        DB::table('courses')->where('price_type', 'premium')->update(['price_type' => 'leveraged_coach']);
        DB::table('rooms')->where('required_plan', 'pro')->update(['required_plan' => 'collective']);
        DB::table('rooms')->where('required_plan', 'premium')->update(['required_plan' => 'leveraged_coach']);

        if ($isMysql) {
            // Shrink enum to only new values
            DB::statement("ALTER TABLE courses MODIFY COLUMN price_type ENUM('free','collective','leveraged_coach') NOT NULL DEFAULT 'free'");
            DB::statement("ALTER TABLE rooms MODIFY COLUMN required_plan ENUM('free','collective','leveraged_coach') NOT NULL DEFAULT 'free'");
        }
    }

    public function down(): void
    {
        $isMysql = DB::getDriverName() === 'mysql';

        if ($isMysql) {
            DB::statement("ALTER TABLE courses MODIFY COLUMN price_type ENUM('free','pro','premium','collective','leveraged_coach') NOT NULL DEFAULT 'free'");
            DB::statement("ALTER TABLE rooms MODIFY COLUMN required_plan ENUM('free','pro','premium','collective','leveraged_coach') NOT NULL DEFAULT 'free'");
        }

        DB::table('courses')->where('price_type', 'collective')->update(['price_type' => 'pro']);
        DB::table('courses')->where('price_type', 'leveraged_coach')->update(['price_type' => 'premium']);
        DB::table('rooms')->where('required_plan', 'collective')->update(['required_plan' => 'pro']);
        DB::table('rooms')->where('required_plan', 'leveraged_coach')->update(['required_plan' => 'premium']);

        if ($isMysql) {
            DB::statement("ALTER TABLE courses MODIFY COLUMN price_type ENUM('free','pro','premium') NOT NULL DEFAULT 'free'");
            DB::statement("ALTER TABLE rooms MODIFY COLUMN required_plan ENUM('free','pro','premium') NOT NULL DEFAULT 'free'");
        }
    }
};
