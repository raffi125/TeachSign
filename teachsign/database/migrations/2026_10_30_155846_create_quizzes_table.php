<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->string('title');
            $table->json('questions')->nullable();
            $table->json('attempts')->nullable();
            $table->boolean('is_locked')->default(true);
            $table->timestamp('unlock_at')->nullable();
            $table->boolean('is_score_visible')->default(false);
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lesson_id', 'is_locked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
