<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->withCount('lessons')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $counts = $this->gradedCounts();

        $users->getCollection()->each(
            fn (User $user) => $user->setAttribute('quiz_attempts_count', $counts[$user->id] ?? 0)
        );

        return view('admin.users.index', compact('users', 'search'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $data['password'],
            'role' => $data['role'],
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function show(User $user): View
    {
        $user->loadCount('lessons');
        $user->quiz_attempts_count = $this->gradedCounts()[$user->id] ?? 0;

        $users = User::get(['id', 'username'])->keyBy('id')->all();
        $progress = collect();

        foreach (Quiz::with('lesson:id,title')->get() as $quiz) {
            foreach ($quiz->attempts as $index => $row) {
                if (($row['status'] ?? null) === 'graded' && ($row['user_id'] ?? null) === $user->id) {
                    $progress->push(QuizAttempt::fromRow($row, $quiz, $index, $users));
                }
            }
        }

        $progress = $progress->sortByDesc('score')->unique('lesson_id');

        return view('admin.users.show', compact('user', 'progress'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($user->is($request->user()) && ($data['role'] ?? $user->role) !== User::ROLE_ADMIN) {
            return back()->withErrors(['role' => 'Anda tidak dapat menurunkan peran akun sendiri.']);
        }

        $attributes = [
            'username' => $data['username'] ?? $user->username,
            'email' => $data['email'] ?? $user->email,
            'role' => $data['role'] ?? $user->role,
        ];

        if ($request->filled('password')) {
            $attributes['password_hash'] = $data['password'];
        }

        $user->update($attributes);

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        if ($user->isAdmin() && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return back()->with('error', 'Minimal satu admin harus tetap ada.');
        }

        if ($user->lessons()->exists()) {
            return back()->with('error', 'Pengguna masih memiliki pelajaran. Hapus atau pindahkan pelajaran tersebut terlebih dahulu.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * @return Collection<int, int>
     */
    private function gradedCounts(): Collection
    {
        // shortcut: counts every attempt in PHP, fine for a classroom-sized dataset
        return Quiz::all(['id', 'attempts'])
            ->flatMap(fn (Quiz $quiz) => $quiz->attempts)
            ->filter(fn (array $row) => ($row['status'] ?? null) === 'graded' && isset($row['user_id']))
            ->countBy('user_id');
    }
}
