@extends('layouts.app')

@section('title', 'Pengguna')
@section('heading', 'Manajemen Pengguna')

@section('actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">+ Pengguna Baru</a>
@endsection

@section('content')
    <section class="card">
        <form method="GET" class="toolbar">
            <input type="search" name="search" value="{{ $search }}" class="input" placeholder="Cari username atau email…">
            <button class="btn btn-secondary">Cari</button>
            @if ($search !== '')
                <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Pelajaran</th>
                        <th>Kuis dinilai</th>
                        <th class="align-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="cell-user">
                                    <span class="avatar avatar-sm">{{ strtoupper(substr($user->username, 0, 1)) }}</span>
                                    {{ $user->username }}
                                </div>
                            </td>
                            <td class="muted">{{ $user->email }}</td>
                            <td><span class="badge badge-{{ $user->role }}">{{ $user->isAdmin() ? 'Admin' : 'Siswa' }}</span></td>
                            <td>{{ $user->lessons_count }}</td>
                            <td>{{ $user->quiz_attempts_count }}</td>
                            <td class="align-right">
                                <div class="row-actions">
                                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-ghost btn-sm">Lihat</a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary btn-sm">Ubah</a>
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                          onsubmit="return confirm('Hapus pengguna {{ $user->username }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">Tidak ada pengguna ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links('partials.pagination') }}
    </section>
@endsection
