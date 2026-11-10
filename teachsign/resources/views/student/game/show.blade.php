@extends('layouts.app')

@section('title', 'Games')
@section('heading', 'Arcade TeachSign')

@section('actions')
    <a href="{{ route('student.games.index') }}" class="btn btn-ghost">‹ Semua Game</a>
@endsection

@section('content')
    <div class="game-modes">
        <a href="{{ route('student.game.show', ['mode' => 'sign']) }}"
           class="game-mode {{ $mode === 'sign' ? 'is-active' : '' }}">
            <span>
                <strong>Sign Rush</strong>
                <span class="muted">Tap emoji, 3 detik per ronde</span>
            </span>
        </a>
        <a href="{{ route('student.game.show', ['mode' => 'word']) }}"
           class="game-mode {{ $mode === 'word' ? 'is-active' : '' }}">
            <span>
                <strong>Word Quest</strong>
                <span class="muted">Gambar muncul, ketik jawaban</span>
            </span>
        </a>
        <a href="{{ route('student.game.show', ['mode' => 'flip']) }}"
           class="game-mode {{ $mode === 'flip' ? 'is-active' : '' }}">
            <span>
                <strong>Flip Flap</strong>
                <span class="muted">Balik kartu, cocokkan pasangan</span>
            </span>
        </a>
    </div>

    @include('student.game._'.$mode)
@endsection
