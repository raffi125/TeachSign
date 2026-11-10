<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'graded_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected function completedAt(): Attribute
    {
        return Attribute::get(fn () => $this->graded_at ?? $this->created_at);
    }

    public static function fromRow(array $row, Quiz $quiz, int $index, array $users = []): self
    {
        $attempt = new self;
        $attempt->forceFill($row);
        $attempt->id = $quiz->id.'-'.$index;
        $attempt->attempt_index = $index;
        $attempt->setRelation('quiz', $quiz);
        $attempt->setRelation('lesson', $quiz->lesson);

        if (isset($users[$row['user_id'] ?? 0])) {
            $attempt->setRelation('user', $users[$row['user_id']]);
        }

        if (isset($users[$row['graded_by'] ?? 0])) {
            $attempt->setRelation('grader', $users[$row['graded_by']]);
        }

        return $attempt;
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
