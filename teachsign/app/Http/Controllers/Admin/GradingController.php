<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradingController extends Controller
{
    public function index(): View
    {
        // shortcut: hydrates every quiz attempt in PHP, fine for a classroom-sized dataset
        $users = User::get(['id', 'username'])->keyBy('id')->all();
        $quizzes = Quiz::with('lesson:id,title')->latest('id')->get();

        $pending = collect();
        $graded = collect();

        foreach ($quizzes as $quiz) {
            foreach ($quiz->attempts as $index => $row) {
                $attempt = QuizAttempt::fromRow($row, $quiz, $index, $users);
                ($row['status'] ?? null) === 'pending' ? $pending->push($attempt) : $graded->push($attempt);
            }
        }

        $pending = $pending->sortBy(fn (QuizAttempt $attempt) => $attempt->created_at?->timestamp ?? 0)->values();
        $graded = $graded->sortByDesc('graded_at')->take(15)->values();

        return view('admin.grading.index', compact('pending', 'graded'));
    }

    public function update(Request $request, Quiz $quiz, int $attempt): RedirectResponse
    {
        $data = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:100'],
        ], [
            'score.required' => 'Nilai wajib diisi.',
            'score.min' => 'Nilai minimal 0.',
            'score.max' => 'Nilai maksimal 100.',
        ]);

        $quiz->updateAttempt($attempt, [
            'score' => $data['score'],
            'status' => 'graded',
            'graded_by' => $request->user()->id,
            'graded_at' => now()->toIso8601String(),
        ]);

        return back()->with('success', 'Nilai esai berhasil disimpan.');
    }
}
