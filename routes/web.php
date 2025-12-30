<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DatasetController;


Route::view('/', 'home')->name('home');

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

    Route::get('/datasets/upload', [DatasetController::class, 'uploadForm'])->name('datasets.upload');
    Route::post('/datasets/upload', [DatasetController::class, 'upload'])->name('datasets.upload.post');

    Route::get('/datasets/{id}', [DatasetController::class, 'show'])->name('datasets.show');
    Route::get('/datasets/{id}/edit', [DatasetController::class, 'edit'])->name('datasets.edit');
    Route::put('/datasets/{id}', [DatasetController::class, 'update'])->name('datasets.update');
    Route::delete('/datasets/{id}', [DatasetController::class, 'destroy'])->name('datasets.destroy');

    Route::post('/datasets/{id}/share', [DatasetController::class, 'share'])->name('datasets.share');
});

// Admin routes protected by auth and admin middleware
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users');
    Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.delete');
});
