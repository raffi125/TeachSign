<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LessonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();
        $category = $request->string('category')->trim()->toString();

        $lessons = Lesson::query()
            ->withCount(['quizzes' => fn ($query) => $query->available()])
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->latest('id')
            ->paginate(9);

        $progress = $this->bestAttempts($request, $lessons->pluck('id')->all());

        $lessons->through(fn (Lesson $lesson) => [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'category' => $lesson->category,
            'media_url' => $lesson->media_url,
            'description' => $lesson->description,
            'quizzes_count' => $lesson->quizzes_count,
            'best_score' => $progress->get($lesson->id)?->score,
        ]);

        return response()->json($lessons);
    }

    public function show(Request $request, Lesson $lesson): JsonResponse
    {
        $lesson->load(['quizzes' => fn ($query) => $query->latest('id')]);

        $progress = $this->bestAttempts($request, [$lesson->id])->first();

        return response()->json([
            'lesson' => $lesson,
            'best_score' => $progress?->score,
            'quizzes' => $lesson->quizzes->map(fn (Quiz $quiz) => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'questions_count' => $quiz->questions_count,
                'duration_seconds' => $quiz->duration_seconds,
                'is_locked' => $quiz->is_locked,
                'unlock_at' => $quiz->unlock_at,
                'is_available' => $quiz->isAvailable(),
                'is_score_visible' => $quiz->is_score_visible,
            ]),
        ]);
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
