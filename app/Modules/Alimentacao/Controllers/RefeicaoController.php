<?php

namespace App\Modules\Alimentacao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Alimentacao\Actions\RegistrarRefeicao;
use App\Modules\Alimentacao\Models\Alimento;
use App\Modules\Alimentacao\Models\Refeicao;
use App\Modules\Alimentacao\Requests\RefeicaoRequest;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Rebanho\Models\Lote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefeicaoController extends Controller
{
    public function __construct(private RegistrarRefeicao $registrar) {}

    public function index(): View
    {
        return view('alimentacao::refeicoes.index', ['refeicoes' => Refeicao::with(['animal', 'itens.alimento'])->orderByDesc('data_fornecimento')->paginate(20)]);
    }

    public function create(Request $request): View
    {
        $filtros = $request->validate(['lote_id' => ['nullable', 'integer']]);
        $loteId = isset($filtros['lote_id']) ? Lote::findOrFail($filtros['lote_id'])->id : null;

        return view('alimentacao::refeicoes.form', [
            'loteId' => $loteId,
            'lotes' => Lote::withCount(['animais' => fn (Builder $query): Builder => $query->ativos()])->orderBy('nome')->get(),
            'selected' => $request->filled('animal_id') ? Animal::ativos()->findOrFail($request->integer('animal_id')) : null,
            'alimentos' => Alimento::disponiveis()->orderBy('nome')->get(),
        ]);
    }

    public function store(RefeicaoRequest $request): RedirectResponse
    {
        $registros = $this->registrar->handle($request->validated());
        if ($request->validated('destino') === 'LOTE') {
            return to_route('refeicoes.index')->with('success', $registros->count().' refeições individuais registradas para os animais do lote.');
        }

        return to_route('refeicoes.show', $registros->first())->with('success', 'Refeição registrada.');
    }

    public function show(Refeicao $refeicao): View
    {
        $refeicao->load(['animal', 'itens.alimento']);

        return view('alimentacao::refeicoes.show', compact('refeicao'));
    }
}
