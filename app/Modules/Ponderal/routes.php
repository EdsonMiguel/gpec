<?php

use App\Modules\Ponderal\Controllers\MotivoPesagemController;
use App\Modules\Ponderal\Controllers\PesagemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:registrar-manejo'])->group(function (): void {
    Route::get('/pesagens', [PesagemController::class, 'index'])->name('pesagens.index');
    Route::post('/pesagens', [PesagemController::class, 'store'])->name('pesagens.store');
    Route::get('/motivos-pesagem', [MotivoPesagemController::class, 'index'])->name('motivos-pesagem.index');
    Route::middleware('can:administrar')->group(function (): void {
        Route::resource('motivos-pesagem', MotivoPesagemController::class)->parameters(['motivos-pesagem' => 'motivo'])->except(['index', 'show']);
    });
    Route::get('/motivos-pesagem/{motivo}', [MotivoPesagemController::class, 'show'])->name('motivos-pesagem.show');
});
