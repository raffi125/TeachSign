<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $data['password'],
            'role' => User::ROLE_STUDENT,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
