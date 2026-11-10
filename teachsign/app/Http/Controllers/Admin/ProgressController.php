<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function index(Request $request): View
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

        $students = User::where('role', User::ROLE_STUDENT)->orderBy('username')->get(['id', 'username']);
        $lessons = Lesson::orderBy('title')->get(['id', 'title']);

        return view('admin.progress.index', compact('attempts', 'students', 'lessons', 'userId', 'lessonId', 'status'));
    }
}
