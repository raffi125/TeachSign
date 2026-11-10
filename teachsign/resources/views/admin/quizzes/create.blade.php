@extends('layouts.app')

@section('title', 'Kuis Baru')
@section('heading', 'Tambah Kuis')

@section('content')
    <section class="card">
        <form method="POST" action="{{ route('admin.quizzes.store') }}" class="form-stack">
            @csrf
            @include('admin.quizzes._form')
        </form>
    </section>
@endsection
