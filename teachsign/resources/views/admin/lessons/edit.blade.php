@extends('layouts.app')

@section('title', 'Ubah Pelajaran')
@section('heading', 'Ubah Pelajaran')

@section('content')
    <section class="card">
        <form method="POST" action="{{ route('admin.lessons.update', $lesson) }}" class="form-stack">
            @csrf
            @method('PUT')
            @include('admin.lessons._form')
        </form>
    </section>
@endsection
