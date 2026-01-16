<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DatasetController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\DatasetController as AdminDatasetController;
use App\Http\Controllers\Admin\AdminController;


Route::get('/', [HomeController::class, 'index'])->name('home');

// Register routes
Route::get('/register', [RegisterController::class, 'showForm'])->name('register.show');
Route::post('/register', [RegisterController::class, 'register'])->name('register.perform');

// Login routes
Route::get('/login', [LoginController::class, 'showForm'])->name('login.show');
Route::post('/login', [LoginController::class, 'login'])->name('login.perform');

// Logout route
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('home');
})->name('logout');

// Dataset routes (auth only)
Route::middleware('auth')->group(function () {
    Route::get('/datasets', [DatasetController::class, 'index'])->name('datasets.index');

    // Upload routes must be defined before any dynamic /datasets/{id} route
    Route::get('/datasets/upload', [DatasetController::class, 'uploadForm'])->name('datasets.upload');
    Route::post('/datasets/upload', [DatasetController::class, 'upload'])->name('datasets.upload.post');

    Route::get('/datasets/{id}/edit', [DatasetController::class, 'edit'])
        ->whereNumber('id')
        ->name('datasets.edit');

    Route::put('/datasets/{id}', [DatasetController::class, 'update'])
        ->whereNumber('id')
        ->name('datasets.update');

    Route::delete('/datasets/{id}', [DatasetController::class, 'destroy'])
        ->whereNumber('id')
        ->name('datasets.destroy');

    Route::post('/datasets/{id}/share', [DatasetController::class, 'share'])
        ->whereNumber('id')
        ->name('datasets.share');
});

// Share route (token-based) must be defined before /datasets/{id}.
Route::get('/datasets/share/{token}', [DatasetController::class, 'shareShow'])
    ->where('token', '[A-Za-z0-9\-]{16,128}')
    ->name('datasets.share.show');

// Datasets: public routes
Route::get('/datasets/{id}', [DatasetController::class, 'show'])
    ->whereNumber('id')
    ->name('datasets.show');

Route::get('/datasets/{id}/download', [DatasetController::class, 'download'])
    ->whereNumber('id')
    ->name('datasets.download');

Route::get('/files/{file}/download', [DatasetController::class, 'downloadFile'])
    ->whereNumber('file')
    ->name('files.download');

// Admin routes protected by auth and admin middleware
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');

    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users');
    Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.delete');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/datasets', [AdminDatasetController::class, 'index'])->name('datasets.index');
    Route::get('/datasets/{dataset}/edit', [AdminDatasetController::class, 'edit'])->name('datasets.edit');
    Route::put('/datasets/{dataset}', [AdminDatasetController::class, 'update'])->name('datasets.update');
    Route::delete('/datasets/{dataset}', [AdminDatasetController::class, 'destroy'])->name('datasets.destroy');
});

