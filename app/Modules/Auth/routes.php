<?php

use App\Modules\Auth\Controllers\RegisteredUserController;
use App\Modules\Auth\Controllers\SessionController;
use App\Modules\Auth\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'guest'])->group(function (): void {
    Route::get('/cadastro', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/cadastro', [RegisteredUserController::class, 'store'])->middleware('throttle:register')->name('register.store');
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:login')->name('login.store');
});
Route::post('/logout', [SessionController::class, 'destroy'])->middleware(['web', 'auth'])->name('logout');

Route::middleware(['web', 'auth', 'can:administrar'])->group(function (): void {
    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
});
