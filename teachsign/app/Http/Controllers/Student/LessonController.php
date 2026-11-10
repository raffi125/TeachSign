<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $category = $request->string('category')->trim()->toString();

        $lessons = Lesson::query()
            ->withCount(['quizzes' => fn ($query) => $query->available()])
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->latest('id')
            ->paginate(9)
            ->withQueryString();

        $progress = $this->bestAttempts($request, $lessons->pluck('id')->all());

        return view('student.lessons.index', compact('lessons', 'progress', 'search', 'category'));
    }

    public function show(Request $request, Lesson $lesson): View
    {
        $lesson->load([
            'quizzes' => fn ($query) => $query->latest('id'),
        ]);

        $progress = $this->bestAttempts($request, [$lesson->id])->first();

        $availableQuizzes = $lesson->quizzes->filter->isAvailable();

        return view('student.lessons.show', compact('lesson', 'progress', 'availableQuizzes'));
    }

    /**
     * @param  array<int, int>  $lessonIds
     * @return Collection<int, QuizAttempt>
     */
    private function bestAttempts(Request $request, array $lessonIds): Collection
    {
        $user = $request->user();
        $users = [$user->id => $user];

        return Quiz::with('lesson:id,title,category')
            ->whereIn('lesson_id', $lessonIds)
            ->get()
            ->flatMap(fn (Quiz $quiz) => collect($quiz->attempts)
                ->map(fn (array $row, int $index) => QuizAttempt::fromRow($row, $quiz, $index, $users)))
            ->where('status', 'graded')
            ->where('user_id', $user->id)
            ->sortByDesc('score')
            ->unique('lesson_id')
            ->keyBy('lesson_id');
    }
}
