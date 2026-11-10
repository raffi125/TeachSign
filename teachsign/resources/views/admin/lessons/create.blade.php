@extends('layouts.app')

@section('title', 'Pelajaran Baru')
@section('heading', 'Tambah Pelajaran')

@section('content')
    <section class="card">
        <form method="POST" action="{{ route('admin.lessons.store') }}" class="form-stack">
            @csrf
            @include('admin.lessons._form')
        </form>
    </section>
@endsection
