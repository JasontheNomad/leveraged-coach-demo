<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('group_content');
    }

    public function down(): void
    {
        Schema::create('group_content', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('content_type');
            $table->unsignedBigInteger('content_id');
            $table->timestamps();
            $table->unique(['group_id', 'content_type', 'content_id']);
        });
    }
};
