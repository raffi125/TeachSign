@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@if (auth()->user()->isAdmin())
    @section('actions')
        <a href="{{ route('admin.lessons.create') }}" class="btn btn-primary">+ Pelajaran Baru</a>
    @endsection

    @section('content')
        <div class="stat-grid">
            <div class="stat-card">
                <span class="stat-label">Total Pengguna</span>
                <span class="stat-value">{{ $stats['users'] }}</span>
                <span class="stat-sub">{{ $stats['students'] }} siswa</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Pelajaran</span>
                <span class="stat-value">{{ $stats['lessons'] }}</span>
                <span class="stat-sub">materi tersedia</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Kuis</span>
                <span class="stat-value">{{ $stats['quizzes'] }}</span>
                <span class="stat-sub">{{ $stats['locked_quizzes'] }} terkunci</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Kuis Dikerjakan</span>
                <span class="stat-value">{{ $stats['graded_attempts'] }}</span>
                <span class="stat-sub">{{ $stats['pending_attempts'] }} menunggu nilai</span>
            </div>
        </div>

        <section class="card">
            <div class="card-header">
                <h2>Progres per Pelajaran</h2>
                <span class="muted">6 pelajaran teratas</span>
            </div>
            @if ($lessonProgress->isEmpty())
                <p class="empty">Belum ada progres tercatat.</p>
            @else
                <div class="chart-wrap">
                    <canvas id="adminLessonChart"></canvas>
                </div>
            @endif
        </section>

        <section class="card">
            <div class="card-header">
                <h2>Progres Terbaru</h2>
                <a href="{{ route('admin.progress.index') }}" class="link">Lihat semua</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Pelajaran</th>
                            <th>Skor</th>
                            <th>Selesai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentProgress as $item)
                            <tr>
                                <td>{{ $item->user->username }}</td>
                                <td>{{ $item->lesson->title }}</td>
                                <td><span class="badge badge-score">{{ $item->score }}</span></td>
                                <td class="muted">{{ $item->completed_at->format('d M Y, H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty">Belum ada progres tercatat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endsection

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            const adminChartEl = document.getElementById('adminLessonChart');
            if (adminChartEl) {
                new Chart(adminChartEl, {
                    type: 'bar',
                    data: {
                        labels: @json($lessonProgress->pluck('title')),
                        datasets: [{
                            label: 'Sesi selesai',
                            data: @json($lessonProgress->pluck('total')),
                            backgroundColor: 'rgba(184, 68, 42, 0.75)',
                            borderRadius: 6,
                            maxBarThickness: 42,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#344054', font: { weight: '600' } },
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0, color: '#667085' },
                                grid: { color: '#eef2f6' },
                            },
                        },
                    },
                });
            }
        </script>
    @endpush
@else
    @section('content')
        <section class="info-hero">
            <div>
                <h2>Halo, {{ auth()->user()->username }}</h2>
                <p class="info-hero-sub">Lanjutkan materi, kerjakan kuis, atau latihan di mini-game.</p>
            </div>
            <div class="hero-actions">
                <a href="{{ route('student.lessons.index') }}" class="btn btn-primary">Mulai Belajar</a>
                <a href="{{ route('student.games.index') }}" class="btn btn-hero-ghost">Main Game</a>
            </div>
        </section>

        <div class="stat-grid">
            <div class="stat-card">
                <span class="stat-label">Total Pelajaran</span>
                <span class="stat-value">{{ $stats['lessons'] }}</span>
                <span class="stat-sub">materi bahasa isyarat</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Selesai</span>
                <span class="stat-value">{{ $stats['completed'] }}</span>
                <span class="stat-sub">{{ $stats['remaining'] }} tersisa</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Rata-rata Skor</span>
                <span class="stat-value">{{ $stats['average'] }}</span>
                <span class="stat-sub">semua pelajaran</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Skor Terbaik</span>
                <span class="stat-value">{{ $stats['best'] }}</span>
                <span class="stat-sub">dari 100</span>
            </div>
        </div>

        <section class="card">
            <div class="card-header">
                <h2>Lanjutkan Belajar</h2>
                <a href="{{ route('student.lessons.index') }}" class="link">Semua pelajaran</a>
            </div>
            <div class="course-grid course-grid-compact">
                @forelse ($continueLessons as $lesson)
                    <a href="{{ route('student.lessons.show', $lesson) }}" class="course-card course-card-compact">
                        <div class="course-cover cat-{{ Str::slug($lesson->category) }}">
                            <span class="course-cover-badge">{{ $lesson->category }}</span>
                            
                        </div>
                        <div class="course-body">
                            <h3 class="course-title">{{ $lesson->title }}</h3>
                            <p class="course-desc">{{ $lesson->quizzes_count }} kuis terbuka</p>
                        </div>
                        <div class="course-footer">
                            <span class="course-progress-label">Belum dimulai</span>
                        </div>
                    </a>
                @empty
                    <p class="empty">Semua pelajaran sudah kamu selesaikan.</p>
                @endforelse
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2>Perkembangan Skor</h2>
                <a href="{{ route('student.progress.index') }}" class="link">Detail progres</a>
            </div>
            @if ($chartData->isEmpty())
                <p class="empty">Belum ada skor. Selesaikan kuis atau mini-game untuk melihat grafik.</p>
            @else
                <div class="chart-wrap">
                    <canvas id="studentScoreChart"></canvas>
                </div>
            @endif
        </section>

        <div class="split-grid">
            <section class="card">
                <div class="card-header">
                    <h2>Aktivitas Terakhir</h2>
                    <a href="{{ route('student.progress.index') }}" class="link">Progres saya</a>
                </div>
                <div class="mini-list">
                    @forelse ($recentProgress as $item)
                        <div class="mini-list-item is-static">
                            <div class="mini-list-body">
                                <strong>{{ $item->lesson->title }}</strong>
                                <span class="muted">{{ $item->completed_at->format('d M Y, H:i') }}</span>
                            </div>
                            <span class="badge badge-score">{{ $item->score }}</span>
                        </div>
                    @empty
                        <p class="empty">Belum ada aktivitas.</p>
                    @endforelse
                </div>
            </section>

            <div class="stack">
                <section class="card">
                    <div class="card-header">
                        <h2>Kuis Terbuka</h2>
                        <a href="{{ route('student.quizzes.index') }}" class="link">Lihat kuis</a>
                    </div>
                    <p class="info-highlight">{{ $availableQuizCount }} kuis siap dikerjakan</p>
                    <p class="muted">Kuis yang terkunci akan terbuka sesuai jadwal dari admin.</p>
                </section>

                <section class="card">
                    <div class="card-header"><h2>Kategori Materi</h2></div>
                    <div class="category-chips">
                        @forelse ($categories as $category)
                            <a href="{{ route('student.lessons.index', ['category' => $category->category]) }}" class="category-chip">
                                {{ $category->category }}
                                <span>{{ $category->total }}</span>
                            </a>
                        @empty
                            <p class="empty">Belum ada kategori.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            const studentChartEl = document.getElementById('studentScoreChart');
            if (studentChartEl) {
                const rows = @json($chartData);
                new Chart(studentChartEl, {
                    type: 'bar',
                    data: {
                        labels: rows.map((row) => row.label),
                        datasets: [{
                            label: 'Skor',
                            data: rows.map((row) => row.score),
                            backgroundColor: 'rgba(184, 68, 42, 0.75)',
                            borderRadius: 6,
                            barThickness: 18,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                beginAtZero: true,
                                suggestedMax: 100,
                                ticks: { color: '#667085' },
                                grid: { color: '#eef2f6' },
                            },
                            y: {
                                grid: { display: false },
                                ticks: { color: '#344054', font: { weight: '600' } },
                            },
                        },
                    },
                });
            }
        </script>
    @endpush
@endif
