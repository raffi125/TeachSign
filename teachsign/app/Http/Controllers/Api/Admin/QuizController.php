<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $lessonId = $request->integer('lesson_id');

        $quizzes = Quiz::query()
            ->with(['lesson:id,title', 'locker:id,username'])
            ->when($lessonId > 0, fn ($query) => $query->where('lesson_id', $lessonId))
            ->latest('id')
            ->paginate(10);

        return response()->json($quizzes);
    }

    public function store(StoreQuizRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['is_locked'] = $request->boolean('is_locked', true);
        $data['is_score_visible'] = $request->boolean('is_score_visible', false);
        $data['locked_by'] = $request->user()->id;
        $data['unlock_at'] = $data['unlock_at'] ?? null;
        $data['duration_seconds'] = $data['duration_seconds'] ?? null;
        $data['attempts'] = [];

        $quiz = Quiz::create($data);

        return response()->json($quiz, 201);
    }

    public function show(Quiz $quiz): JsonResponse
    {
        $quiz->load(['lesson:id,title', 'locker:id,username']);

        return response()->json($quiz);
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz): JsonResponse
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

        return response()->json($quiz->fresh(['lesson:id,title']));
    }

    public function toggleLock(Request $request, Quiz $quiz): JsonResponse
    {
        $quiz->update([
            'is_locked' => ! $quiz->is_locked,
            'locked_by' => $request->user()->id,
            'unlock_at' => null,
        ]);

        return response()->json([
            'message' => $quiz->is_locked ? 'Kuis berhasil dikunci.' : 'Kuis berhasil dibuka.',
            'is_locked' => $quiz->is_locked,
        ]);
    }

    public function destroy(Quiz $quiz): JsonResponse
    {
        $quiz->delete();

        return response()->json(['message' => 'Kuis berhasil dihapus.']);
    }
}
