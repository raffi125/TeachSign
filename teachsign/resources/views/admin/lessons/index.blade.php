@extends('layouts.app')

@section('title', 'Pelajaran')
@section('heading', 'Manajemen Pelajaran')

@section('actions')
    <a href="{{ route('admin.lessons.create') }}" class="btn btn-primary">+ Pelajaran Baru</a>
@endsection

@section('content')
    <section class="card">
        <form method="GET" class="toolbar">
            <input type="search" name="search" value="{{ $search }}" class="input" placeholder="Cari judul…">
            <select name="category" class="select">
                <option value="">Semua kategori</option>
                @foreach (\App\Models\Lesson::CATEGORIES as $cat)
                    <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
            <button class="btn btn-secondary">Filter</button>
            @if ($search !== '' || $category !== '')
                <a href="{{ route('admin.lessons.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Media</th>
                        <th>Kuis</th>
                        <th>Dibuat oleh</th>
                        <th class="align-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lessons as $lesson)
                        <tr>
                            <td><strong>{{ $lesson->title }}</strong></td>
                            <td><span class="badge badge-category">{{ $lesson->category }}</span></td>
                            <td>
                                @if ($lesson->media_url)
                                    @php($mediaType = \App\Support\MediaEmbed::parse($lesson->media_url)['type'])
                                    <a href="{{ $lesson->media_url }}" target="_blank" rel="noopener" class="link">
                                        {{ ['youtube' => 'YouTube', 'drive' => 'Drive', 'video' => 'Video', 'image' => 'Gambar', 'link' => 'Tautan'][$mediaType] ?? 'Tautan' }}
                                    </a>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td>{{ $lesson->quizzes_count }}</td>
                            <td class="muted">{{ $lesson->creator?->username ?? '—' }}</td>
                            <td class="align-right">
                                <div class="row-actions">
                                    <a href="{{ route('admin.lessons.show', $lesson) }}" class="btn btn-ghost btn-sm">Lihat</a>
                                    <a href="{{ route('admin.lessons.edit', $lesson) }}" class="btn btn-secondary btn-sm">Ubah</a>
                                    <form method="POST" action="{{ route('admin.lessons.destroy', $lesson) }}"
                                          onsubmit="return confirm('Hapus pelajaran ini beserta kuis & progres terkait?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">Tidak ada pelajaran ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $lessons->links('partials.pagination') }}
    </section>
@endsection
