<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $attempts = $this->attempts();
            $graded = $attempts->where('status', 'graded');

            $stats = [
                'users' => User::count(),
                'students' => User::where('role', User::ROLE_STUDENT)->count(),
                'lessons' => Lesson::count(),
                'quizzes' => Quiz::count(),
                'locked_quizzes' => Quiz::where('is_locked', true)->count(),
                'graded_attempts' => $graded->count(),
                'pending_attempts' => $attempts->where('status', 'pending')->count(),
            ];

            $recentProgress = $graded
                ->sortByDesc(fn (QuizAttempt $attempt) => $attempt->graded_at?->timestamp ?? 0)
                ->take(5)
                ->values();

            $lessonProgress = $graded
                ->groupBy('lesson_id')
                ->map(fn (Collection $rows) => (object) [
                    'title' => $rows->first()->lesson?->title ?? '—',
                    'total' => $rows->count(),
                ])
                ->sortByDesc('total')
                ->take(6)
                ->values();

            return view('dashboard', compact('stats', 'recentProgress', 'lessonProgress'));
        }

        $myProgress = $this->attempts()
            ->where('status', 'graded')
            ->where('user_id', $user->id)
            ->sortByDesc('score')
            ->unique('lesson_id');

        $totalLessons = Lesson::count();
        $completed = $myProgress->count();

        $stats = [
            'lessons' => $totalLessons,
            'completed' => $completed,
            'remaining' => max(0, $totalLessons - $completed),
            'average' => (int) round($myProgress->avg('score') ?? 0),
            'best' => (int) ($myProgress->max('score') ?? 0),
        ];

        $continueLessons = Lesson::query()
            ->whereNotIn('id', $myProgress->pluck('lesson_id'))
            ->withCount(['quizzes' => fn ($query) => $query->available()])
            ->latest('id')
            ->limit(3)
            ->get();

        $recentProgress = $myProgress->take(5);
        $availableQuizCount = Quiz::available()->count();
        $chartData = $myProgress->sortBy(fn (QuizAttempt $attempt) => $attempt->completed_at?->timestamp ?? 0)
            ->map(fn (QuizAttempt $attempt) => [
                'label' => $attempt->lesson?->title ?? '—',
                'score' => $attempt->score,
            ])
            ->values();
        $categories = Lesson::query()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        return view('dashboard', compact(
            'stats',
            'continueLessons',
            'recentProgress',
            'availableQuizCount',
            'chartData',
            'categories'
        ));
    }

    /**
     * @return Collection<int, QuizAttempt>
     */
    private function attempts(): Collection
    {
        $users = User::get(['id', 'username'])->keyBy('id')->all();

        return Quiz::with('lesson:id,title,category')
            ->get()
            ->flatMap(fn (Quiz $quiz) => collect($quiz->attempts)
                ->map(fn (array $row, int $index) => QuizAttempt::fromRow($row, $quiz, $index, $users)));
    }
}
