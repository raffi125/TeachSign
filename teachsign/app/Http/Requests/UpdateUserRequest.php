<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'username' => ['sometimes', 'required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9_.]+$/'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()->mixedCase()],
            'role' => ['sometimes', 'required', Rule::in([User::ROLE_ADMIN, User::ROLE_STUDENT])],
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
