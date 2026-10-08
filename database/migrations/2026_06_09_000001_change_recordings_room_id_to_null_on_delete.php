<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recordings', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
        });
        Schema::table('recordings', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->change();
        });
        Schema::table('recordings', function (Blueprint $table) {
            $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recordings', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
        });
        Schema::table('recordings', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable(false)->change();
        });
        Schema::table('recordings', function (Blueprint $table) {
            $table->foreign('room_id')->references('id')->on('rooms')->cascadeOnDelete();
        });
    }
};
