<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · TeachSign</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Newsreader:wght@500;600&display=swap" rel="stylesheet">
    @include('partials.theme')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-body">
    @include('partials.theme-toggle', ['class' => 'theme-toggle-floating'])
    <div class="auth-card">
        <div class="auth-brand">
            <span class="brand-mark">TS</span>
            <span class="brand-text">TeachSign</span>
        </div>
        <h1>Masuk</h1>
        <p class="muted">Masuk untuk melanjutkan belajar.</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="alert-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="form-stack">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="input" required autofocus>
            </div>
            <div class="field">
                <label for="password">Kata sandi</label>
                <input id="password" type="password" name="password" class="input" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Masuk</button>
        </form>

        <p class="auth-hint">Belum punya akun? <a href="{{ route('register') }}" class="link">Daftar</a></p>
    </div>
</body>
</html>
