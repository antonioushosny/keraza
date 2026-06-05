<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\DocsController;

$defineRoutes = function ($prefix = '') {
    $namePrefix = $prefix ? $prefix . '.' : '';
    Route::group(['prefix' => $prefix, 'as' => $namePrefix], function () {
        Route::get('/', [LeaderboardController::class, 'index'])->name('rankings');
        Route::get('/docs', [DocsController::class, 'index'])->name('docs');

        // Auth Routes
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login']);
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

        Route::middleware(['auth'])->group(function () {
            Route::get('/parent', [ParentController::class, 'index'])->name('parent.dashboard');
            Route::get('/parent/profile', [ParentController::class, 'showProfile'])->name('parent.profile');
            Route::post('/parent/profile', [ParentController::class, 'updateProfile']);
            Route::post('/parent/student/{student}/upload-image', [ParentController::class, 'uploadImage'])->name('parent.student.upload-image');
        });

        Route::middleware(['auth:admin'])->group(function () {
            Route::get('/admin/students/import-template', [\App\Http\Controllers\StudentImportController::class, 'downloadTemplate'])->name('admin.students.import-template');
        });
    });
};

$defineRoutes('');
$defineRoutes('e3dady');
