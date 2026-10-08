<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite can't ALTER COLUMN — drop and recreate with nullable url/name
        Schema::drop('rooms');

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('daily_room_name')->nullable()->unique();
            $table->string('daily_room_url')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->enum('required_plan', ['free', 'pro', 'premium'])->default('free');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('rooms');
    }
};
