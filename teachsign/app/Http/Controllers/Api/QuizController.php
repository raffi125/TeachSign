<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\QuizAnswersRequest;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $lessonId = $request->integer('lesson_id');
        $status = $request->string('status')->trim()->toString();

        $quizzes = Quiz::query()
            ->with('lesson:id,title,category')
            ->when($lessonId > 0, fn ($query) => $query->where('lesson_id', $lessonId))
            ->when($status === 'open', fn ($query) => $query->available())
            ->when($status === 'locked', fn ($query) => $query->where(function ($query) {
                $query->where('is_locked', true)
                    ->where(fn ($q) => $q->whereNull('unlock_at')->orWhere('unlock_at', '>', now()));
            }))
            ->latest('id')
            ->paginate(10);

        $quizzes->through(fn (Quiz $quiz) => [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'lesson' => $quiz->lesson,
            'questions_count' => $quiz->questions_count,
            'duration_seconds' => $quiz->duration_seconds,
            'is_locked' => $quiz->is_locked,
            'unlock_at' => $quiz->unlock_at,
            'is_available' => $quiz->isAvailable(),
        ]);

        return response()->json($quizzes);
    }

    public function show(Quiz $quiz): JsonResponse
    {
        abort_unless($quiz->isAvailable(), 403, 'Kuis ini masih terkunci.');

        return response()->json([
            'quiz' => [
                'id' => $quiz->id,
                'lesson_id' => $quiz->lesson_id,
                'title' => $quiz->title,
                'duration_seconds' => $quiz->duration_seconds,
                'is_score_visible' => $quiz->is_score_visible,
            ],
            'questions' => $quiz->questions->map(fn ($question) => [
                'id' => $question->id,
                'question' => $question->question,
                'type' => $question->type,
                'options' => $question->type === 'essay' ? null : $question->options,
                'media_url' => $question->media_url,
            ]),
        ]);
    }

    public function submit(QuizAnswersRequest $request, Quiz $quiz): JsonResponse
    {
        abort_unless($quiz->isAvailable(), 403, 'Kuis ini masih terkunci.');

        $answers = $request->validated('answers');
        $questions = $quiz->questions;
        $essayQuestions = $questions->where('type', 'essay');
        $choiceQuestions = $questions->where('type', '!=', 'essay');
        $correctCount = 0;
        $missing = false;

        foreach ($choiceQuestions as $question) {
            $answer = $answers[$question->id] ?? null;

            if (! is_string($answer)) {
                $missing = true;

                continue;
            }

            if (in_array($answer, $question->options ?? [], true) && $answer === $question->correct_answer) {
                $correctCount++;
            }
        }

        foreach ($essayQuestions as $question) {
            if (trim((string) ($answers[$question->id] ?? '')) === '') {
                $missing = true;
            }
        }

        if ($missing) {
            throw ValidationException::withMessages([
                'answers' => 'Jawab semua soal sebelum mengirim.',
            ]);
        }

        if ($essayQuestions->isNotEmpty()) {
            $quiz->addAttempt([
                'user_id' => $request->user()->id,
                'lesson_id' => $quiz->lesson_id,
                'answers' => $answers,
                'choice_correct' => $correctCount,
                'choice_total' => $choiceQuestions->count(),
                'essay_total' => $essayQuestions->count(),
                'score' => null,
                'status' => 'pending',
                'graded_by' => null,
                'graded_at' => null,
                'created_at' => now()->toIso8601String(),
            ]);

            return response()->json([
                'quiz_id' => $quiz->id,
                'pending' => true,
                'correct_count' => $correctCount,
                'choice_total' => $choiceQuestions->count(),
                'essay_count' => $essayQuestions->count(),
                'total' => $questions->count(),
                'visible' => (bool) $quiz->is_score_visible,
            ]);
        }

        $total = max(1, $questions->count());
        $score = (int) round(($correctCount / $total) * 100);

        $quiz->addAttempt([
            'user_id' => $request->user()->id,
            'lesson_id' => $quiz->lesson_id,
            'answers' => $answers,
            'choice_correct' => $correctCount,
            'choice_total' => $choiceQuestions->count(),
            'essay_total' => 0,
            'score' => $score,
            'status' => 'graded',
            'graded_by' => null,
            'graded_at' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ]);

        $result = [
            'quiz_id' => $quiz->id,
            'correct' => $correctCount === $total,
            'correct_count' => $correctCount,
            'total' => $total,
            'score' => $score,
            'visible' => (bool) $quiz->is_score_visible,
        ];

        if ($quiz->is_score_visible) {
            $result['correct_answers'] = $questions->mapWithKeys(
                fn ($question) => [$question->id => $question->correct_answer]
            )->all();
        }

        return response()->json($result);
    }
}
