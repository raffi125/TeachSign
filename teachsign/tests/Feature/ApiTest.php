<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $student;

    private Lesson $lesson;

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
        return Quiz::create(array_merge([
            'lesson_id' => $this->lesson->id,
            'title' => 'Kuis API',
            'questions' => [[
                'question' => 'Berapa jari untuk angka tiga?',
                'type' => 'choice',
                'options' => ['Satu', 'Dua', 'Tiga'],
                'correct_answer' => 'Tiga',
            ]],
            'attempts' => [],
            'is_locked' => false,
            'is_score_visible' => true,
            'locked_by' => $this->admin->id,
        ], $overrides));
    }

    public function test_guest_cannot_access_protected_endpoints(): void
    {
        $this->getJson('/api/lessons')->assertUnauthorized();
        $this->getJson('/api/games')->assertUnauthorized();
        $this->getJson('/api/progress')->assertUnauthorized();
    }

    public function test_user_can_login_and_receive_a_token(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => $this->student->email,
            'password' => 'Password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'username', 'email', 'role']])
            ->assertJsonPath('user.id', $this->student->id);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->student->id,
        ]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->postJson('/api/login', [
            'email' => $this->student->email,
            'password' => 'salah',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Email atau kata sandi salah.');
    }

    public function test_user_can_view_profile_and_logout_revokes_token(): void
    {
        $token = $this->postJson('/api/login', [
            'email' => $this->student->email,
            'password' => 'Password123',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('username', $this->student->username);

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Berhasil keluar.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_student_cannot_access_admin_endpoints(): void
    {
        Sanctum::actingAs($this->student);

        $this->getJson('/api/admin/users')
            ->assertForbidden()
            ->assertJsonPath('message', 'Halaman ini hanya untuk admin.');
    }

    public function test_student_can_list_and_view_lessons(): void
    {
        Sanctum::actingAs($this->student);

        $this->getJson('/api/lessons')
            ->assertOk()
            ->assertJsonPath('data.0.title', $this->lesson->title)
            ->assertJsonPath('data.0.quizzes_count', 0);

        $this->getJson("/api/lessons/{$this->lesson->id}")
            ->assertOk()
            ->assertJsonPath('lesson.id', $this->lesson->id)
            ->assertJsonPath('quizzes', []);
    }

    public function test_student_can_view_unlocked_quiz_without_answer_key(): void
    {
        $quiz = $this->makeQuiz();
        Sanctum::actingAs($this->student);

        $response = $this->getJson("/api/quizzes/{$quiz->id}")->assertOk();

        $response->assertJsonPath('quiz.id', $quiz->id)
            ->assertJsonPath('questions.0.question', 'Berapa jari untuk angka tiga?');

        $this->assertArrayNotHasKey('correct_answer', $response->json('questions.0'));
    }

    public function test_locked_quiz_is_forbidden(): void
    {
        $quiz = $this->makeQuiz(['is_locked' => true, 'unlock_at' => null]);
        Sanctum::actingAs($this->student);

        $this->getJson("/api/quizzes/{$quiz->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Kuis ini masih terkunci.');
    }

    public function test_student_can_submit_quiz_and_receive_score(): void
    {
        $quiz = $this->makeQuiz(['is_score_visible' => true]);
        Sanctum::actingAs($this->student);

        $questionId = (string) $quiz->questions->first()->id;

        $this->postJson("/api/quizzes/{$quiz->id}", [
            'answers' => [$questionId => 'Tiga'],
        ])->assertOk()
            ->assertJsonPath('score', 100)
            ->assertJsonPath('correct', true)
            ->assertJsonPath('visible', true)
            ->assertJsonPath("correct_answers.{$questionId}", 'Tiga');
    }

    public function test_submit_hides_answer_key_when_score_is_hidden(): void
    {
        $quiz = $this->makeQuiz(['is_score_visible' => false]);
        Sanctum::actingAs($this->student);

        $questionId = (string) $quiz->questions->first()->id;

        $response = $this->postJson("/api/quizzes/{$quiz->id}", [
            'answers' => [$questionId => 'Tiga'],
        ])->assertOk();

        $response->assertJsonPath('visible', false);
        $response->assertJsonMissingPath('correct_answers');
    }

    public function test_student_can_view_games_and_submit_result(): void
    {
        Sanctum::actingAs($this->student);

        $this->getJson('/api/games')
            ->assertOk()
            ->assertJsonCount(3, 'modes');

        $this->getJson('/api/games/sign')
            ->assertOk()
            ->assertJsonPath('mode', 'sign');

        $this->postJson('/api/games/sign', [
            'hits' => 10,
            'misses' => 1,
            'max_combo' => 5,
            'seconds' => 30,
        ])->assertOk()
            ->assertJsonPath('is_new_best', true);

        $this->assertDatabaseHas('games', [
            'user_id' => $this->student->id,
            'mode' => 'sign',
        ]);
    }

    public function test_student_can_view_progress(): void
    {
        Sanctum::actingAs($this->student);

        $this->getJson('/api/progress')
            ->assertOk()
            ->assertJsonStructure([
                'stats' => ['total_lessons', 'completed', 'average', 'best'],
                'progress',
            ])
            ->assertJsonPath('stats.total_lessons', 1);
    }

    public function test_admin_can_manage_lessons(): void
    {
        Sanctum::actingAs($this->admin);

        $created = $this->postJson('/api/admin/lessons', [
            'title' => 'Kata Sapaan',
            'category' => 'Kata Sehari-hari',
            'description' => 'Panduan gerakan untuk kata sapaan sehari-hari.',
        ])->assertCreated()
            ->assertJsonPath('created_by', $this->admin->id)
            ->json();

        $this->putJson("/api/admin/lessons/{$created['id']}", [
            'title' => 'Kata Sapaan Baru',
            'category' => 'Kata Sehari-hari',
            'description' => 'Panduan gerakan untuk kata sapaan sehari-hari.',
        ])->assertOk()
            ->assertJsonPath('title', 'Kata Sapaan Baru');

        $this->deleteJson("/api/admin/lessons/{$created['id']}")
            ->assertOk()
            ->assertJsonPath('message', 'Pelajaran berhasil dihapus.');

        $this->assertDatabaseMissing('lessons', ['id' => $created['id']]);
    }

    public function test_admin_can_toggle_quiz_lock(): void
    {
        $quiz = $this->makeQuiz(['is_locked' => true]);
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/admin/quizzes/{$quiz->id}/toggle-lock")
            ->assertOk()
            ->assertJsonPath('is_locked', false);

        $this->assertFalse($quiz->fresh()->is_locked);
    }

    public function test_admin_can_grade_an_essay_attempt(): void
    {
        $quiz = $this->makeQuiz([
            'questions' => [[
                'question' => 'Jelaskan gerakan angka tiga.',
                'type' => 'essay',
                'options' => null,
                'correct_answer' => null,
            ]],
        ]);

        $questionId = (string) $quiz->questions->first()->id;

        Sanctum::actingAs($this->student);

        $this->postJson("/api/quizzes/{$quiz->id}", [
            'answers' => [$questionId => 'Tiga jari diangkat.'],
        ])->assertOk()
            ->assertJsonPath('pending', true);

        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/admin/grading/{$quiz->id}/0", ['score' => 85])
            ->assertOk()
            ->assertJsonPath('message', 'Nilai esai berhasil disimpan.');

        $attempt = $quiz->fresh()->attempts[0];

        $this->assertSame('graded', $attempt['status']);
        $this->assertSame(85, $attempt['score']);
        $this->assertSame($this->admin->id, $attempt['graded_by']);
    }

    public function test_admin_can_manage_mini_game_words(): void
    {
        Sanctum::actingAs($this->admin);

        $created = $this->postJson('/api/admin/words', ['word' => 'pagi'])
            ->assertCreated()
            ->assertJsonPath('word', 'PAGI')
            ->json();

        $this->putJson("/api/admin/words/{$created['id']}", ['word' => 'sore'])
            ->assertOk()
            ->assertJsonPath('word', 'SORE');

        $this->deleteJson("/api/admin/words/{$created['id']}")
            ->assertOk()
            ->assertJsonPath('message', 'Kata Mini Games berhasil dihapus.');

        $this->assertDatabaseMissing('games', ['id' => $created['id']]);
    }
}
