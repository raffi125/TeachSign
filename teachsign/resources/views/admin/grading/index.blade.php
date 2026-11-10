@extends('layouts.app')

@section('title', 'Penilaian Esai')
@section('heading', 'Penilaian Esai')

@section('content')
    <section class="card">
        <div class="card-header">
            <h2>Menunggu penilaian ({{ $pending->count() }})</h2>
        </div>

        @forelse ($pending as $attempt)
            @php($questions = $attempt->quiz->questions->keyBy('id'))
            <div class="question-review">
                <p class="quiz-question">
                    {{ $attempt->user?->username ?? '—' }} · {{ $attempt->quiz->title }}
                    <span class="badge badge-locked">{{ $attempt->lesson?->title }}</span>
                </p>
                <p class="muted">
                    Dikirim {{ $attempt->created_at->format('d M Y, H:i') }} ·
                    Pilihan ganda benar {{ $attempt->choice_correct }} dari {{ $attempt->choice_total }} ·
                    {{ $attempt->essay_total }} soal esai
                </p>

                @foreach ($attempt->answers as $questionId => $answer)
                    @php($question = $questions->get((int) $questionId))
                    @if ($question && $question->type === 'essay')
                        <div class="field field-full">
                            <span class="detail-label">{{ $question->question }}</span>
                            <p class="detail-value">{{ $answer }}</p>
                        </div>
                    @endif
                @endforeach

                <form method="POST" action="{{ route('admin.grading.update', [$attempt->quiz, $attempt->attempt_index]) }}" class="toolbar">
                    @csrf
                    @method('PATCH')
                    <div class="field">
                        <label for="score-{{ $attempt->id }}">Nilai akhir (0-100)</label>
                        <input id="score-{{ $attempt->id }}" type="number" name="score" class="input"
                               min="0" max="100" required placeholder="Contoh: 85">
                    </div>
                    <button class="btn btn-primary">Simpan Nilai</button>
                </form>
            </div>
        @empty
            <p class="empty">Tidak ada jawaban esai yang menunggu penilaian.</p>
        @endforelse
    </section>

    <section class="card">
        <div class="card-header">
            <h2>Sudah dinilai</h2>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>Kuis</th>
                        <th>Pelajaran</th>
                        <th>Nilai</th>
                        <th>Dinilai oleh</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($graded as $attempt)
                        <tr>
                            <td>{{ $attempt->user?->username ?? '—' }}</td>
                            <td>{{ $attempt->quiz->title }}</td>
                            <td>{{ $attempt->lesson?->title ?? '—' }}</td>
                            <td><span class="badge badge-score">{{ $attempt->score }}</span></td>
                            <td>{{ $attempt->grader?->username ?? '—' }}</td>
                            <td class="muted">{{ $attempt->graded_at?->format('d M Y, H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">Belum ada jawaban esai yang dinilai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
