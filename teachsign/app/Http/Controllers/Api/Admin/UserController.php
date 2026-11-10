<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
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
            ->paginate(10);

        $counts = $this->gradedCounts();

        $users->through(fn (User $user) => [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'lessons_count' => $user->lessons_count,
            'quiz_attempts_count' => $counts[$user->id] ?? 0,
        ]);

        return response()->json($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $data['password'],
            'role' => $data['role'],
        ]);

        return response()->json($user, 201);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount('lessons');

        $users = User::get(['id', 'username'])->keyBy('id')->all();
        $progress = collect();

        foreach (Quiz::with('lesson:id,title')->get() as $quiz) {
            foreach ($quiz->attempts as $index => $row) {
                if (($row['status'] ?? null) === 'graded' && ($row['user_id'] ?? null) === $user->id) {
                    $progress->push(QuizAttempt::fromRow($row, $quiz, $index, $users));
                }
            }
        }

        $progress = $progress->sortByDesc('score')->unique('lesson_id')->values()
            ->map(fn (QuizAttempt $attempt) => [
                'lesson_id' => $attempt->lesson_id,
                'lesson' => $attempt->lesson?->title,
                'quiz_id' => $attempt->quiz_id,
                'score' => $attempt->score,
                'completed_at' => $attempt->completed_at,
            ]);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'lessons_count' => $user->lessons_count,
            ],
            'progress' => $progress,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        if ($user->is($request->user()) && ($data['role'] ?? $user->role) !== User::ROLE_ADMIN) {
            abort(422, 'Anda tidak dapat menurunkan peran akun sendiri.');
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

        return response()->json($user->fresh());
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            abort(422, 'Anda tidak dapat menghapus akun sendiri.');
        }

        if ($user->isAdmin() && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            abort(422, 'Minimal satu admin harus tetap ada.');
        }

        if ($user->lessons()->exists()) {
            abort(422, 'Pengguna masih memiliki pelajaran. Hapus atau pindahkan pelajaran tersebut terlebih dahulu.');
        }

        $user->delete();

        return response()->json(['message' => 'Pengguna berhasil dihapus.']);
    }

    private function gradedCounts()
    {
        // shortcut: counts every attempt in PHP, fine for a classroom-sized dataset
        return Quiz::all(['id', 'attempts'])
            ->flatMap(fn (Quiz $quiz) => $quiz->attempts)
            ->filter(fn (array $row) => ($row['status'] ?? null) === 'graded' && isset($row['user_id']))
            ->countBy('user_id');
    }
}
