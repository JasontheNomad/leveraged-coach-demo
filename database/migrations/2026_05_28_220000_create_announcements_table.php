<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->enum('type', ['manual', 'recording', 'course', 'lesson', 'schedule'])->default('manual');
            $table->string('icon')->nullable();
            $table->json('visibility_roles')->nullable(); // null = all roles
            $table->timestamps();
        });

        Schema::create('announcement_group', function (Blueprint $table) {
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->primary(['announcement_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_group');
        Schema::dropIfExists('announcements');
    }
};
