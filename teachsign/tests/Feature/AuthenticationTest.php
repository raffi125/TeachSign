<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register(): void
    {
        $this->get(route('register'))->assertOk();

        $this->post(route('register'), [
            'username' => 'siswa.baru',
            'email' => 'siswa@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $user = User::where('email', 'siswa@example.com')->firstOrFail();
        $this->assertSame('siswa.baru', $user->username);
        $this->assertSame(User::ROLE_STUDENT, $user->role);
    }

    public function test_register_ignores_attempt_to_pick_admin_role(): void
    {
        $this->post(route('register'), [
            'username' => 'penyusup',
            'email' => 'penyusup@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'role' => User::ROLE_ADMIN,
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(
            User::ROLE_STUDENT,
            User::where('email', 'penyusup@example.com')->value('role')
        );
    }

    public function test_register_rejects_duplicate_email_and_weak_password(): void
    {
        User::factory()->create(['email' => 'dipakai@example.com']);

        $this->post(route('register'), [
            'username' => 'baru',
            'email' => 'dipakai@example.com',
            'password' => 'lemah',
            'password_confirmation' => 'lemah',
        ])->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }
}
