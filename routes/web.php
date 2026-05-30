<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\DocsController;

Route::get('/', [LeaderboardController::class, 'index'])->name('rankings');
Route::get('/docs', [DocsController::class, 'index'])->name('docs');

// Auth Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/parent', [ParentController::class, 'index'])->name('parent.dashboard');
    Route::post('/parent/student/{student}/upload-image', [ParentController::class, 'uploadImage'])->name('parent.student.upload-image');
});
