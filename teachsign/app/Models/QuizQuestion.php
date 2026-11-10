<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public static function fromArray(array $question, int $index): self
    {
        $model = new self;
        $model->forceFill($question + ['type' => 'choice', 'options' => null, 'correct_answer' => null, 'media_url' => null]);
        $model->id = $index;

        return $model;
    }
}
