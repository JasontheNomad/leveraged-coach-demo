<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Defensive backfill: copy name -> full_name for any legacy row
        // where full_name was never populated, so no display name is lost
        // when the name column goes away.
        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('full_name')->orWhere('full_name', '');
            })
            ->update(['full_name' => DB::raw('name')]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Re-added as NULLABLE on purpose. The original column was
            // NOT NULL, but its data is dropped in up() and cannot be
            // restored — a NOT NULL column with no default would fail on
            // existing rows. This rollback is lossy: name comes back empty.
            $table->string('name')->nullable();
        });
    }
};
