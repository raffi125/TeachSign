<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'admin',
            'email' => 'admin@teachsign.test',
            'password_hash' => 'Password123',
        ]);

        $students = collect([
            ['username' => 'siti', 'email' => 'siti@teachsign.test'],
            ['username' => 'budi', 'email' => 'budi@teachsign.test'],
            ['username' => 'dewi', 'email' => 'dewi@teachsign.test'],
        ])->map(fn (array $data) => User::factory()->create($data));

        $lessons = collect([
            [
                'title' => 'Mengenal Abjad A–E',
                'category' => 'Abjad',
                'media_url' => '/images/abjad/A.webp',
                'description' => "Bentuk tangan mengeja huruf A sampai E.\nA: tangan menggenggam dengan ibu jari di samping.\nB: telapak terbuka, jari rapat.\nC: jari membentuk lengkung seperti huruf C.\nD: telunjuk menunjuk ke atas, jari lain melingkar.\nE: jari-jari terlipat menyentuh ibu jari.",
            ],
            [
                'title' => 'Angka 1–5',
                'category' => 'Angka',
                'media_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                'description' => "Variasi jumlah jari untuk angka satu sampai lima.\n1: telunjuk berdiri.\n2: telunjuk dan jari tengah terbuka.\n3: ibu jari, telunjuk, dan jari tengah terbuka.\n4: empat jari terbuka tanpa ibu jari.\n5: seluruh jari terbuka.",
            ],
            [
                'title' => 'Sapaan Sehari-hari',
                'category' => 'Kata Sehari-hari',
                'media_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                'description' => 'Gerakan untuk halo, terima kasih, dan selamat pagi. Awali gerakan dari dada, arahkan telapak ke depan, lalu gerakkan menjauh sambil tersenyum.',
            ],
        ])->map(fn (array $data) => Lesson::create($data + ['created_by' => $admin->id]));

        $quizDefinitions = [
            [
                'lesson' => $lessons[0],
                'title' => 'Kuis Huruf A-B',
                'is_locked' => false,
                'is_score_visible' => true,
                'duration_seconds' => 60,
                'questions' => [
                    [
                        'question' => 'Bagaimana bentuk tangan untuk huruf A?',
                        'options' => ['Tangan menggenggam dengan ibu jari di samping', 'Telapak terbuka lebar', 'Telunjuk menunjuk ke atas', 'Jari membentuk lengkung'],
                        'correct_answer' => 'Tangan menggenggam dengan ibu jari di samping',
                        'media_url' => '/images/abjad/A.webp',
                    ],
                    [
                        'question' => 'Bagaimana bentuk tangan untuk huruf B?',
                        'options' => ['Telapak terbuka dengan empat jari rapat', 'Tangan menggenggam', 'Telunjuk menunjuk ke atas', 'Ibu jari menyentuh dagu'],
                        'correct_answer' => 'Telapak terbuka dengan empat jari rapat',
                        'media_url' => '/images/abjad/B.webp',
                    ],
                ],
            ],
            [
                'lesson' => $lessons[1],
                'title' => 'Kuis Angka 1-3',
                'is_locked' => true,
                'unlock_at' => now()->addDays(3),
                'is_score_visible' => true,
                'duration_seconds' => 120,
                'questions' => [
                    [
                        'question' => 'Berapa jari yang ditunjukkan untuk angka satu?',
                        'options' => ['Satu jari telunjuk', 'Dua jari', 'Tiga jari', 'Lima jari'],
                        'correct_answer' => 'Satu jari telunjuk',
                        'media_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                    ],
                    [
                        'question' => 'Berapa jari yang ditunjukkan untuk angka dua?',
                        'options' => ['Satu jari', 'Dua jari', 'Tiga jari', 'Empat jari'],
                        'correct_answer' => 'Dua jari',
                    ],
                ],
            ],
            [
                'lesson' => $lessons[2],
                'title' => 'Kuis Sapaan',
                'is_locked' => false,
                'is_score_visible' => true,
                'duration_seconds' => 60,
                'questions' => [
                    [
                        'question' => 'Gerakan tangan untuk sapaan halo adalah?',
                        'options' => ['Melambaikan tangan terbuka', 'Menggenggam tangan', 'Menunjuk ke bawah', 'Menyilangkan tangan'],
                        'correct_answer' => 'Melambaikan tangan terbuka',
                        'media_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                    ],
                    [
                        'question' => 'Sebutkan satu kata sapaan yang kamu ketahui.',
                        'type' => 'essay',
                        'options' => null,
                    ],
                ],
            ],
        ];

        foreach ($quizDefinitions as $definition) {
            $questions = collect($definition['questions'])->map(fn (array $question) => [
                'question' => $question['question'],
                'type' => $question['type'] ?? 'choice',
                'options' => $question['options'] ?? null,
                'correct_answer' => $question['correct_answer'] ?? null,
                'media_url' => $question['media_url'] ?? null,
            ])->all();

            Quiz::create([
                'lesson_id' => $definition['lesson']->id,
                'title' => $definition['title'],
                'questions' => $questions,
                'attempts' => [],
                'is_locked' => $definition['is_locked'],
                'unlock_at' => $definition['unlock_at'] ?? null,
                'is_score_visible' => $definition['is_score_visible'],
                'duration_seconds' => $definition['duration_seconds'] ?? null,
                'locked_by' => $admin->id,
            ]);
        }

        $quiz = $lessons[0]->quizzes()->first();

        if ($quiz !== null) {
            $quiz->attempts = collect([
                [$students[0], 90, now()->subDays(2)],
                [$students[1], 75, now()->subDay()],
            ])->map(fn (array $row) => [
                'user_id' => $row[0]->id,
                'answers' => [],
                'choice_correct' => 0,
                'choice_total' => 0,
                'essay_total' => 0,
                'score' => $row[1],
                'status' => 'graded',
                'graded_by' => null,
                'graded_at' => $row[2]->toIso8601String(),
                'created_at' => $row[2]->toIso8601String(),
            ])->all();

            $quiz->save();
        }

        $this->call(WordQuestWordSeeder::class);
    }
}
