@extends('layouts.app')

@section('title', 'Ubah Pengguna')
@section('heading', 'Ubah Pengguna')

@section('content')
    <section class="card">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="form-stack">
            @csrf
            @method('PUT')
            @include('admin.users._form')
        </form>
    </section>
@endsection
