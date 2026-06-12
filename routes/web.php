<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\FinancialStatementController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::inertia('login', 'auth/Login')->name('login');
    Route::inertia('register', 'auth/Register')->name('register');

    Route::post('login', [AuthController::class, 'login'])->name('login.store');
    Route::post('register', [AuthController::class, 'register'])->name('register.store');

    Route::get('two-factor-challenge', [AuthController::class, 'twoFactorChallenge'])->name('two-factor.challenge');
    Route::post('two-factor-challenge', [AuthController::class, 'confirmTwoFactor'])->name('two-factor.confirm');
});

Route::middleware('auth')->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('transferencias', 'Transferencias')->name('transferencias');
    Route::inertia('historico', 'Historico')->name('historico');

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('wallet/balance', [WalletController::class, 'balance'])->name('wallet.balance');
    Route::get('wallet/balance-history', [WalletController::class, 'balanceHistory'])->name('wallet.balance-history');

    Route::apiResource('financial-statements', FinancialStatementController::class);
});
