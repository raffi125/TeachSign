<?php

use App\Http\Controllers\Api\Admin\GradingController;
use App\Http\Controllers\Api\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Api\Admin\ProgressController as AdminProgressController;
use App\Http\Controllers\Api\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\WordController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\QuizController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('lessons', [LessonController::class, 'index']);
    Route::get('lessons/{lesson}', [LessonController::class, 'show']);
    Route::get('quizzes', [QuizController::class, 'index']);
    Route::get('quizzes/{quiz}', [QuizController::class, 'show']);
    Route::post('quizzes/{quiz}', [QuizController::class, 'submit']);
    Route::get('games', [GameController::class, 'index']);
    Route::get('games/{mode}', [GameController::class, 'show']);
    Route::post('games/{mode}', [GameController::class, 'submit']);
    Route::get('progress', [ProgressController::class, 'index']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::apiResource('users', AdminUserController::class);
        Route::apiResource('lessons', AdminLessonController::class);
        Route::apiResource('quizzes', AdminQuizController::class);
        Route::patch('quizzes/{quiz}/toggle-lock', [AdminQuizController::class, 'toggleLock']);
        Route::get('progress', [AdminProgressController::class, 'index']);
        Route::apiResource('words', WordController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('grading', [GradingController::class, 'index']);
        Route::patch('grading/{quiz}/{attempt}', [GradingController::class, 'update']);
    });
});
