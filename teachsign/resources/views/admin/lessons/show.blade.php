@extends('layouts.app')

@section('title', 'Detail Pelajaran')
@section('heading', $lesson->title)

@section('actions')
    <a href="{{ route('student.lessons.show', $lesson) }}" class="btn btn-ghost" target="_blank" rel="noopener">Pratinjau siswa ↗</a>
    <a href="{{ route('admin.quizzes.create', ['lesson_id' => $lesson->id]) }}" class="btn btn-primary">+ Kuis</a>
    <a href="{{ route('admin.lessons.edit', $lesson) }}" class="btn btn-secondary">Ubah</a>
@endsection

@section('content')
    <section class="card">
        <div class="detail-grid">
            <div>
                <span class="detail-label">Kategori</span>
                <p><span class="badge badge-category">{{ $lesson->category }}</span></p>
            </div>
            <div>
                <span class="detail-label">Dibuat oleh</span>
                <p class="detail-value">{{ $lesson->creator?->username ?? '—' }}</p>
            </div>
            <div>
                <span class="detail-label">Dibuat pada</span>
                <p class="detail-value">{{ $lesson->created_at->format('d M Y, H:i') }}</p>
            </div>
            <div>
                <span class="detail-label">Media</span>
                <p class="detail-value">
                    @if ($lesson->media_url)
                        <a href="{{ $lesson->media_url }}" target="_blank" rel="noopener" class="link">Buka media</a>
                    @else
                        —
                    @endif
                </p>
            </div>
        </div>

        @if ($lesson->media_url)
            <div class="lesson-preview">
                <span class="detail-label">Pratinjau media</span>
                @include('partials.lesson-media', ['lesson' => $lesson])
            </div>
        @endif

        <div class="prose">
            <h2>Panduan gerakan</h2>
            <p>{{ $lesson->description }}</p>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <h2>Kuis ({{ $lesson->quizzes->count() }})</h2>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Pertanyaan</th><th>Status</th><th>Nilai terlihat</th><th>Kunci oleh</th><th class="align-right">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($lesson->quizzes as $quiz)
                        <tr>
                            <td>{{ Str::limit($quiz->title ?: 'Kuis #'.$quiz->id, 70) }}</td>
                            <td>
                                @if ($quiz->is_locked)
                                    <span class="badge badge-locked">Terkunci</span>
                                @else
                                    <span class="badge badge-open">Terbuka</span>
                                @endif
                            </td>
                            <td>{{ $quiz->is_score_visible ? 'Ya' : 'Tidak' }}</td>
                            <td class="muted">{{ $quiz->locker?->username ?? '—' }}</td>
                            <td class="align-right">
                                <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="btn btn-secondary btn-sm">Ubah</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">Belum ada kuis untuk pelajaran ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
