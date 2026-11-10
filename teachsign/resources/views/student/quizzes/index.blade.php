@extends('layouts.app')

@section('title', 'Kuis')
@section('heading', 'Kuis')

@section('content')
    <section class="card">
        <form method="GET" class="toolbar">
            <select name="lesson_id" class="select">
                <option value="">Semua pelajaran</option>
                @foreach ($lessons as $lesson)
                    <option value="{{ $lesson->id }}" @selected($lessonId === $lesson->id)>{{ $lesson->title }}</option>
                @endforeach
            </select>
            <select name="status" class="select">
                <option value="">Semua status</option>
                <option value="open" @selected($status === 'open')>Terbuka</option>
                <option value="locked" @selected($status === 'locked')>Terkunci</option>
            </select>
            <button class="btn btn-secondary">Filter</button>
            @if ($lessonId > 0 || $status !== '')
                <a href="{{ route('student.quizzes.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="quiz-grid">
            @forelse ($quizzes as $quiz)
                @if ($quiz->isAvailable())
                    <a href="{{ route('student.quizzes.show', [$quiz->lesson, $quiz]) }}" class="quiz-card">
                        <div class="quiz-card-top">
                            <span class="badge badge-category">{{ $quiz->lesson?->category ?? 'Kuis' }}</span>
                            <span class="badge badge-open">Terbuka</span>
                        </div>
                        <h3 class="quiz-question">{{ $quiz->title ?: 'Kuis #'.$quiz->id }}</h3>
                        <p class="quiz-lesson">{{ $quiz->lesson?->title ?? '—' }}</p>
                        <div class="quiz-card-footer">
                            <span class="tile-meta">
                                {{ $quiz->questions_count }} soal ·
                                {{ $quiz->duration_seconds ? $quiz->duration_seconds.' dtk' : 'tanpa batas' }}
                            </span>
                            <span class="quiz-cta">Kerjakan ›</span>
                        </div>
                    </a>
                @else
                    <article class="quiz-card is-locked">
                        <div class="quiz-card-top">
                            <span class="badge badge-category">{{ $quiz->lesson?->category ?? 'Kuis' }}</span>
                            <span class="badge badge-locked">Terkunci</span>
                        </div>
                        <h3 class="quiz-question">{{ $quiz->title ?: 'Kuis #'.$quiz->id }}</h3>
                        <p class="quiz-lesson">{{ $quiz->lesson?->title ?? '—' }}</p>
                        <div class="quiz-card-footer">
                            <span class="tile-meta">
                                {{ $quiz->questions_count }} soal ·
                                {{ $quiz->duration_seconds ? $quiz->duration_seconds.' dtk' : 'tanpa batas' }}
                            </span>
                            <span class="muted">
                                {{ $quiz->unlock_at ? 'Buka '.$quiz->unlock_at->format('d M H:i') : 'Menunggu admin' }}
                            </span>
                        </div>
                    </article>
                @endif
            @empty
                <p class="empty">Tidak ada kuis ditemukan.</p>
            @endforelse
        </div>

        {{ $quizzes->links('partials.pagination') }}
    </section>
@endsection
