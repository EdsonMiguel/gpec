<?php

use App\Modules\Rebanho\Controllers\AnimalController;
use App\Modules\Rebanho\Controllers\LoteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/animais', [AnimalController::class, 'index'])->name('animais.index');
    Route::get('/lotes', [LoteController::class, 'index'])->name('lotes.index');
    Route::middleware('can:administrar')->group(function (): void {
        Route::resource('animais', AnimalController::class)->parameters(['animais' => 'animal'])->except(['index', 'show']);
        Route::resource('lotes', LoteController::class)->only(['create', 'store', 'edit', 'update']);
    });
    Route::get('/animais/{animal}', [AnimalController::class, 'show'])->withTrashed()->name('animais.show');
    Route::get('/animais/{animal}/genealogia', [AnimalController::class, 'genealogia'])->withTrashed()->name('animais.genealogia');
});
