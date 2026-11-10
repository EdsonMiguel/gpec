<?php

use App\Modules\Sanidade\Controllers\CasoClinicoController;
use App\Modules\Sanidade\Controllers\DoencaController;
use App\Modules\Sanidade\Controllers\ManejoController;
use App\Modules\Sanidade\Controllers\MedicamentoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:registrar-manejo'])->group(function (): void {
    Route::get('/sanidade', [ManejoController::class, 'index'])->name('sanidade.index');
    Route::post('/sanidade', [ManejoController::class, 'store'])->name('sanidade.store');
    Route::get('/sanidade/casos', [CasoClinicoController::class, 'index'])->name('casos-clinicos.index');
    Route::get('/sanidade/casos/create', [CasoClinicoController::class, 'create'])->name('casos-clinicos.create');
    Route::post('/sanidade/casos', [CasoClinicoController::class, 'store'])->name('casos-clinicos.store');
    Route::get('/sanidade/casos/{caso}', [CasoClinicoController::class, 'show'])->name('casos-clinicos.show');
    Route::post('/sanidade/casos/{caso}/avaliacoes', [CasoClinicoController::class, 'avaliar'])->name('casos-clinicos.avaliacoes.store');
    Route::post('/sanidade/casos/{caso}/encerrar', [CasoClinicoController::class, 'encerrar'])->name('casos-clinicos.encerrar');

    Route::middleware('can:administrar')->group(function (): void {
        Route::resource('doencas', DoencaController::class)->parameters(['doencas' => 'doenca'])->except(['show', 'destroy']);
        Route::get('/medicamentos', [MedicamentoController::class, 'index'])->name('medicamentos.index');
        Route::post('/medicamentos', [MedicamentoController::class, 'store'])->name('medicamentos.store');
        Route::patch('/medicamentos/{medicamento}/disponibilidade', [MedicamentoController::class, 'toggle'])->name('medicamentos.toggle');
    });
});
