<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class ProgressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->integer('user_id');
        $lessonId = $request->integer('lesson_id');
        $status = $request->string('status')->trim()->toString();

        $users = User::get(['id', 'username'])->keyBy('id')->all();

        /** @var Collection<int, QuizAttempt> $items */
        $items = collect();

        foreach (Quiz::with('lesson:id,title')->get() as $quiz) {
            foreach ($quiz->attempts as $index => $row) {
                $items->push(QuizAttempt::fromRow($row, $quiz, $index, $users));
            }
        }

        $items = $items
            ->when($userId > 0, fn (Collection $rows) => $rows->where('user_id', $userId))
            ->when($lessonId > 0, fn (Collection $rows) => $rows->where('lesson_id', $lessonId))
            ->when(in_array($status, ['graded', 'pending'], true), fn (Collection $rows) => $rows->where('status', $status))
            ->sortByDesc(fn (QuizAttempt $attempt) => $attempt->created_at?->timestamp ?? 0)
            ->values();

        $attempts = new LengthAwarePaginator(
            $items->forPage(Paginator::resolveCurrentPage(), 15)->values(),
            $items->count(),
            15,
            Paginator::resolveCurrentPage(),
            ['path' => Paginator::resolveCurrentPath()],
        );
        $attempts->withQueryString();

        $attempts->through(fn (QuizAttempt $attempt) => [
            'id' => $attempt->id,
            'quiz_id' => $attempt->quiz_id,
            'quiz' => $attempt->quiz?->title,
            'lesson_id' => $attempt->lesson_id,
            'lesson' => $attempt->lesson?->title,
            'user_id' => $attempt->user_id,
            'username' => $attempt->user?->username,
            'score' => $attempt->score,
            'status' => $attempt->status,
            'created_at' => $attempt->created_at,
            'graded_at' => $attempt->graded_at,
        ]);

        return response()->json($attempts);
    }
}
