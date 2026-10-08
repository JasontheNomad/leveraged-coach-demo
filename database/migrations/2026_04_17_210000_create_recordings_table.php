<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('daily_recording_id')->unique();
            $table->string('title');
            $table->integer('duration_seconds')->nullable();
            $table->string('r2_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('download_url')->nullable();
            $table->enum('status', ['processing', 'ready', 'failed'])->default('processing');
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordings');
    }
};
