<?php

namespace App\Modules\Alimentacao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Alimentacao\Actions\AlimentacaoTransaction;
use App\Modules\Alimentacao\Models\Alimento;
use App\Modules\Alimentacao\Models\TipoAlimento;
use App\Modules\Alimentacao\Requests\AlimentoRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AlimentoController extends Controller
{
    public function __construct(private AlimentacaoTransaction $transaction) {}

    public function index(): View
    {
        return view('alimentacao::alimentos.index', ['alimentos' => Alimento::with('tipo')->withCount('itens')->orderBy('nome')->paginate(20)]);
    }

    public function create(): View
    {
        return $this->form(new Alimento(['ativo' => true]));
    }

    public function store(AlimentoRequest $request): RedirectResponse
    {
        $this->transaction->run(function () use ($request): void {
            $this->validateTipo($request);
            Alimento::create($request->validated());
        });

        return to_route('alimentos.index')->with('success', 'Alimento cadastrado.');
    }

    public function edit(Alimento $alimento): View
    {
        return $this->form($alimento);
    }

    public function update(AlimentoRequest $request, Alimento $alimento): RedirectResponse
    {
        $this->transaction->run(function () use ($request, $alimento): void {
            $record = Alimento::findOrFail($alimento->id);
            $this->validateTipo($request, $record);
            if ($record->unidade !== $request->validated('unidade') && $record->itens()->exists()) {
                throw ValidationException::withMessages(['unidade' => 'Este alimento já foi fornecido. Para usar outra unidade, cadastre um novo alimento.']);
            }
            $record->update($request->validated());
        });

        return to_route('alimentos.index')->with('success', 'Alimento atualizado.');
    }

    public function destroy(Alimento $alimento): RedirectResponse
    {
        $this->transaction->run(function () use ($alimento): void {
            $record = Alimento::findOrFail($alimento->id);
            if ($record->itens()->exists() || $record->itensFinanceiros()->exists()) {
                throw ValidationException::withMessages(['alimento' => 'Alimento utilizado em refeições ou transações. Inative-o para preservar o histórico.']);
            }
            $record->delete();
        });

        return to_route('alimentos.index')->with('success', 'Alimento excluído.');
    }

    private function validateTipo(AlimentoRequest $request, ?Alimento $alimento = null): void
    {
        $tipo = TipoAlimento::findOrFail($request->integer('tipo_alimento_id'));
        if (! $tipo->ativo && $tipo->id !== $alimento?->tipo_alimento_id) {
            throw ValidationException::withMessages(['tipo_alimento_id' => 'Selecione um tipo de alimento ativo.']);
        }
    }

    private function form(Alimento $alimento): View
    {
        return view('alimentacao::alimentos.form', [
            'alimento' => $alimento,
            'tipos' => TipoAlimento::where(fn (Builder $query): Builder => $query->where('ativo', true)->orWhere('id', $alimento->tipo_alimento_id))->orderBy('nome')->get(),
        ]);
    }
}
