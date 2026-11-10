@extends('layouts.app')

@section('title', 'Ubah Kata')
@section('heading', 'Ubah Kata Mini Games')

@section('content')
    <section class="card">
        <form method="POST" action="{{ route('admin.word-quest.update', $word) }}" class="form-stack">
            @csrf
            @method('PUT')
            @include('admin.word-quest._form')
        </form>
    </section>
@endsection
