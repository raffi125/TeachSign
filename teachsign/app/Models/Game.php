<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Game extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'mode', 'word', 'is_active', 'score'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWords(Builder $query): Builder
    {
        return $query->whereNotNull('word');
    }

    public function scopeScores(Builder $query): Builder
    {
        return $query->whereNotNull('mode')->whereNotNull('score');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
