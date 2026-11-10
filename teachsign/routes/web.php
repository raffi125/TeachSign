<?php

use App\Http\Controllers\Admin\GradingController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\ProgressController as AdminProgressController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WordQuestWordController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Student\LessonController as StudentLessonController;
use App\Http\Controllers\Student\MiniGameController;
use App\Http\Controllers\Student\ProgressController;
use App\Http\Controllers\Student\QuizController as StudentQuizController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:6,1');

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('learn')->name('student.')->group(function () {
        Route::get('lessons', [StudentLessonController::class, 'index'])->name('lessons.index');
        Route::get('lessons/{lesson}', [StudentLessonController::class, 'show'])->name('lessons.show');
        Route::get('quizzes', [StudentQuizController::class, 'index'])->name('quizzes.index');
        Route::get('lessons/{lesson}/quizzes/{quiz}', [StudentQuizController::class, 'show'])->name('quizzes.show');
        Route::post('lessons/{lesson}/quizzes/{quiz}', [StudentQuizController::class, 'submit'])->name('quizzes.submit');
        Route::get('games', [MiniGameController::class, 'index'])->name('games.index');
        Route::get('games/{mode}', [MiniGameController::class, 'show'])->name('game.show');
        Route::post('games/{mode}', [MiniGameController::class, 'submit'])->name('game.submit');
        Route::get('progress', [ProgressController::class, 'index'])->name('progress.index');
    });
});

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('lessons', LessonController::class);
        Route::resource('quizzes', QuizController::class);
        Route::patch('quizzes/{quiz}/toggle-lock', [QuizController::class, 'toggleLock'])->name('quizzes.toggle-lock');
        Route::get('progress', [AdminProgressController::class, 'index'])->name('progress.index');
        Route::resource('word-quest', WordQuestWordController::class)
            ->parameters(['word-quest' => 'word'])
            ->except('show');
        Route::get('grading', [GradingController::class, 'index'])->name('grading.index');
        Route::patch('grading/{quiz}/{attempt}', [GradingController::class, 'update'])->name('grading.update');
    });
