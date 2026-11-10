<?php

namespace App\Modules\Alimentacao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Alimentacao\Actions\AlimentacaoTransaction;
use App\Modules\Alimentacao\Models\TipoAlimento;
use App\Modules\Alimentacao\Requests\TipoAlimentoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TipoAlimentoController extends Controller
{
    public function __construct(private AlimentacaoTransaction $transaction) {}

    public function index(): View
    {
        return view('alimentacao::tipos.index', ['tipos' => TipoAlimento::withCount('alimentos')->orderBy('nome')->paginate(20)]);
    }

    public function create(): View
    {
        return view('alimentacao::tipos.form', ['tipo' => new TipoAlimento(['ativo' => true])]);
    }

    public function store(TipoAlimentoRequest $request): RedirectResponse
    {
        $this->transaction->run(fn () => TipoAlimento::create($request->validated()));

        return to_route('tipos-alimento.index')->with('success', 'Tipo de alimento cadastrado.');
    }

    public function edit(TipoAlimento $tipo): View
    {
        return view('alimentacao::tipos.form', compact('tipo'));
    }

    public function update(TipoAlimentoRequest $request, TipoAlimento $tipo): RedirectResponse
    {
        $this->transaction->run(fn () => TipoAlimento::findOrFail($tipo->id)->update($request->validated()));

        return to_route('tipos-alimento.index')->with('success', 'Tipo de alimento atualizado.');
    }

    public function destroy(TipoAlimento $tipo): RedirectResponse
    {
        $this->transaction->run(function () use ($tipo): void {
            $record = TipoAlimento::findOrFail($tipo->id);
            if ($record->alimentos()->exists()) {
                throw ValidationException::withMessages(['tipo' => 'Tipo utilizado em alimentos. Inative-o para preservar os cadastros.']);
            }
            $record->delete();
        });

        return to_route('tipos-alimento.index')->with('success', 'Tipo de alimento excluído.');
    }
}
