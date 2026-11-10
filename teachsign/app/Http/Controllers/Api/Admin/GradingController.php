<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GradingController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::get(['id', 'username'])->keyBy('id')->all();

        /** @var Collection<int, QuizAttempt> $attempts */
        $attempts = collect();

        foreach (Quiz::with('lesson:id,title')->get() as $quiz) {
            foreach ($quiz->attempts as $index => $row) {
                $attempts->push(QuizAttempt::fromRow($row, $quiz, $index, $users));
            }
        }

        $pending = $attempts->where('status', 'pending')
            ->sortBy(fn (QuizAttempt $attempt) => $attempt->created_at?->timestamp ?? 0)
            ->values();

        $graded = $attempts->where('status', 'graded')
            ->sortByDesc(fn (QuizAttempt $attempt) => $attempt->graded_at?->timestamp ?? 0)
            ->take(15)
            ->values();

        return response()->json([
            'pending' => $pending->map(fn (QuizAttempt $attempt) => $this->payload($attempt)),
            'graded' => $graded->map(fn (QuizAttempt $attempt) => $this->payload($attempt)),
        ]);
    }

    public function update(Request $request, Quiz $quiz, int $attempt): JsonResponse
    {
        $data = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:100'],
        ], [
            'score.required' => 'Nilai wajib diisi.',
            'score.integer' => 'Nilai harus berupa angka bulat.',
            'score.min' => 'Nilai minimal 0.',
            'score.max' => 'Nilai maksimal 100.',
        ]);

        $updated = $quiz->updateAttempt($attempt, [
            'score' => (int) $data['score'],
            'status' => 'graded',
            'graded_by' => $request->user()->id,
            'graded_at' => now()->toIso8601String(),
        ]);

        abort_unless($updated, 404, 'Jawaban tidak ditemukan.');

        return response()->json(['message' => 'Nilai esai berhasil disimpan.']);
    }

    private function payload(QuizAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'attempt_index' => $attempt->attempt_index,
            'quiz_id' => $attempt->quiz_id,
            'quiz' => $attempt->quiz?->title,
            'lesson' => $attempt->lesson?->title,
            'user_id' => $attempt->user_id,
            'username' => $attempt->user?->username,
            'score' => $attempt->score,
            'status' => $attempt->status,
            'answers' => $attempt->answers,
            'created_at' => $attempt->created_at,
            'graded_at' => $attempt->graded_at,
        ];
    }
}
