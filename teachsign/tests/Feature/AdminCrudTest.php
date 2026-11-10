<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.lessons.index'))->assertRedirect(route('login'));
    }

    public function test_students_cannot_access_admin_pages(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('admin.lessons.index'))->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_a_lesson(): void
    {
        $this->actingAs($this->admin)->post(route('admin.lessons.store'), [
            'title' => 'Mengenal Abjad A-E',
            'category' => 'Abjad',
            'media_url' => 'https://example.com/video.mp4',
            'description' => 'Panduan lengkap gerakan tangan untuk huruf A sampai E.',
        ])->assertRedirect(route('admin.lessons.index'));

        $lesson = Lesson::firstWhere('title', 'Mengenal Abjad A-E');
        $this->assertNotNull($lesson);
        $this->assertSame($this->admin->id, $lesson->created_by);

        $this->actingAs($this->admin)->put(route('admin.lessons.update', $lesson), [
            'title' => 'Mengenal Abjad A-F',
        ])->assertRedirect(route('admin.lessons.index'));

        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'title' => 'Mengenal Abjad A-F']);

        $this->actingAs($this->admin)->delete(route('admin.lessons.destroy', $lesson))
            ->assertRedirect(route('admin.lessons.index'));

        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    public function test_admin_lesson_pages_preview_media_before_and_after_saving(): void
    {
        $lesson = Lesson::create([
            'title' => 'Mengenal Abjad A-E',
            'category' => 'Abjad',
            'media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'description' => 'Panduan lengkap gerakan tangan untuk huruf A sampai E.',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.lessons.show', $lesson))
            ->assertOk()
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertSee(route('student.lessons.show', $lesson), false);

        $this->actingAs($this->admin)
            ->get(route('admin.lessons.edit', $lesson))
            ->assertOk()
            ->assertSee('lesson-media-preview', false)
            ->assertSee('https://www.youtube.com/watch?v=dQw4w9WgXcQ', false);
    }

    public function test_admin_can_save_a_relative_media_path(): void
    {
        $this->actingAs($this->admin)->post(route('admin.lessons.store'), [
            'title' => 'Mengenal Abjad A',
            'category' => 'Abjad',
            'media_url' => '/images/abjad/A.webp',
            'description' => 'Panduan gerakan tangan untuk huruf A.',
        ])->assertRedirect(route('admin.lessons.index'));

        $this->assertDatabaseHas('lessons', [
            'title' => 'Mengenal Abjad A',
            'media_url' => '/images/abjad/A.webp',
        ]);
    }

    public function test_lesson_validation_rejects_invalid_category(): void
    {
        $this->actingAs($this->admin)->post(route('admin.lessons.store'), [
            'title' => 'Valid title',
            'category' => 'Kategori Tidak Ada',
            'description' => 'Deskripsi yang cukup panjang untuk lolos validasi.',
        ])->assertSessionHasErrors('category');
    }

    public function test_quiz_correct_answer_must_be_one_of_the_options(): void
    {
        $lesson = Lesson::create([
            'title' => 'Angka 1-5',
            'category' => 'Angka',
            'description' => 'Panduan gerakan tangan untuk angka satu sampai lima.',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post(route('admin.quizzes.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'Kuis Angka',
            'questions' => [[
                'question' => 'Berapa jumlah jari untuk angka tiga?',
                'options' => ['Satu', 'Dua', 'Tiga'],
                'correct_answer' => 'Empat',
            ]],
        ])->assertSessionHasErrors('questions.0.correct_answer');

        $this->actingAs($this->admin)->post(route('admin.quizzes.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'Kuis Angka',
            'questions' => [[
                'question' => 'Berapa jumlah jari untuk angka tiga?',
                'options' => ['Satu', 'Dua', 'Tiga'],
                'correct_answer' => 'Tiga',
            ]],
            'is_locked' => true,
            'duration_seconds' => 90,
        ])->assertRedirect(route('admin.quizzes.index'));

        $quiz = Quiz::firstOrFail();
        $this->assertSame('Kuis Angka', $quiz->title);
        $this->assertCount(1, $quiz->questions);
        $this->assertSame('Tiga', $quiz->questions->first()->correct_answer);
        $this->assertTrue($quiz->is_locked);
        $this->assertSame(90, $quiz->duration_seconds);
        $this->assertSame($this->admin->id, $quiz->locked_by);
    }

    public function test_quiz_auto_unlocks_after_unlock_at(): void
    {
        $lesson = Lesson::create([
            'title' => 'Sapaan',
            'category' => 'Kata Sehari-hari',
            'description' => 'Panduan gerakan sapaan sehari-hari.',
            'created_by' => $this->admin->id,
        ]);

        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Kuis Sapaan',
            'is_locked' => true,
            'unlock_at' => now()->subMinute(),
            'is_score_visible' => false,
            'locked_by' => $this->admin->id,
        ]);

        $this->assertTrue($quiz->isAvailable());
        $this->assertSame(1, Quiz::available()->count());

        $quiz->update(['unlock_at' => now()->addDay()]);
        $this->assertFalse($quiz->fresh()->isAvailable());
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $existing = User::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'username' => 'baru',
            'email' => $existing->email,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'role' => 'student',
        ])->assertSessionHasErrors('email');
    }

    public function test_admin_quiz_pages_render_structured_questions(): void
    {
        $lesson = Lesson::create([
            'title' => 'Kata Sapaan',
            'category' => 'Kata Sehari-hari',
            'description' => 'Panduan gerakan sapaan sehari-hari.',
            'created_by' => $this->admin->id,
        ]);

        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Kuis Sapaan',
            'is_locked' => false,
            'is_score_visible' => true,
            'locked_by' => $this->admin->id,
        ]);

        $quiz->questions = [[
            'question' => 'Gerakan tangan untuk sapaan halo adalah?',
            'type' => 'choice',
            'options' => ['Melambaikan tangan', 'Menggenggam tangan'],
            'correct_answer' => 'Melambaikan tangan',
        ]];
        $quiz->save();

        $this->actingAs($this->admin)->get(route('admin.quizzes.index'))->assertOk()->assertSee('Kuis Sapaan');
        $this->actingAs($this->admin)->get(route('admin.quizzes.show', $quiz))->assertOk()->assertSee('Melambaikan tangan');
        $this->actingAs($this->admin)->get(route('admin.quizzes.edit', $quiz))->assertOk()->assertSee('Gerakan tangan untuk sapaan halo adalah?');
        $this->actingAs($this->admin)->get(route('admin.lessons.show', $lesson))->assertOk()->assertSee('Kuis Sapaan');
    }

    public function test_admin_can_store_an_essay_question(): void
    {
        $lesson = Lesson::create([
            'title' => 'Sapaan Dasar',
            'category' => 'Kata Sehari-hari',
            'description' => 'Panduan gerakan sapaan sehari-hari.',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post(route('admin.quizzes.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'Kuis Esai Sapaan',
            'questions' => [[
                'question' => 'Sebutkan satu kata sapaan yang kamu ketahui.',
                'type' => 'essay',
            ]],
        ])->assertRedirect(route('admin.quizzes.index'));

        $question = Quiz::firstOrFail()->questions->first();
        $this->assertSame('essay', $question->type);
        $this->assertNull($question->options);
        $this->assertNull($question->correct_answer);
    }

    public function test_admin_progress_page_renders_attempts(): void
    {
        $lesson = Lesson::create([
            'title' => 'Progres Sapaan',
            'category' => 'Kata Sehari-hari',
            'description' => 'Panduan gerakan sapaan untuk halaman progres.',
            'created_by' => $this->admin->id,
        ]);

        $student = User::factory()->create(['username' => 'siswa-progres']);

        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Kuis Progres',
            'is_locked' => false,
            'locked_by' => $this->admin->id,
        ]);

        $quiz->addAttempt([
            'user_id' => $student->id,
            'answers' => [],
            'choice_correct' => 0,
            'choice_total' => 0,
            'essay_total' => 0,
            'score' => 80,
            'status' => 'graded',
            'graded_by' => $this->admin->id,
            'graded_at' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ]);

        $this->actingAs($this->admin)->get(route('admin.progress.index'))
            ->assertOk()
            ->assertSee('siswa-progres')
            ->assertSee('Kuis Progres');
    }

    public function test_admin_can_manage_word_quest_words(): void
    {
        $this->actingAs($this->admin)->post(route('admin.word-quest.store'), [
            'word' => 'pagi',
            'is_active' => '1',
        ])->assertRedirect(route('admin.word-quest.index'));

        $word = Game::firstOrFail();
        $this->assertSame('PAGI', $word->word);
        $this->assertTrue($word->is_active);

        $this->actingAs($this->admin)->put(route('admin.word-quest.update', $word), [
            'word' => 'baby',
            'is_active' => '0',
        ])->assertRedirect(route('admin.word-quest.index'));

        $this->assertDatabaseHas('games', [
            'id' => $word->id,
            'word' => 'BABY',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)->delete(route('admin.word-quest.destroy', $word))
            ->assertRedirect(route('admin.word-quest.index'));

        $this->assertDatabaseMissing('games', ['id' => $word->id]);
    }

    public function test_word_quest_rejects_duplicates_and_symbols(): void
    {
        Game::create(['word' => 'HALO', 'is_active' => true]);

        $this->actingAs($this->admin)->post(route('admin.word-quest.store'), [
            'word' => 'halo',
        ])->assertSessionHasErrors('word');

        $this->actingAs($this->admin)->post(route('admin.word-quest.store'), [
            'word' => 'HALO!',
        ])->assertSessionHasErrors('word');
    }

    public function test_choice_question_still_requires_options(): void
    {
        $lesson = Lesson::create([
            'title' => 'Sapaan Lanjutan',
            'category' => 'Kata Sehari-hari',
            'description' => 'Panduan gerakan sapaan lanjutan.',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post(route('admin.quizzes.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'Kuis Pilihan',
            'questions' => [[
                'question' => 'Gerakan tangan untuk sapaan halo adalah?',
                'type' => 'choice',
                'correct_answer' => 'Melambaikan tangan',
            ]],
        ])->assertSessionHasErrors('questions.0.options');
    }
}
