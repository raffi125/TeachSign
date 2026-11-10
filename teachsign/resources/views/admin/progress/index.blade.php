@extends('layouts.app')

@section('title', 'Progres')
@section('heading', 'Progres Kuis')

@section('content')
    <section class="card">
        <form method="GET" class="toolbar">
            <select name="user_id" class="select">
                <option value="">Semua siswa</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}" @selected($userId === $student->id)>{{ $student->username }}</option>
                @endforeach
            </select>
            <select name="lesson_id" class="select">
                <option value="">Semua pelajaran</option>
                @foreach ($lessons as $lesson)
                    <option value="{{ $lesson->id }}" @selected($lessonId === $lesson->id)>{{ $lesson->title }}</option>
                @endforeach
            </select>
            <select name="status" class="select">
                <option value="">Semua status</option>
                <option value="graded" @selected($status === 'graded')>Sudah dinilai</option>
                <option value="pending" @selected($status === 'pending')>Menunggu nilai</option>
            </select>
            <button class="btn btn-secondary">Filter</button>
            @if ($userId > 0 || $lessonId > 0 || $status !== '')
                <a href="{{ route('admin.progress.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>Pelajaran</th>
                        <th>Kuis</th>
                        <th>Skor</th>
                        <th>Status</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $attempt)
                        <tr>
                            <td>{{ $attempt->user?->username ?? '—' }}</td>
                            <td>{{ $attempt->lesson?->title ?? '—' }}</td>
                            <td>{{ $attempt->quiz?->title ?? '—' }}</td>
                            <td>
                                @if ($attempt->score === null)
                                    <span class="muted">—</span>
                                @else
                                    <span class="badge badge-score">{{ $attempt->score }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($attempt->status === 'graded')
                                    <span class="badge badge-open">Sudah dinilai</span>
                                @else
                                    <a href="{{ route('admin.grading.index') }}" class="badge badge-score">Menunggu nilai</a>
                                @endif
                            </td>
                            <td class="muted">{{ $attempt->completed_at->format('d M Y, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">Belum ada kuis dikerjakan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $attempts->links('partials.pagination') }}
    </section>
@endsection
