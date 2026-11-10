<?php

use App\Modules\Alimentacao\Controllers\AlimentoController;
use App\Modules\Alimentacao\Controllers\RefeicaoController;
use App\Modules\Alimentacao\Controllers\TipoAlimentoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:registrar-manejo'])->group(function (): void {
    Route::redirect('/alimentacao', '/refeicoes')->name('alimentacao.index');
    Route::resource('refeicoes', RefeicaoController::class)->parameters(['refeicoes' => 'refeicao'])->only(['index', 'create', 'store', 'show']);
    Route::get('/alimentos', [AlimentoController::class, 'index'])->name('alimentos.index');
    Route::get('/tipos-alimento', [TipoAlimentoController::class, 'index'])->name('tipos-alimento.index');
    Route::middleware('can:administrar')->group(function (): void {
        Route::resource('alimentos', AlimentoController::class)->except(['index', 'show']);
        Route::resource('tipos-alimento', TipoAlimentoController::class)->parameters(['tipos-alimento' => 'tipo'])->except(['index', 'show']);
    });
});
