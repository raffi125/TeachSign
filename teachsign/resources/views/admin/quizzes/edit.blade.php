@extends('layouts.app')

@section('title', 'Ubah Kuis')
@section('heading', 'Ubah Kuis')

@section('content')
    <section class="card">
        <form method="POST" action="{{ route('admin.quizzes.update', $quiz) }}" class="form-stack">
            @csrf
            @method('PUT')
            @include('admin.quizzes._form')
        </form>
    </section>
@endsection
