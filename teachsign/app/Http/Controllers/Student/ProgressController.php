<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $users = [$user->id => $user];

        $attempts = Quiz::with('lesson:id,title,category')
            ->get()
            ->flatMap(fn (Quiz $quiz) => collect($quiz->attempts)
                ->map(fn (array $row, int $index) => QuizAttempt::fromRow($row, $quiz, $index, $users)))
            ->where('status', 'graded')
            ->where('user_id', $user->id);

        $best = $attempts->sortByDesc('score')->unique('lesson_id');

        $stats = [
            'total_lessons' => Lesson::count(),
            'completed' => $best->count(),
            'average' => (int) round($best->avg('score') ?? 0),
            'best' => (int) ($best->max('score') ?? 0),
        ];

        $progress = $best->sortByDesc(fn (QuizAttempt $attempt) => $attempt->completed_at?->timestamp ?? 0)->values();

        return view('student.progress.index', compact('progress', 'stats'));
    }
}
