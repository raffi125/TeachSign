<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('mode', 20)->nullable();
            $table->string('word', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('score')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'mode']);
            $table->unique('word');
        });

        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('game_scores');
        Schema::dropIfExists('word_quest_words');
    }

    public function down(): void
    {
        // shortcut: squash is one-way, restore by re-running the old create migrations if ever needed
    }
};
