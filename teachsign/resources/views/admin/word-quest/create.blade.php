@extends('layouts.app')

@section('title', 'Kata Baru')
@section('heading', 'Tambah Kata Mini Games')

@section('content')
    <section class="card">
        <form method="POST" action="{{ route('admin.word-quest.store') }}" class="form-stack">
            @csrf
            @include('admin.word-quest._form')
        </form>
    </section>
@endsection
