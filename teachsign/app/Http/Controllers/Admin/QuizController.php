<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $lessonId = $request->integer('lesson_id');

        $quizzes = Quiz::query()
            ->with(['lesson:id,title', 'locker:id,username'])
            ->when($lessonId > 0, fn ($query) => $query->where('lesson_id', $lessonId))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $lessons = Lesson::orderBy('title')->get(['id', 'title']);

        return view('admin.quizzes.index', compact('quizzes', 'lessons', 'lessonId'));
    }

    public function create(Request $request): View
    {
        $lessons = Lesson::orderBy('title')->get(['id', 'title']);
        $selectedLesson = $request->integer('lesson_id') ?: null;

        return view('admin.quizzes.create', compact('lessons', 'selectedLesson'));
    }

    public function store(StoreQuizRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['is_locked'] = $request->boolean('is_locked', true);
        $data['is_score_visible'] = $request->boolean('is_score_visible', false);
        $data['locked_by'] = $request->user()->id;
        $data['unlock_at'] = $data['unlock_at'] ?? null;
        $data['duration_seconds'] = $data['duration_seconds'] ?? null;
        $data['attempts'] = [];

        Quiz::create($data);

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Kuis berhasil ditambahkan.');
    }

    public function show(Quiz $quiz): View
    {
        $quiz->load(['lesson:id,title', 'locker:id,username']);

        return view('admin.quizzes.show', compact('quiz'));
    }

    public function edit(Quiz $quiz): View
    {
        $lessons = Lesson::orderBy('title')->get(['id', 'title']);

        return view('admin.quizzes.edit', compact('quiz', 'lessons'));
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz): RedirectResponse
    {
        $data = $request->validated();

        if ($request->has('is_locked')) {
            $data['is_locked'] = $request->boolean('is_locked');
            $data['locked_by'] = $request->user()->id;
        }

        if ($request->has('is_score_visible')) {
            $data['is_score_visible'] = $request->boolean('is_score_visible');
        }

        if ($request->has('unlock_at')) {
            $data['unlock_at'] = $data['unlock_at'] ?? null;
        }

        if ($request->has('duration_seconds')) {
            $data['duration_seconds'] = $data['duration_seconds'] ?? null;
        }

        $quiz->update($data);

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Kuis berhasil diperbarui.');
    }

    public function toggleLock(Request $request, Quiz $quiz): RedirectResponse
    {
        $quiz->update([
            'is_locked' => ! $quiz->is_locked,
            'locked_by' => $request->user()->id,
            'unlock_at' => null,
        ]);

        return back()->with('success', $quiz->is_locked ? 'Kuis berhasil dikunci.' : 'Kuis berhasil dibuka.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $quiz->delete();

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Kuis berhasil dihapus.');
    }
}
