@extends('layouts.app')

@section('title', 'Detail Kuis')
@section('heading', 'Detail Kuis')

@section('actions')
    <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="btn btn-primary">Ubah</a>
@endsection

@section('content')
    <section class="card">
        <div class="detail-grid">
            <div>
                <span class="detail-label">Judul</span>
                <p class="detail-value">{{ $quiz->title ?: 'Kuis #'.$quiz->id }}</p>
            </div>
            <div>
                <span class="detail-label">Pelajaran</span>
                <p class="detail-value">{{ $quiz->lesson?->title ?? '—' }}</p>
            </div>
            <div>
                <span class="detail-label">Status</span>
                <p>
                    @if ($quiz->is_locked)
                        <span class="badge badge-locked">Terkunci</span>
                    @else
                        <span class="badge badge-open">Terbuka</span>
                    @endif
                </p>
            </div>
            <div>
                <span class="detail-label">Batas waktu</span>
                <p class="detail-value">{{ $quiz->duration_seconds ? $quiz->duration_seconds.' detik' : 'Tanpa batas' }}</p>
            </div>
            <div>
                <span class="detail-label">Auto-unlock</span>
                <p class="detail-value">{{ $quiz->unlock_at?->format('d M Y, H:i') ?? '—' }}</p>
            </div>
            <div>
                <span class="detail-label">Nilai terlihat</span>
                <p class="detail-value">{{ $quiz->is_score_visible ? 'Ya' : 'Tidak' }}</p>
            </div>
            <div>
                <span class="detail-label">Terakhir diubah oleh</span>
                <p class="detail-value">{{ $quiz->locker?->username ?? '—' }}</p>
            </div>
        </div>

        <div class="prose">
            <h2>Soal ({{ $quiz->questions->count() }})</h2>

            @foreach ($quiz->questions as $index => $question)
                <div class="question-review">
                    <p class="quiz-question">
                        {{ $index + 1 }}. {{ $question->question }}
                        <span class="badge {{ $question->type === 'essay' ? 'badge-open' : '' }}">{{ $question->type === 'essay' ? 'Esai' : 'Pilihan ganda' }}</span>
                    </p>
                    @if ($question->type === 'essay')
                        <p class="detail-value">Kunci: {{ $question->correct_answer }}</p>
                    @else
                        <ul class="answer-list">
                            @foreach ($question->options ?? [] as $option)
                                <li class="{{ $option === $question->correct_answer ? 'is-correct' : '' }}">
                                    {{ $option }}
                                    @if ($option === $question->correct_answer)
                                        <span class="badge badge-open">Kunci</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endsection
