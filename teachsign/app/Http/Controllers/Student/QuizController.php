<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\QuizAnswersRequest;
use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
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
            ->paginate(10)
            ->withQueryString();

        $lessons = Lesson::orderBy('title')->get(['id', 'title']);

        return view('student.quizzes.index', compact('quizzes', 'lessons', 'lessonId', 'status'));
    }

    public function show(Lesson $lesson, Quiz $quiz): View
    {
        $this->ensurePlayable($lesson, $quiz);

        $remaining = null;
        $deadline = null;

        if ($quiz->duration_seconds !== null) {
            $sessionKey = 'quiz_started.'.$quiz->id;
            $startedAt = (int) session($sessionKey, 0);

            if ($startedAt <= 0 || (time() - $startedAt) > $quiz->duration_seconds) {
                $startedAt = time();
                session([$sessionKey => $startedAt]);
            }

            $remaining = max(0, $quiz->duration_seconds - (time() - $startedAt));
            $deadline = now()->addSeconds($remaining)->getTimestampMs();
        }

        return view('student.quizzes.show', compact('lesson', 'quiz', 'remaining', 'deadline'));
    }

    public function submit(QuizAnswersRequest $request, Lesson $lesson, Quiz $quiz): RedirectResponse
    {
        $this->ensurePlayable($lesson, $quiz);

        $sessionKey = 'quiz_started.'.$quiz->id;
        $startedAt = (int) session($sessionKey, 0);
        session()->forget($sessionKey);

        if ($quiz->duration_seconds !== null) {
            $elapsed = $startedAt > 0 ? time() - $startedAt : null;

            if ($elapsed === null || $elapsed > $quiz->duration_seconds + 2) {
                return redirect()
                    ->route('student.lessons.show', $lesson)
                    ->with('error', 'Waktu pengerjaan kuis sudah habis. Silakan coba lagi.');
            }
        }

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
                'lesson_id' => $lesson->id,
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

            return redirect()
                ->route('student.lessons.show', $lesson)
                ->with('quiz_result', [
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
            'lesson_id' => $lesson->id,
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

        return redirect()
            ->route('student.lessons.show', $lesson)
            ->with('quiz_result', $result);
    }

    private function ensurePlayable(Lesson $lesson, Quiz $quiz): void
    {
        abort_unless($quiz->lesson_id === $lesson->id, 404);

        abort_unless($quiz->isAvailable(), 403, 'Kuis ini masih terkunci.');
    }
}
