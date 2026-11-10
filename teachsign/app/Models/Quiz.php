<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

#[Fillable([
    'lesson_id',
    'title',
    'questions',
    'attempts',
    'is_locked',
    'unlock_at',
    'is_score_visible',
    'duration_seconds',
    'locked_by',
])]
class Quiz extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'is_score_visible' => 'boolean',
            'unlock_at' => 'datetime',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    protected function questions(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): Collection => collect(json_decode($value ?? '[]', true))
                ->values()
                ->map(fn (array $question, int $index) => QuizQuestion::fromArray($question, $index)),
            set: fn ($questions): string => json_encode(
                collect($questions)
                    ->map(fn ($question) => $question instanceof QuizQuestion
                        ? [
                            'question' => $question->question,
                            'type' => $question->type,
                            'options' => $question->options,
                            'correct_answer' => $question->correct_answer,
                            'media_url' => $question->media_url,
                        ]
                        : (array) $question)
                    ->values()
                    ->all()
            ),
        );
    }

    protected function questionsCount(): Attribute
    {
        return Attribute::get(fn (): int => $this->questions->count());
    }

    protected function attempts(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): Collection => collect(json_decode($value ?? '[]', true)),
            set: fn ($attempts): string => json_encode(
                collect($attempts)->map(fn ($attempt) => (array) $attempt)->values()->all()
            ),
        );
    }

    public function addAttempt(array $attempt): void
    {
        // shortcut: attempts JSON is not row-locked, move back to a table if concurrent attempts matter
        $rows = $this->attempts->all();
        $rows[] = $attempt;
        $this->attempts = $rows;
        $this->save();
    }

    public function updateAttempt(int $index, array $changes): bool
    {
        $rows = $this->attempts->all();

        if (! isset($rows[$index])) {
            return false;
        }

        $rows[$index] = array_merge($rows[$index], $changes);
        $this->attempts = $rows;
        $this->save();

        return true;
    }

    public function isAvailable(): bool
    {
        if (! $this->is_locked) {
            return true;
        }

        return $this->unlock_at !== null && $this->unlock_at->isPast();
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('is_locked', false)
                ->orWhere(fn (Builder $q) => $q->whereNotNull('unlock_at')->where('unlock_at', '<=', now()));
        });
    }
}
