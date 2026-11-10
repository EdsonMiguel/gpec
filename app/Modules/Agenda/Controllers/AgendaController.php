<?php

namespace App\Modules\Agenda\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agenda\Models\Compromisso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function index(Request $request): View
    {
        $origens = $request->user()->isAdmin()
            ? Compromisso::ORIGENS
            : Arr::except(Compromisso::ORIGENS, [Compromisso::FINANCEIRO]);
        $filtros = $request->validate([
            'status' => ['nullable', Rule::in([...array_keys(Compromisso::STATUS), 'TODOS'])],
            'origem' => ['nullable', Rule::in(array_keys($origens))],
        ]);
        $status = $filtros['status'] ?? 'PENDENTE';
        $origem = $filtros['origem'] ?? null;
        $compromissos = Compromisso::visiveis($request->user())
            ->when($status !== 'TODOS', fn (Builder $query): Builder => $query->where('status', $status))
            ->when($origem, fn (Builder $query): Builder => $query->where('origem_type', $origem))
            ->orderBy('data_prevista')->orderBy('id')->paginate(20)->withQueryString();

        return view('agenda::index', compact('compromissos', 'origens', 'status', 'origem'));
    }

    public function atualizar(Request $request, int $compromisso): RedirectResponse
    {
        $dados = $request->validate([
            'status' => ['required', Rule::in(['CONCLUIDO', 'CANCELADO'])],
            'retorno' => ['nullable', Rule::in(['dashboard', 'agenda.index'])],
        ]);

        DB::transaction(function () use ($request, $compromisso, $dados): void {
            $registro = Compromisso::visiveis($request->user())->lockForUpdate()->findOrFail($compromisso);
            if ($registro->status === $dados['status']) {
                return;
            }
            if ($registro->status !== 'PENDENTE') {
                throw ValidationException::withMessages(['status' => 'Apenas compromissos pendentes podem mudar de situação.']);
            }
            $registro->update(['status' => $dados['status']]);
        });

        return to_route($dados['retorno'] ?? 'agenda.index')->with('success', 'Situação do compromisso atualizada.');
    }

    public function excluir(Request $request, int $compromisso): RedirectResponse
    {
        $dados = $request->validate(['retorno' => ['nullable', Rule::in(['dashboard', 'agenda.index'])]]);

        DB::transaction(function () use ($request, $compromisso): void {
            Compromisso::visiveis($request->user())->lockForUpdate()->findOrFail($compromisso)->delete();
        });

        return to_route($dados['retorno'] ?? 'agenda.index')->with('success', 'Compromisso excluído da agenda.');
    }
}
