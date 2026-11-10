<?php

namespace App\Modules\Financeiro\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Alimentacao\Models\Alimento;
use App\Modules\Financeiro\Actions\FinanceiroTransaction;
use App\Modules\Financeiro\Actions\RegistrarTransacao;
use App\Modules\Financeiro\Models\ContaOrcamentaria;
use App\Modules\Financeiro\Models\Produto;
use App\Modules\Financeiro\Models\Transacao;
use App\Modules\Financeiro\Requests\CancelamentoRequest;
use App\Modules\Financeiro\Requests\EfetivacaoRequest;
use App\Modules\Financeiro\Requests\TransacaoRequest;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Sanidade\Models\Medicamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransacaoController extends Controller
{
    public function __construct(private FinanceiroTransaction $transaction) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'inicio' => ['nullable', 'date_format:Y-m-d'], 'fim' => ['nullable', 'date_format:Y-m-d'],
            'tipo' => ['nullable', Rule::in(['COMPRA', 'VENDA'])],
            'status' => ['nullable', Rule::in(['PENDENTE', 'EFETIVADA', 'CANCELADA'])],
            'conta_id' => ['nullable', 'integer', Rule::exists('contas_orcamentarias', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);
        $inicio = $filters['inicio'] ?? today()->startOfMonth()->toDateString();
        $fim = $filters['fim'] ?? today()->endOfMonth()->toDateString();
        if ($fim < $inicio) {
            throw ValidationException::withMessages(['fim' => 'A data final deve ser igual ou posterior à inicial.']);
        }
        $query = Transacao::whereBetween('data_transacao', [$inicio, $fim]);
        foreach (['tipo', 'status', 'conta_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field === 'conta_id' ? 'conta_orcamentaria_id' : $field, $filters[$field]);
            }
        }

        return view('financeiro::transacoes.index', [
            'transacoes' => (clone $query)->with('conta')->orderByDesc('data_transacao')->orderByDesc('id')->paginate(20)->withQueryString(),
            'receitas' => (string) (clone $query)->where('tipo', 'VENDA')->where('status', 'EFETIVADA')->sum('valor_total'),
            'despesas' => (string) (clone $query)->where('tipo', 'COMPRA')->where('status', 'EFETIVADA')->sum('valor_total'),
            'aPagar' => (string) (clone $query)->where('tipo', 'COMPRA')->where('status', 'PENDENTE')->sum('valor_total'),
            'aReceber' => (string) (clone $query)->where('tipo', 'VENDA')->where('status', 'PENDENTE')->sum('valor_total'),
            'contas' => ContaOrcamentaria::orderBy('nome')->get(), 'inicio' => $inicio, 'fim' => $fim,
        ]);
    }

    public function create(): View
    {
        return view('financeiro::transacoes.form', [
            'contas' => ContaOrcamentaria::where('ativo', true)->orderBy('nome')->get(),
            'alimentos' => Alimento::disponiveis()->orderBy('nome')->get(),
            'medicamentos' => Medicamento::where('ativo', true)->orderBy('nome')->get(),
            'animais' => Animal::withTrashed()->orderBy('brinco')->get(),
            'produtos' => Produto::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function store(TransacaoRequest $request, RegistrarTransacao $registrar): RedirectResponse
    {
        $record = $registrar->handle($request->validated());

        return to_route('transacoes.show', $record)->with('success', 'Transação registrada.');
    }

    public function show(Transacao $transacao): View
    {
        $transacao->load(['conta', 'itens']);

        return view('financeiro::transacoes.show', compact('transacao'));
    }

    public function efetivar(EfetivacaoRequest $request, Transacao $transacao): RedirectResponse
    {
        $this->transaction->run(function () use ($request, $transacao): void {
            $record = Transacao::findOrFail($transacao->id);
            if ($record->status === 'EFETIVADA' && $record->data_efetivacao->toDateString() === $request->validated('data_efetivacao') && $record->forma_pagamento === $request->validated('forma_pagamento')) {
                return;
            }
            if ($record->status !== 'PENDENTE') {
                throw ValidationException::withMessages(['transacao' => 'Somente transações pendentes podem ser efetivadas.']);
            }
            $record->update([...$request->validated(), 'status' => 'EFETIVADA']);
        });

        return to_route('transacoes.show', $transacao)->with('success', 'Pagamento ou recebimento registrado.');
    }

    public function cancelar(CancelamentoRequest $request, Transacao $transacao): RedirectResponse
    {
        $this->transaction->run(function () use ($request, $transacao): void {
            $record = Transacao::findOrFail($transacao->id);
            if ($record->status === 'CANCELADA') {
                if ($record->motivo_cancelamento !== $request->validated('motivo_cancelamento')) {
                    throw ValidationException::withMessages(['transacao' => 'Esta transação já foi cancelada e seu histórico é preservado.']);
                }

                return;
            }
            $record->update(['status' => 'CANCELADA', 'data_cancelamento' => today(), 'motivo_cancelamento' => $request->validated('motivo_cancelamento')]);
        });

        return to_route('transacoes.show', $transacao)->with('success', 'Lançamento cancelado. Histórico preservado.');
    }
}
