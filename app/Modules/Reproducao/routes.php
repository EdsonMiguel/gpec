<?php

use App\Modules\Reproducao\Controllers\GestacaoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:registrar-manejo'])->group(function (): void {
    Route::get('/gestacoes', [GestacaoController::class, 'index'])->name('gestacoes.index');
    Route::get('/gestacoes/create', [GestacaoController::class, 'create'])->name('gestacoes.create');
    Route::post('/gestacoes', [GestacaoController::class, 'store'])->name('gestacoes.store');
    Route::get('/gestacoes/{gestacao}', [GestacaoController::class, 'show'])->name('gestacoes.show');
    Route::post('/gestacoes/{gestacao}/exames', [GestacaoController::class, 'registrarExame'])->name('gestacoes.exames.store');
    Route::post('/gestacoes/{gestacao}/encerrar', [GestacaoController::class, 'encerrar'])->name('gestacoes.encerrar');
    Route::post('/gestacoes/{gestacao}/crias', [GestacaoController::class, 'vincularCria'])->middleware('can:administrar')->name('gestacoes.crias.store');
});
