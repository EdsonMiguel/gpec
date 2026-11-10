<?php

namespace App\Modules\Sanidade\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Sanidade\Actions\AcompanharCasoClinico;
use App\Modules\Sanidade\Models\CasoClinico;
use App\Modules\Sanidade\Models\Doenca;
use App\Modules\Sanidade\Requests\AvaliacaoClinicaRequest;
use App\Modules\Sanidade\Requests\CasoClinicoRequest;
use App\Modules\Sanidade\Requests\EncerrarCasoClinicoRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CasoClinicoController extends Controller
{
    public function __construct(private AcompanharCasoClinico $acompanhamento) {}

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'animal_id' => ['nullable', 'integer'],
            'busca' => ['nullable', 'string', 'max:50'],
            'situacao' => ['nullable', Rule::in(['ATIVOS', 'ENCERRADOS', 'TODOS'])],
        ]);
        $animal = isset($filtros['animal_id']) ? Animal::withTrashed()->findOrFail($filtros['animal_id']) : null;
        $situacao = $filtros['situacao'] ?? ($animal ? 'TODOS' : 'ATIVOS');
        $busca = $filtros['busca'] ?? '';
        $casos = CasoClinico::with('animal')
            ->when($situacao === 'ATIVOS', fn (Builder $query): Builder => $query->ativos())
            ->when($situacao === 'ENCERRADOS', fn (Builder $query): Builder => $query->where('status', 'ENCERRADO'))
            ->when($animal, fn (Builder $query): Builder => $query->where('animal_id', $animal->id))
            ->when($busca !== '', fn (Builder $query): Builder => $query->whereHas(
                'animal', fn (Builder $animalQuery): Builder => $animalQuery->whereLike('brinco', '%'.addcslashes($busca, '%_\\').'%'),
            ))
            ->orderByDesc('data_inicio')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('sanidade::casos.index', compact('animal', 'casos', 'situacao', 'busca'));
    }

    public function create(Request $request): View
    {
        $filtros = $request->validate(['animal_id' => ['nullable', 'integer']]);
        $selected = isset($filtros['animal_id']) ? Animal::ativos()->findOrFail($filtros['animal_id']) : null;

        return view('sanidade::casos.form', compact('selected'));
    }

    public function store(CasoClinicoRequest $request): RedirectResponse
    {
        $caso = $this->acompanhamento->abrir($request->validated());

        return to_route('casos-clinicos.show', $caso)->with('success', 'Caso clínico aberto.');
    }

    public function show(CasoClinico $caso): View
    {
        $caso->load('animal');

        return view('sanidade::casos.show', [
            'caso' => $caso,
            'avaliacoes' => $caso->avaliacoes()->with('doenca')->orderByDesc('data_avaliacao')->orderByDesc('id')->paginate(10),
            'doencas' => $caso->estaAtivo() ? Doenca::where('ativo', true)->orderBy('nome')->get() : collect(),
        ]);
    }

    public function avaliar(AvaliacaoClinicaRequest $request, CasoClinico $caso): RedirectResponse
    {
        $this->acompanhamento->avaliar($caso, $request->validated());

        return to_route('casos-clinicos.show', $caso)->with('success', 'Avaliação veterinária registrada.');
    }

    public function encerrar(EncerrarCasoClinicoRequest $request, CasoClinico $caso): RedirectResponse
    {
        $this->acompanhamento->encerrar($caso, $request->validated());

        return to_route('casos-clinicos.show', $caso)->with('success', 'Caso encerrado. O histórico foi preservado.');
    }
}
