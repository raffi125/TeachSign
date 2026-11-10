<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9_.]+$/'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()->mixedCase()],
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
