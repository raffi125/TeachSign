<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $student;

    private Lesson $lesson;

    private int $quizCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->student = User::factory()->create();

        $this->lesson = Lesson::create([
            'title' => 'Angka 1-5',
            'category' => 'Angka',
            'description' => 'Panduan gerakan tangan untuk angka satu sampai lima.',
            'created_by' => $this->admin->id,
        ]);
    }

    private function makeQuiz(array $overrides = []): Quiz
    {
        $question = $overrides['question'] ?? 'Berapa jari untuk angka tiga?';
        $options = $overrides['options'] ?? ['Satu', 'Dua', 'Tiga'];
        $correct = $overrides['correct_answer'] ?? 'Tiga';
        unset($overrides['question'], $overrides['options'], $overrides['correct_answer']);

        $this->quizCounter++;

        return Quiz::create(array_merge([
            'lesson_id' => $this->lesson->id,
            'title' => 'Kuis Uji '.$this->quizCounter,
            'questions' => [[
                'question' => $question,
                'type' => 'choice',
                'options' => $options,
                'correct_answer' => $correct,
            ]],
            'attempts' => [],
            'is_locked' => false,
            'is_score_visible' => true,
            'locked_by' => $this->admin->id,
        ], $overrides));
    }

    private function addQuestion(Quiz $quiz, array $question): QuizQuestion
    {
        $questions = $quiz->questions->all();
        $questions[] = $question + ['type' => 'choice', 'options' => null, 'correct_answer' => null];
        $quiz->questions = $questions;
        $quiz->save();

        return $quiz->questions->last();
    }

    private function answerPayload(Quiz $quiz, string $answer): array
    {
        return ['answers' => [$quiz->questions->first()->id => $answer]];
    }

    public function test_student_can_view_a_lesson(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.lessons.show', $this->lesson))
            ->assertOk()
            ->assertSee('Angka 1-5');
    }

    public function test_youtube_media_renders_an_embed_iframe(): void
    {
        $this->lesson->update(['media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10s']);

        $this->actingAs($this->student)
            ->get(route('student.lessons.show', $this->lesson))
            ->assertOk()
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertDontSee('Buka media gestur');
    }

    public function test_google_drive_media_renders_a_preview_iframe(): void
    {
        $this->lesson->update(['media_url' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/view?usp=sharing']);

        $this->actingAs($this->student)
            ->get(route('student.lessons.show', $this->lesson))
            ->assertOk()
            ->assertSee('drive.google.com/file/d/1AbCdEfGhIjKlMnOp/preview', false);
    }

    public function test_unknown_media_url_falls_back_to_a_plain_link(): void
    {
        $this->lesson->update(['media_url' => 'https://example.com/panduan']);

        $this->actingAs($this->student)
            ->get(route('student.lessons.show', $this->lesson))
            ->assertOk()
            ->assertSee('Buka media gestur')
            ->assertDontSee('<iframe', false);
    }

    public function test_unsafe_media_scheme_is_not_rendered(): void
    {
        $this->lesson->update(['media_url' => 'javascript:alert(1)']);

        $this->actingAs($this->student)
            ->get(route('student.lessons.show', $this->lesson))
            ->assertOk()
            ->assertDontSee('javascript:alert')
            ->assertDontSee('Buka media gestur');
    }

    public function test_quiz_question_media_is_stored_and_rendered(): void
    {
        $quiz = $this->makeQuiz();

        $this->addQuestion($quiz, [
            'question' => 'Huruf apa yang ditunjukkan gambar ini?',
            'options' => ['A', 'B'],
            'correct_answer' => 'A',
            'media_url' => '/images/abjad/A.webp',
        ]);

        $this->assertSame('/images/abjad/A.webp', $quiz->fresh()->questions->last()->media_url);

        $this->actingAs($this->student)
            ->get(route('student.quizzes.show', [$this->lesson, $quiz]))
            ->assertOk()
            ->assertSee('/images/abjad/A.webp', false);
    }

    public function test_locked_quiz_cannot_be_opened_or_submitted(): void
    {
        $quiz = $this->makeQuiz(['is_locked' => true, 'unlock_at' => now()->addDay()]);

        $this->actingAs($this->student)
            ->get(route('student.quizzes.show', [$this->lesson, $quiz]))
            ->assertForbidden();

        $this->actingAs($this->student)
            ->post(route('student.quizzes.submit', [$this->lesson, $quiz]), $this->answerPayload($quiz, 'Tiga'))
            ->assertForbidden();
    }

    public function test_correct_quiz_answer_records_an_attempt_and_shows_score(): void
    {
        $quiz = $this->makeQuiz();

        $response = $this->actingAs($this->student)->post(
            route('student.quizzes.submit', [$this->lesson, $quiz]),
            $this->answerPayload($quiz, 'Tiga')
        );

        $response->assertRedirect(route('student.lessons.show', $this->lesson));
        $response->assertSessionHas('quiz_result', function (array $result) {
            return $result['correct'] === true
                && $result['correct_count'] === 1
                && $result['total'] === 1
                && $result['score'] === 100
                && $result['visible'] === true;
        });

        $attempts = $quiz->fresh()->attempts;

        $this->assertCount(1, $attempts);
        $this->assertSame($this->student->id, $attempts[0]['user_id']);
        $this->assertSame(100, $attempts[0]['score']);
        $this->assertSame('graded', $attempts[0]['status']);
    }

    public function test_multi_question_quiz_scores_proportionally(): void
    {
        $quiz = $this->makeQuiz();
        $second = $this->addQuestion($quiz, [
            'question' => 'Soal kedua tentang angka dua?',
            'options' => ['Satu', 'Dua', 'Tiga'],
            'correct_answer' => 'Dua',
        ]);

        $response = $this->actingAs($this->student)->post(
            route('student.quizzes.submit', [$this->lesson, $quiz]),
            ['answers' => [
                $quiz->questions->first()->id => 'Tiga',
                $second->id => 'Satu',
            ]]
        );

        $response->assertSessionHas('quiz_result', function (array $result) {
            return $result['score'] === 50 && $result['correct_count'] === 1 && $result['total'] === 2;
        });
    }

    public function test_quiz_with_hidden_score_does_not_expose_answers(): void
    {
        $quiz = $this->makeQuiz(['is_score_visible' => false]);

        $response = $this->actingAs($this->student)->post(
            route('student.quizzes.submit', [$this->lesson, $quiz]),
            $this->answerPayload($quiz, 'Satu')
        );

        $response->assertSessionHas('quiz_result', function (array $result) {
            return $result['visible'] === false && ! array_key_exists('correct_answers', $result);
        });
    }

    public function test_answer_must_be_one_of_the_options(): void
    {
        $quiz = $this->makeQuiz();

        $this->actingAs($this->student)->post(
            route('student.quizzes.submit', [$this->lesson, $quiz]),
            $this->answerPayload($quiz, 'Jawaban palsu')
        )->assertSessionHasErrors('answers');
    }

    public function test_missing_answers_are_rejected(): void
    {
        $quiz = $this->makeQuiz();
        $this->addQuestion($quiz, [
            'question' => 'Soal kedua tentang angka dua?',
            'options' => ['Satu', 'Dua', 'Tiga'],
            'correct_answer' => 'Dua',
        ]);

        $this->actingAs($this->student)->post(
            route('student.quizzes.submit', [$this->lesson, $quiz]),
            ['answers' => [$quiz->questions->first()->id => 'Tiga']]
        )->assertSessionHasErrors('answers');
    }

    public function test_mini_game_scores_hits_and_combo_then_keeps_the_best_score(): void
    {
        $this->actingAs($this->student)->post(route('student.game.submit', 'sign'), [
            'hits' => 20,
            'misses' => 0,
            'max_combo' => 10,
            'seconds' => 30,
        ])->assertRedirect(route('student.games.index'))
            ->assertSessionHas('game_result', fn (array $result) => $result['score'] === 100);

        $this->assertDatabaseHas('games', [
            'user_id' => $this->student->id,
            'mode' => 'sign',
            'score' => 100,
        ]);

        $this->actingAs($this->student)->post(route('student.game.submit', 'sign'), [
            'hits' => 0,
            'misses' => 20,
            'max_combo' => 0,
            'seconds' => 60,
        ]);

        $score = Game::where('user_id', $this->student->id)->where('mode', 'sign')->firstOrFail();
        $this->assertSame(100, $score->score);
    }

    public function test_game_modes_open_without_picking_a_lesson(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.game.show', 'sign'))
            ->assertOk()
            ->assertSee('game-board')
            ->assertSee('Sign Rush');
    }

    public function test_unknown_game_mode_is_not_found(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.game.show', 'arcade'))
            ->assertNotFound();
    }

    public function test_word_quest_mode_shows_a_typing_answer_field(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.game.show', 'word'))
            ->assertOk()
            ->assertSee('word-input')
            ->assertSee('Ketik kata')
            ->assertSee('Word Quest');
    }

    public function test_word_quest_uses_active_words_as_clues(): void
    {
        Game::create(['word' => 'PAGI', 'is_active' => true]);
        Game::create(['word' => 'BABY', 'is_active' => false]);

        $this->actingAs($this->student)
            ->get(route('student.game.show', 'word'))
            ->assertOk()
            ->assertSee('PAGI')
            ->assertDontSee('BABY')
            ->assertSee('word-clue-sequence');
    }

    public function test_flip_flap_mode_renders_a_card_board(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.game.show', 'flip'))
            ->assertOk()
            ->assertSee('flip-board')
            ->assertSee('Flip Flap');
    }

    public function test_word_quest_score_is_recorded(): void
    {
        $this->actingAs($this->student)->post(route('student.game.submit', 'word'), [
            'hits' => 5,
            'misses' => 0,
            'max_combo' => 5,
            'seconds' => 40,
        ])->assertSessionHas('game_result', fn (array $result) => $result['score'] === 100);

        $this->assertDatabaseHas('games', [
            'user_id' => $this->student->id,
            'mode' => 'word',
            'score' => 100,
        ]);
    }

    public function test_mini_game_rejects_invalid_hits(): void
    {
        $this->actingAs($this->student)->post(route('student.game.submit', 'sign'), [
            'hits' => -1,
            'misses' => 0,
            'max_combo' => 0,
            'seconds' => 10,
        ])->assertSessionHasErrors('hits');
    }

    public function test_progress_page_lists_student_scores(): void
    {
        $quiz = $this->makeQuiz();
        $quiz->addAttempt([
            'user_id' => $this->student->id,
            'answers' => [],
            'choice_correct' => 1,
            'choice_total' => 1,
            'essay_total' => 0,
            'score' => 80,
            'status' => 'graded',
            'graded_by' => null,
            'graded_at' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.progress.index'))
            ->assertOk()
            ->assertSee('Angka 1-5')
            ->assertSee('80');
    }

    public function test_student_lesson_index_lists_lessons(): void
    {
        $this->makeQuiz();

        $this->actingAs($this->student)
            ->get(route('student.lessons.index'))
            ->assertOk()
            ->assertSee('Angka 1-5')
            ->assertSee('1 kuis terbuka');
    }

    public function test_student_quiz_index_shows_locked_and_open_quizzes(): void
    {
        $this->makeQuiz();
        $this->makeQuiz(['title' => 'Soal terkunci?', 'is_locked' => true]);

        $this->actingAs($this->student)
            ->get(route('student.quizzes.index'))
            ->assertOk()
            ->assertSee('Soal terkunci?')
            ->assertSee('Kerjakan')
            ->assertSee('Terkunci');
    }

    public function test_student_game_index_lists_the_three_arcade_modes(): void
    {
        Game::create(['user_id' => $this->student->id, 'mode' => 'flip', 'score' => 84]);

        $this->actingAs($this->student)
            ->get(route('student.games.index'))
            ->assertOk()
            ->assertSee('Sign Rush')
            ->assertSee('Word Quest')
            ->assertSee('Flip Flap')
            ->assertSee('Terbaik 84');
    }

    public function test_dashboard_is_informational_for_students(): void
    {
        $this->makeQuiz();

        $this->actingAs($this->student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Halo, '.$this->student->username)
            ->assertSee('Lanjutkan Belajar')
            ->assertSee('Aktivitas Terakhir')
            ->assertSee('Kategori Materi');
    }

    public function test_quiz_page_shows_timer_and_number_navigator(): void
    {
        $quiz = $this->makeQuiz(['duration_seconds' => 60]);
        $this->addQuestion($quiz, [
            'question' => 'Soal kedua tentang angka dua?',
            'options' => ['Satu', 'Dua', 'Tiga'],
            'correct_answer' => 'Dua',
        ]);

        $this->actingAs($this->student)
            ->get(route('student.quizzes.show', [$this->lesson, $quiz]))
            ->assertOk()
            ->assertSee('quiz-timer')
            ->assertSee('Soal 1 dari 2')
            ->assertSee('quiz-nav')
            ->assertSee('Soal kedua tentang angka dua?');
    }

    public function test_expired_quiz_submission_is_rejected(): void
    {
        $quiz = $this->makeQuiz(['duration_seconds' => 30]);

        $this->actingAs($this->student)
            ->withSession(['quiz_started.'.$quiz->id => now()->subMinutes(5)->getTimestamp()])
            ->post(route('student.quizzes.submit', [$this->lesson, $quiz]), $this->answerPayload($quiz, 'Tiga'))
            ->assertRedirect(route('student.lessons.show', $this->lesson))
            ->assertSessionHas('error');

        $this->assertSame([], $quiz->fresh()->attempts->all());
    }

    public function test_quiz_submission_within_time_limit_is_accepted(): void
    {
        $quiz = $this->makeQuiz(['duration_seconds' => 30]);

        $this->actingAs($this->student)
            ->withSession(['quiz_started.'.$quiz->id => now()->getTimestamp()])
            ->post(route('student.quizzes.submit', [$this->lesson, $quiz]), $this->answerPayload($quiz, 'Tiga'))
            ->assertRedirect(route('student.lessons.show', $this->lesson));

        $attempts = $quiz->fresh()->attempts;

        $this->assertCount(1, $attempts);
        $this->assertSame(100, $attempts[0]['score']);
    }

    public function test_essay_submission_waits_for_manual_grading(): void
    {
        $quiz = $this->makeQuiz();
        $essay = $this->addQuestion($quiz, [
            'question' => 'Sebutkan satu kata sapaan yang kamu ketahui.',
            'type' => 'essay',
            'options' => null,
            'correct_answer' => null,
        ]);

        $response = $this->actingAs($this->student)->post(
            route('student.quizzes.submit', [$this->lesson, $quiz]),
            ['answers' => [
                $quiz->questions->first()->id => 'Tiga',
                $essay->id => 'Halo semuanya',
            ]]
        );

        $response->assertRedirect(route('student.lessons.show', $this->lesson));
        $response->assertSessionHas('quiz_result', function (array $result) {
            return $result['pending'] === true
                && $result['correct_count'] === 1
                && $result['choice_total'] === 1
                && $result['essay_count'] === 1;
        });

        $attempts = $quiz->fresh()->attempts;

        $this->assertCount(1, $attempts);
        $this->assertSame('pending', $attempts[0]['status']);
        $this->assertNull($attempts[0]['score']);
        $this->assertSame('Halo semuanya', $attempts[0]['answers'][$essay->id]);
    }

    public function test_admin_manual_score_is_saved_and_updates_the_attempt(): void
    {
        $quiz = $this->makeQuiz();
        $essay = $this->addQuestion($quiz, [
            'question' => 'Sebutkan satu kata sapaan yang kamu ketahui.',
            'type' => 'essay',
            'options' => null,
            'correct_answer' => null,
        ]);

        $this->actingAs($this->student)->post(
            route('student.quizzes.submit', [$this->lesson, $quiz]),
            ['answers' => [
                $quiz->questions->first()->id => 'Tiga',
                $essay->id => 'Halo',
            ]]
        );

        $this->actingAs($this->admin)
            ->get(route('admin.grading.index'))
            ->assertOk()
            ->assertSee($this->student->username)
            ->assertSee('Halo');

        $this->actingAs($this->admin)
            ->from(route('admin.grading.index'))
            ->patch(route('admin.grading.update', [$quiz, 0]), ['score' => 90])
            ->assertRedirect(route('admin.grading.index'))
            ->assertSessionHas('success');

        $attempt = $quiz->fresh()->attempts[0];

        $this->assertSame('graded', $attempt['status']);
        $this->assertSame(90, $attempt['score']);
        $this->assertSame($this->admin->id, $attempt['graded_by']);
    }

    public function test_blank_essay_answer_is_rejected(): void
    {
        $quiz = $this->makeQuiz();
        $essay = $this->addQuestion($quiz, [
            'question' => 'Sebutkan satu kata sapaan yang kamu ketahui.',
            'type' => 'essay',
            'options' => null,
            'correct_answer' => null,
        ]);

        $this->actingAs($this->student)->post(
            route('student.quizzes.submit', [$this->lesson, $quiz]),
            ['answers' => [
                $quiz->questions->first()->id => 'Tiga',
                $essay->id => '   ',
            ]]
        )->assertSessionHasErrors('answers.'.$essay->id);
    }
}
