<?php

use App\Modules\Agenda\Controllers\AgendaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:registrar-manejo'])->group(function (): void {
    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
    Route::patch('/agenda/{compromisso}', [AgendaController::class, 'atualizar'])->whereNumber('compromisso')->name('agenda.atualizar');
    Route::delete('/agenda/{compromisso}', [AgendaController::class, 'excluir'])->whereNumber('compromisso')->name('agenda.excluir');
});
