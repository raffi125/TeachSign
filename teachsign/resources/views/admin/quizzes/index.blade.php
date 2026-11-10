@extends('layouts.app')

@section('title', 'Kuis')
@section('heading', 'Manajemen Kuis')

@section('actions')
    <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary">+ Kuis Baru</a>
@endsection

@section('content')
    <section class="card">
        <form method="GET" class="toolbar">
            <select name="lesson_id" class="select">
                <option value="">Semua pelajaran</option>
                @foreach ($lessons as $lesson)
                    <option value="{{ $lesson->id }}" @selected($lessonId === $lesson->id)>{{ $lesson->title }}</option>
                @endforeach
            </select>
            <button class="btn btn-secondary">Filter</button>
            @if ($lessonId > 0)
                <a href="{{ route('admin.quizzes.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kuis</th>
                        <th>Pelajaran</th>
                        <th>Status</th>
                        <th>Durasi</th>
                        <th>Nilai</th>
                        <th>Diubah oleh</th>
                        <th class="align-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quizzes as $quiz)
                        <tr>
                            <td>
                                <strong>{{ $quiz->title ?: 'Kuis #'.$quiz->id }}</strong>
                                <span class="muted"> · {{ $quiz->questions_count }} soal</span>
                            </td>
                            <td class="muted">{{ $quiz->lesson?->title ?? '—' }}</td>
                            <td>
                                @if ($quiz->is_locked)
                                    <span class="badge badge-locked">Terkunci</span>
                                    @if ($quiz->unlock_at)
                                        <span class="badge badge-scheduled">{{ $quiz->unlock_at->format('d M H:i') }}</span>
                                    @endif
                                @else
                                    <span class="badge badge-open">Terbuka</span>
                                @endif
                            </td>
                            <td>{{ $quiz->duration_seconds ? $quiz->duration_seconds.' dtk' : 'Tanpa batas' }}</td>
                            <td>{{ $quiz->is_score_visible ? 'Terlihat' : 'Disembunyikan' }}</td>
                            <td class="muted">{{ $quiz->locker?->username ?? '—' }}</td>
                            <td class="align-right">
                                <div class="row-actions">
                                    <form method="POST" action="{{ route('admin.quizzes.toggle-lock', $quiz) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-secondary btn-sm">{{ $quiz->is_locked ? 'Buka' : 'Kunci' }}</button>
                                    </form>
                                    <a href="{{ route('admin.quizzes.show', $quiz) }}" class="btn btn-ghost btn-sm">Lihat</a>
                                    <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="btn btn-secondary btn-sm">Ubah</a>
                                    <form method="POST" action="{{ route('admin.quizzes.destroy', $quiz) }}"
                                          onsubmit="return confirm('Hapus kuis ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">Tidak ada kuis ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $quizzes->links('partials.pagination') }}
    </section>
@endsection
