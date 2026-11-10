<?php

namespace App\Modules\Reproducao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Reproducao\Actions\AcompanharGestacao;
use App\Modules\Reproducao\Actions\VincularCria;
use App\Modules\Reproducao\Models\Gestacao;
use App\Modules\Reproducao\Requests\EncerrarGestacaoRequest;
use App\Modules\Reproducao\Requests\ExameGestacaoRequest;
use App\Modules\Reproducao\Requests\GestacaoRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GestacaoController extends Controller
{
    public function __construct(private AcompanharGestacao $acompanhamento) {}

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'animal_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(array_keys(Gestacao::STATUS))],
        ]);

        $animal = isset($filtros['animal_id']) ? Animal::withTrashed()->findOrFail($filtros['animal_id']) : null;
        $gestacoes = Gestacao::with(['matriz', 'reprodutor'])
            ->when($animal, fn (Builder $query): Builder => $query->where(
                fn (Builder $ciclos): Builder => $ciclos->where('matriz_id', $animal->id)->orWhere('reprodutor_id', $animal->id),
            ))
            ->when($filtros['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->orderByDesc('data_cobertura')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('reproducao::index', compact('gestacoes', 'animal'));
    }

    public function create(Request $request): View
    {
        $filtros = $request->validate(['matriz_id' => ['nullable', 'integer']]);

        return view('reproducao::form', [
            'matrizId' => $filtros['matriz_id'] ?? null,
            'matrizes' => Animal::ativos()->where('sexo', 'FEMEA')->orderBy('brinco')->get(),
            'reprodutores' => Animal::withTrashed()->where('sexo', 'MACHO')->orderBy('brinco')->get(),
        ]);
    }

    public function store(GestacaoRequest $request): RedirectResponse
    {
        $gestacao = $this->acompanhamento->iniciar($request->validated());

        return to_route('gestacoes.show', $gestacao)->with('success', 'Cobertura registrada.');
    }

    public function show(Request $request, Gestacao $gestacao): View
    {
        $gestacao->load(['matriz', 'reprodutor', 'crias', 'exames' => fn ($query) => $query->orderBy('data_exame')->orderBy('id')]);
        $criasDisponiveis = collect();
        if ($gestacao->status === 'PARTO' && $request->user()->can('administrar')) {
            $criasDisponiveis = Animal::whereNull('gestacao_nascimento_id')
                ->whereDate('data_nascimento', $gestacao->data_encerramento)
                ->orderBy('brinco')->get();
        }

        return view('reproducao::show', compact('gestacao', 'criasDisponiveis'));
    }

    public function registrarExame(ExameGestacaoRequest $request, Gestacao $gestacao): RedirectResponse
    {
        $this->acompanhamento->registrarExame($gestacao, $request->validated());

        return to_route('gestacoes.show', $gestacao)->with('success', 'Exame registrado.');
    }

    public function encerrar(EncerrarGestacaoRequest $request, Gestacao $gestacao): RedirectResponse
    {
        $this->acompanhamento->encerrar($gestacao, $request->validated());

        return to_route('gestacoes.show', $gestacao)->with('success', 'Desfecho registrado.');
    }

    public function vincularCria(Request $request, Gestacao $gestacao, VincularCria $vincular): RedirectResponse
    {
        $dados = $request->validate(['cria_id' => ['required', 'integer']]);
        $vincular->handle($gestacao, (int) $dados['cria_id']);

        return to_route('gestacoes.show', $gestacao)->with('success', 'Cria vinculada ao parto. Filiação registrada na ficha do animal.');
    }
}
