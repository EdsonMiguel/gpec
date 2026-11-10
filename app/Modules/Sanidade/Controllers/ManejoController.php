<?php

namespace App\Modules\Sanidade\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Rebanho\Models\Lote;
use App\Modules\Sanidade\Actions\RegistrarAplicacao;
use App\Modules\Sanidade\Models\ManejoSanitario;
use App\Modules\Sanidade\Models\Medicamento;
use App\Modules\Sanidade\Requests\ManejoRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManejoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate(['aplicacao_id' => ['nullable', 'integer'], 'lote_id' => ['nullable', 'integer']]);
        $loteId = isset($filtros['lote_id']) ? Lote::findOrFail($filtros['lote_id'])->id : null;
        $aplicacao = isset($filtros['aplicacao_id']) ? ManejoSanitario::with('animal')->findOrFail($filtros['aplicacao_id']) : null;
        $selected = $request->filled('animal_id') ? Animal::ativos()->findOrFail($request->integer('animal_id')) : null;
        if ($selected === null && $aplicacao?->animal->status === 'ATIVO' && ! $aplicacao->animal->trashed()) {
            $selected = $aplicacao->animal;
        }
        $manejos = ManejoSanitario::with(['animal', 'medicamento']);
        if ($aplicacao) {
            $manejos->whereKey($aplicacao->id);
        }

        return view('sanidade::index', [
            'selected' => $selected,
            'aplicacao' => $aplicacao,
            'loteId' => $loteId,
            'lotes' => Lote::withCount(['animais' => fn (Builder $query): Builder => $query->ativos()])->orderBy('nome')->get(),
            'medicamentos' => Medicamento::where('ativo', true)->orderBy('nome')->get(),
            'manejos' => $manejos->orderByDesc('data_aplicacao')->orderByDesc('id')->paginate(20),
        ]);
    }

    public function store(ManejoRequest $request, RegistrarAplicacao $registrar): RedirectResponse
    {
        $registros = $registrar->handle($request->validated());
        if ($request->validated('destino') === 'LOTE') {
            return to_route('sanidade.index')->with('success', $registros->count().' aplicações individuais registradas para os animais do lote.');
        }

        return to_route('sanidade.index')->with('success', 'Aplicação registrada.');
    }
}
