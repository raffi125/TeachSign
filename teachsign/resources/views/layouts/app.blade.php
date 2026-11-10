<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · TeachSign</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Newsreader:wght@500;600&display=swap" rel="stylesheet">
    @include('partials.theme')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <span class="brand-mark" aria-hidden="true">TS</span>
            <span class="brand-text">TeachSign</span>
        </a>

        <nav class="nav">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                Dashboard
            </a>

            @auth
                @if (auth()->user()->isAdmin())
                    <p class="nav-label">Manajemen</p>
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">
                        Pengguna
                    </a>
                    <a href="{{ route('admin.lessons.index') }}" class="nav-link {{ request()->routeIs('admin.lessons.*') ? 'is-active' : '' }}">
                        Pelajaran
                    </a>
                    <a href="{{ route('admin.quizzes.index') }}" class="nav-link {{ request()->routeIs('admin.quizzes.*') ? 'is-active' : '' }}">
                        Kuis
                    </a>
                    <a href="{{ route('admin.word-quest.index') }}" class="nav-link {{ request()->routeIs('admin.word-quest.*') ? 'is-active' : '' }}">
                        Mini Games
                    </a>
                    <a href="{{ route('admin.progress.index') }}" class="nav-link {{ request()->routeIs('admin.progress.*') ? 'is-active' : '' }}">
                        Progres
                    </a>
                    <a href="{{ route('admin.grading.index') }}" class="nav-link {{ request()->routeIs('admin.grading.*') ? 'is-active' : '' }}">
                        Penilaian
                    </a>
                @else
                    <p class="nav-label">Belajar</p>
                    <a href="{{ route('student.lessons.index') }}" class="nav-link {{ request()->routeIs('student.lessons.*') ? 'is-active' : '' }}">
                        Pelajaran
                    </a>
                    <a href="{{ route('student.quizzes.index') }}" class="nav-link {{ request()->routeIs('student.quizzes.*') ? 'is-active' : '' }}">
                        Kuis
                    </a>
                    <a href="{{ route('student.games.index') }}" class="nav-link {{ request()->routeIs('student.games.*', 'student.game.*') ? 'is-active' : '' }}">
                        Games
                    </a>
                    <a href="{{ route('student.progress.index') }}" class="nav-link {{ request()->routeIs('student.progress.*') ? 'is-active' : '' }}">
                        Progres Saya
                    </a>
                @endif
            @endauth
        </nav>

        @auth
            <div class="sidebar-footer">
                <div class="user-chip">
                    <span class="avatar">{{ strtoupper(substr(auth()->user()->username, 0, 1)) }}</span>
                    <div class="user-meta">
                        <strong>{{ auth()->user()->username }}</strong>
                        <span class="badge badge-{{ auth()->user()->role }}">{{ auth()->user()->isAdmin() ? 'Admin' : 'Siswa' }}</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-block">Keluar</button>
                </form>
            </div>
        @endauth
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-title">@yield('heading', 'Dashboard')</h1>
            </div>
            <div class="topbar-tools">
                @yield('actions')
                @include('partials.theme-toggle')
            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Periksa kembali input Anda.</strong>
                <ul class="alert-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
