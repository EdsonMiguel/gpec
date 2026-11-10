<?php

use App\Modules\Financeiro\Controllers\ContaOrcamentariaController;
use App\Modules\Financeiro\Controllers\ProdutoController;
use App\Modules\Financeiro\Controllers\TransacaoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:administrar'])->group(function (): void {
    Route::redirect('/financeiro', '/transacoes')->name('financeiro.index');
    Route::resource('contas-orcamentarias', ContaOrcamentariaController::class)->parameters(['contas-orcamentarias' => 'conta'])->except('show');
    Route::resource('produtos', ProdutoController::class)->except('show');
    Route::resource('transacoes', TransacaoController::class)->parameters(['transacoes' => 'transacao'])->only(['index', 'create', 'store', 'show']);
    Route::post('/transacoes/{transacao}/efetivar', [TransacaoController::class, 'efetivar'])->name('transacoes.efetivar');
    Route::post('/transacoes/{transacao}/cancelar', [TransacaoController::class, 'cancelar'])->name('transacoes.cancelar');
});
