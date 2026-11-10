<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9_.]+$/'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()->mixedCase()],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_STUDENT])],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, dan garis bawah.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }
}
