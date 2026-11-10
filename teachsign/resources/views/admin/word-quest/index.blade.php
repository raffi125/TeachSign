@extends('layouts.app')

@section('title', 'Mini Games')
@section('heading', 'Manajemen Mini Games')

@section('actions')
    <a href="{{ route('admin.word-quest.create') }}" class="btn btn-primary">+ Kata Baru</a>
@endsection

@section('content')
    <section class="card">
        <form method="GET" class="toolbar">
            <input type="search" name="search" value="{{ $search }}" class="input" placeholder="Cari kata…">
            <select name="status" class="select">
                <option value="">Semua status</option>
                <option value="active" @selected($status === 'active')>Aktif</option>
                <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
            </select>
            <button class="btn btn-secondary">Filter</button>
            @if ($search !== '' || $status !== '')
                <a href="{{ route('admin.word-quest.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kata</th>
                        <th>Huruf</th>
                        <th>Status</th>
                        <th class="align-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($words as $word)
                        <tr>
                            <td><strong>{{ $word->word }}</strong></td>
                            <td class="muted">{{ strlen(str_replace(' ', '', $word->word)) }}</td>
                            <td>
                                <span class="badge {{ $word->is_active ? 'badge-open' : 'badge-locked' }}">
                                    {{ $word->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="align-right">
                                <div class="row-actions">
                                    <a href="{{ route('admin.word-quest.edit', $word) }}" class="btn btn-secondary btn-sm">Ubah</a>
                                    <form method="POST" action="{{ route('admin.word-quest.destroy', $word) }}"
                                          onsubmit="return confirm('Hapus kata ini dari Mini Games?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">Belum ada kata Mini Games.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $words->links('partials.pagination') }}
    </section>
@endsection
