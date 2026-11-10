<?php

namespace App\Modules\Financeiro\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Financeiro\Actions\FinanceiroTransaction;
use App\Modules\Financeiro\Models\ContaOrcamentaria;
use App\Modules\Financeiro\Requests\ContaOrcamentariaRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContaOrcamentariaController extends Controller
{
    public function __construct(private FinanceiroTransaction $transaction) {}

    public function index(): View
    {
        return view('financeiro::contas.index', ['contas' => ContaOrcamentaria::withCount('transacoes')
            ->withSum(['transacoes as realizado' => fn (Builder $query): Builder => $query->where('status', 'EFETIVADA')], 'valor_total')
            ->withSum(['transacoes as pendente' => fn (Builder $query): Builder => $query->where('status', 'PENDENTE')], 'valor_total')
            ->orderBy('codigo')->paginate(20)]);
    }

    public function create(): View
    {
        return view('financeiro::contas.form', ['conta' => new ContaOrcamentaria(['ativo' => true, 'valor_orcado' => '0.00', 'periodo_inicio' => today()->startOfYear(), 'periodo_fim' => today()->endOfYear()])]);
    }

    public function store(ContaOrcamentariaRequest $request): RedirectResponse
    {
        $this->transaction->run(fn () => ContaOrcamentaria::create($request->validated()));

        return to_route('contas-orcamentarias.index')->with('success', 'Conta de orçamento cadastrada.');
    }

    public function edit(ContaOrcamentaria $conta): View
    {
        return view('financeiro::contas.form', compact('conta'));
    }

    public function update(ContaOrcamentariaRequest $request, ContaOrcamentaria $conta): RedirectResponse
    {
        $this->transaction->run(function () use ($request, $conta): void {
            $record = ContaOrcamentaria::findOrFail($conta->id);
            $record->fill($request->validated());
            if ($record->isDirty(['natureza', 'periodo_inicio', 'periodo_fim']) && $record->transacoes()->exists()) {
                throw ValidationException::withMessages(['conta' => 'Uma conta utilizada mantém a natureza e a vigência. Cadastre outra conta para um novo período.']);
            }
            $record->save();
        });

        return to_route('contas-orcamentarias.index')->with('success', 'Conta de orçamento atualizada.');
    }

    public function destroy(ContaOrcamentaria $conta): RedirectResponse
    {
        $this->transaction->run(function () use ($conta): void {
            $record = ContaOrcamentaria::findOrFail($conta->id);
            if ($record->transacoes()->exists()) {
                throw ValidationException::withMessages(['conta' => 'Conta utilizada em transações. Inative-a para preservar o histórico.']);
            }
            $record->delete();
        });

        return to_route('contas-orcamentarias.index')->with('success', 'Conta de orçamento excluída.');
    }
}
