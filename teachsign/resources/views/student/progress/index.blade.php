@extends('layouts.app')

@section('title', 'Progres Saya')
@section('heading', 'Progres Saya')

@section('content')
    <div class="stat-grid">
        <div class="stat-card">
            <span class="stat-label">Pelajaran Selesai</span>
            <span class="stat-value">{{ $stats['completed'] }}</span>
            <span class="stat-sub">dari {{ $stats['total_lessons'] }} pelajaran</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Rata-rata Skor</span>
            <span class="stat-value">{{ $stats['average'] }}</span>
            <span class="stat-sub">semua pelajaran</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Skor Terbaik</span>
            <span class="stat-value">{{ $stats['best'] }}</span>
            <span class="stat-sub">maksimal 100</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Belum Dimainkan</span>
            <span class="stat-value">{{ max(0, $stats['total_lessons'] - $stats['completed']) }}</span>
            <span class="stat-sub">ayo coba lagi</span>
        </div>
    </div>

    <section class="card">
        <div class="card-header"><h2>Riwayat Progres</h2></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Pelajaran</th><th>Skor</th><th>Progres</th><th>Selesai</th></tr>
                </thead>
                <tbody>
                    @forelse ($progress as $item)
                        <tr>
                            <td>
                                <a href="{{ route('student.lessons.show', $item->lesson) }}" class="link">
                                    {{ $item->lesson->title }}
                                </a>
                            </td>
                            <td><span class="badge badge-score">{{ $item->score }}</span></td>
                            <td>
                                <div class="score-bar">
                                    <span style="width: {{ min(100, $item->score) }}%"></span>
                                </div>
                            </td>
                            <td class="muted">{{ $item->completed_at->format('d M Y, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">Belum ada progres. Mulai pelajaran pertamamu!</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
