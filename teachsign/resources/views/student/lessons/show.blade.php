@extends('layouts.app')

@section('title', $lesson->title)
@section('heading', $lesson->title)

@section('actions')
    <a href="{{ route('student.lessons.index') }}" class="btn btn-ghost">‹ Kembali</a>
@endsection

@section('content')
    @if (session('quiz_result'))
        @php($result = session('quiz_result'))
        @if (! empty($result['pending']))
            <div class="alert alert-warning">
                <strong>Jawaban terkirim.</strong>
                Pilihan ganda benar {{ $result['correct_count'] }} dari {{ $result['choice_total'] }} soal.
                {{ $result['essay_count'] }} jawaban esai menunggu penilaian admin.
                <div class="result-meta">Nilai masuk ke progres setelah dinilai.</div>
            </div>
        @else
            <div class="alert {{ $result['correct'] ? 'alert-success' : 'alert-warning' }}">
                @if ($result['visible'])
                    @if ($result['correct'])
                        <strong>Semua jawaban benar!</strong> Skor kuis: {{ $result['score'] }}.
                    @else
                        <strong>Belum semua tepat.</strong> Benar {{ $result['correct_count'] }} dari {{ $result['total'] }} soal. Skor kuis: {{ $result['score'] }}.
                    @endif
                @else
                    <strong>Jawaban terkirim.</strong> Nilai kuis disembunyikan oleh admin.
                @endif
                <div class="result-meta">Skor terbaik pelajaran ini: {{ $progress?->score ?? 0 }}</div>
            </div>
        @endif
    @endif

    <section class="card lesson-hero">
        <div class="lesson-hero-body">
            <div class="lesson-hero-top">
                <span class="badge badge-category">{{ $lesson->category }}</span>
                @if ($progress)
                    <span class="badge badge-score">Skor terbaik {{ $progress->score }}</span>
                @endif
            </div>

            @include('partials.lesson-media', ['lesson' => $lesson])

            <div class="prose">
                <h2>Panduan gerakan</h2>
                <p>{{ $lesson->description }}</p>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <h2>Kuis</h2>
            <span class="muted">{{ $availableQuizzes->count() }} dari {{ $lesson->quizzes->count() }} terbuka</span>
        </div>

        <div class="quiz-list">
            @forelse ($lesson->quizzes as $quiz)
                <div class="quiz-item {{ $quiz->isAvailable() ? '' : 'is-locked' }}">
                    <div class="quiz-item-main">
                        <p class="quiz-question">{{ $quiz->title ?: 'Kuis #'.$quiz->id }}</p>
                        <p class="muted quiz-meta">
                            {{ $quiz->questions_count }} soal
                            @if ($quiz->is_score_visible)
                                · nilai ditampilkan
                            @else
                                · nilai disembunyikan
                            @endif
                        </p>
                    </div>

                    @if ($quiz->isAvailable())
                        <a href="{{ route('student.quizzes.show', [$lesson, $quiz]) }}" class="btn btn-primary btn-sm">Kerjakan</a>
                    @else
                        <div class="quiz-lock">
                            <span class="badge badge-locked">Terkunci</span>
                            @if ($quiz->unlock_at)
                                <span class="muted">Buka {{ $quiz->unlock_at->format('d M Y, H:i') }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <p class="empty">Belum ada kuis untuk pelajaran ini.</p>
            @endforelse
        </div>
    </section>

    <section class="card game-cta-card">
        <div>
            <h2>Mini-Game: Sign Rush, Word Quest, Flip Flap</h2>
            <p class="muted">Tap emoji sebelum 3 detik habis, ketik jawaban dari gambar, atau balik kartu untuk mencocokkan pasangan.</p>
        </div>
        <a href="{{ route('student.games.index') }}" class="btn btn-primary">Buka Games</a>
    </section>
@endsection
