@extends('layouts.app')

@section('title', 'Pelajaran')
@section('heading', 'Pelajaran')

@section('content')
    <section class="card">
        <form method="GET" class="toolbar">
            <input type="search" name="search" value="{{ $search }}" class="input" placeholder="Cari judul pelajaran…">
            <select name="category" class="select">
                <option value="">Semua kategori</option>
                @foreach (\App\Models\Lesson::CATEGORIES as $cat)
                    <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
            <button class="btn btn-secondary">Filter</button>
            @if ($search !== '' || $category !== '')
                <a href="{{ route('student.lessons.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="course-grid">
            @forelse ($lessons as $lesson)
                @php($score = $progress[$lesson->id]->score ?? null)
                <a href="{{ route('student.lessons.show', $lesson) }}" class="course-card">
                    <div class="course-cover cat-{{ Str::slug($lesson->category) }}">
                        <span class="course-cover-badge">{{ $lesson->category }}</span>
                    </div>
                    <div class="course-body">
                        <h3 class="course-title">{{ $lesson->title }}</h3>
                        <p class="course-desc">{{ Str::limit($lesson->description, 80) }}</p>
                        <div class="course-meta">
                            <span>{{ $lesson->quizzes_count }} kuis terbuka</span>
                        </div>
                    </div>
                    <div class="course-footer">
                        <div class="course-progress">
                            <span class="progress-track">
                                <span class="progress-fill" style="width: {{ $score === null ? 0 : min(100, $score) }}%"></span>
                            </span>
                            <span class="course-progress-label">
                                {{ $score === null ? 'Belum dimulai' : 'Skor '.$score }}
                            </span>
                        </div>
                        @if ($score !== null)
                            <span class="badge badge-score">{{ $score }}</span>
                        @endif
                    </div>
                </a>
            @empty
                <p class="empty">Tidak ada pelajaran ditemukan.</p>
            @endforelse
        </div>

        {{ $lessons->links('partials.pagination') }}
    </section>
@endsection
