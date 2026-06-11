<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::inertia('login', 'auth/Login')->name('login');
    Route::inertia('register', 'auth/Register')->name('register');

    Route::post('login', [AuthController::class, 'login'])->name('login.store');
    Route::post('register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});
