@extends('layouts.app')

@section('title', 'Pengguna Baru')
@section('heading', 'Tambah Pengguna')

@section('content')
    <section class="card">
        <form method="POST" action="{{ route('admin.users.store') }}" class="form-stack">
            @csrf
            @include('admin.users._form')
        </form>
    </section>
@endsection
