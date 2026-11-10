@extends('layouts.app')

@section('title', 'Games')
@section('heading', 'Arcade TeachSign')

@section('content')
    @if (session('game_result'))
        @php($result = session('game_result'))
        <div class="alert alert-success">
            <strong>{{ $result['mode'] }} selesai!</strong> Skor {{ $result['score'] }}.
            {{ $result['hits'] }} tepat, {{ $result['misses'] }} meleset.
        </div>
    @endif

    <section class="card">
        <div class="card-header">
            <h2>Pilih Game</h2>
            <span class="muted">Langsung main, tanpa pilih pelajaran</span>
        </div>

        <div class="arcade-grid">
            @foreach ($modes as $key => $mode)
                @php($best = $scores[$key] ?? null)
                <a href="{{ route('student.game.show', ['mode' => $key]) }}" class="arcade-card">
                    <span class="arcade-body">
                        <strong>{{ $mode['title'] }}</strong>
                        <span class="muted">{{ $mode['desc'] }}</span>
                    </span>
                    <span class="arcade-score">
                        {{ $best === null ? 'Belum dimainkan' : 'Terbaik '.$best }}
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endsection
