<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->boolean('revoke_on_cancel')->default(false)->after('is_active');
        });

        DB::table('groups')
            ->whereIn('slug', ['leveraged-coach', 'collective'])
            ->update(['revoke_on_cancel' => true]);
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('revoke_on_cancel');
        });
    }
};
