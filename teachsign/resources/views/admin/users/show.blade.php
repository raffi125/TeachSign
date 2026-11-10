@extends('layouts.app')

@section('title', 'Detail Pengguna')
@section('heading', 'Detail Pengguna')

@section('actions')
    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">Ubah</a>
@endsection

@section('content')
    <section class="card">
        <div class="detail-grid">
            <div>
                <span class="detail-label">Username</span>
                <p class="detail-value">{{ $user->username }}</p>
            </div>
            <div>
                <span class="detail-label">Email</span>
                <p class="detail-value">{{ $user->email }}</p>
            </div>
            <div>
                <span class="detail-label">Peran</span>
                <p><span class="badge badge-{{ $user->role }}">{{ $user->isAdmin() ? 'Admin' : 'Siswa' }}</span></p>
            </div>
            <div>
                <span class="detail-label">Bergabung</span>
                <p class="detail-value">{{ $user->created_at->format('d M Y, H:i') }}</p>
            </div>
            <div>
                <span class="detail-label">Pelajaran dibuat</span>
                <p class="detail-value">{{ $user->lessons_count }}</p>
            </div>
            <div>
                <span class="detail-label">Kuis dinilai</span>
                <p class="detail-value">{{ $user->quiz_attempts_count }}</p>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card-header"><h2>Riwayat Kuis</h2></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Pelajaran</th><th>Skor</th><th>Selesai</th></tr>
                </thead>
                <tbody>
                    @forelse ($progress as $item)
                        <tr>
                            <td>{{ $item->lesson->title }}</td>
                            <td><span class="badge badge-score">{{ $item->score }}</span></td>
                            <td class="muted">{{ $item->completed_at->format('d M Y, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty">Belum ada progres.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
