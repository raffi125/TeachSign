<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['username', 'email', 'password_hash', 'role'])]
#[Hidden(['password_hash'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_STUDENT = 'student';

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'created_by');
    }

    public function lockedQuizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'locked_by');
    }
}
