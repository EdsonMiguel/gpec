<?php

namespace App\Modules\Rebanho\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Models\Tenant;
use App\Modules\Ponderal\Models\Pesagem;
use App\Modules\Rebanho\Actions\SaveAnimal;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Rebanho\Models\Lote;
use App\Modules\Rebanho\Requests\AnimalRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnimalController extends Controller
{
    public function __construct(private SaveAnimal $saveAnimal) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'busca' => ['nullable', 'string', 'max:50'],
            'lote_id' => ['nullable', 'integer'],
        ]);
        $lote = isset($filters['lote_id']) ? Lote::findOrFail($filters['lote_id']) : null;
        $search = $filters['busca'] ?? '';
        $animais = Animal::with('lote')
            ->withCount(['casosClinicos as casos_clinicos_ativos_count' => fn (Builder $query): Builder => $query->ativos()])
            ->when($lote, fn (Builder $query): Builder => $query->where('lote_id', $lote->id))
            ->when($search !== '', fn (Builder $query): Builder => $query->whereLike('brinco', '%'.addcslashes($search, '%_\\').'%'))
            ->orderBy('brinco')->paginate(20)->withQueryString();

        return view('rebanho::animais.index', compact('animais', 'search', 'lote'));
    }

    public function create(): View
    {
        return $this->form(new Animal(['status' => 'ATIVO']));
    }

    public function store(AnimalRequest $request): RedirectResponse
    {
        $animal = $this->saveAnimal->handle($request->validated());

        return to_route('animais.show', $animal)->with('success', 'Animal cadastrado.');
    }

    public function show(Animal $animal): View
    {
        $animal->load(['lote', 'pai', 'mae']);
        $animal->loadCount(['casosClinicos as casos_clinicos_ativos_count' => fn (Builder $query): Builder => $query->ativos()]);

        return view('rebanho::animais.show', [
            'animal' => $animal,
            ...$this->evolucaoPeso($animal),
            'pesagens' => $animal->pesagens()->with('motivo')->orderByDesc('data_pesagem')->paginate(10, ['*'], 'pesagens'),
            'manejos' => $animal->manejos()->with('medicamento')->orderByDesc('data_aplicacao')->paginate(10, ['*'], 'manejos'),
            'casosClinicos' => $animal->casosClinicos()->orderByDesc('data_inicio')->orderByDesc('id')->limit(5)->get(),
            'refeicoes' => $animal->refeicoes()->with('itens.alimento')->orderByDesc('data_fornecimento')->paginate(10, ['*'], 'refeicoes'),
        ]);
    }

    /**
     * @return array{evolucaoPeso: list<array{instante: int, data: string, peso: float}>, diasAcompanhados: int, ganhoMedioDiario: ?float}
     */
    private function evolucaoPeso(Animal $animal): array
    {
        $pesagens = $animal->pesagens()->orderBy('data_pesagem')->get(['peso', 'data_pesagem']);
        $primeira = $pesagens->first();
        $ultima = $pesagens->last();
        $dias = $primeira && $ultima
            ? (int) $primeira->data_pesagem->copy()->startOfDay()->diff($ultima->data_pesagem->copy()->startOfDay())->days
            : 0;

        return [
            'evolucaoPeso' => $pesagens->map(fn (Pesagem $pesagem): array => [
                'instante' => $pesagem->data_pesagem->getTimestamp(),
                'data' => $pesagem->data_pesagem->format('d/m/Y H:i'),
                'peso' => (float) $pesagem->peso,
            ])->all(),
            'diasAcompanhados' => $dias,
            'ganhoMedioDiario' => $dias > 0 ? ((float) $ultima->peso - (float) $primeira->peso) / $dias : null,
        ];
    }

    public function edit(Animal $animal): View
    {
        return $this->form($animal);
    }

    public function genealogia(Animal $animal): View
    {
        $animal->load(['pai.pai', 'pai.mae', 'mae.pai', 'mae.mae', 'gestacaoNascimento']);
        $campoFilhotes = $animal->sexo === 'FEMEA' ? 'mae_id' : 'pai_id';
        $descendentes = Animal::withTrashed()->where($campoFilhotes, $animal->id)->orderBy('brinco')->paginate(20);

        return view('rebanho::animais.genealogia', compact('animal', 'descendentes'));
    }

    public function update(AnimalRequest $request, Animal $animal): RedirectResponse
    {
        $animal = $this->saveAnimal->handle($request->validated(), $animal);

        return to_route('animais.show', $animal)->with('success', 'Animal atualizado.');
    }

    public function destroy(Request $request, Animal $animal): RedirectResponse
    {
        DB::transaction(function () use ($request, $animal): void {
            Tenant::whereKey($request->user()->tenant_id)->lockForUpdate()->firstOrFail();
            $animal->delete();
        });

        return to_route('animais.index')->with('success', 'Animal arquivado. Histórico preservado.');
    }

    private function form(Animal $animal): View
    {
        $parentes = Animal::withTrashed()->when($animal->exists, fn (Builder $query): Builder => $query->whereKeyNot($animal->id))
            ->orderBy('brinco')->get(['id', 'brinco', 'nome_ou_numero', 'sexo', 'deleted_at']);

        return view('rebanho::animais.form', [
            'animal' => $animal,
            'lotes' => Lote::orderBy('nome')->get(),
            'pais' => $parentes->where('sexo', 'MACHO'),
            'maes' => $parentes->where('sexo', 'FEMEA'),
        ]);
    }
}
