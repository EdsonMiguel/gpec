<?php

use App\Modules\Fazendas\Controllers\AreaController;
use App\Modules\Fazendas\Controllers\FazendaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->scopeBindings()->group(function (): void {
    Route::get('/fazendas', [FazendaController::class, 'index'])->name('fazendas.index');
    Route::middleware('can:administrar')->group(function (): void {
        Route::resource('fazendas', FazendaController::class)->except(['index', 'show', 'destroy']);
        Route::resource('fazendas.areas', AreaController::class)->only(['create', 'store', 'edit', 'update']);
    });
    Route::get('/fazendas/{fazenda}', [FazendaController::class, 'show'])->name('fazendas.show');
});
