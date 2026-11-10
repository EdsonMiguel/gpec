<?php

namespace App\Modules\Sanidade\Actions;

use App\Modules\Auth\Models\Tenant;
use App\Modules\Sanidade\Models\ManejoSanitario;
use App\Modules\Sanidade\Models\Medicamento;
use App\Support\Lancamentos\SelecionarAnimais;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RegistrarAplicacao
{
    public function __construct(private SelecionarAnimais $selecao) {}

    /** @return Collection<int, ManejoSanitario> */
    public function handle(array $dados): Collection
    {
        Gate::authorize('registrar-manejo');

        return DB::transaction(function () use ($dados): Collection {
            Tenant::whereKey(Auth::user()->tenant_id)->lockForUpdate()->firstOrFail();
            $chave = $dados['chave_lancamento'] ?? null;
            $hash = hash('sha256', json_encode(Arr::except($dados, ['chave_lancamento', 'brinco']), JSON_THROW_ON_ERROR));
            if ($chave !== null) {
                $existentes = ManejoSanitario::where('chave_lancamento', $chave)->get();
                if ($existentes->isNotEmpty()) {
                    if ($existentes->contains(fn (ManejoSanitario $manejo): bool => $manejo->hash_lancamento !== $hash)) {
                        throw ValidationException::withMessages(['chave_lancamento' => 'Este formulário já foi enviado com outros dados. Abra um novo lançamento.']);
                    }

                    return $existentes;
                }
            }

            $animais = $this->selecao->paraLancamento($dados, 'data_aplicacao');
            $medicamento = Medicamento::where('ativo', true)->lockForUpdate()->find($dados['medicamento_id']);
            if ($medicamento === null) {
                throw ValidationException::withMessages(['medicamento_id' => 'Selecione um medicamento ativo desta conta.']);
            }
            $registros = new Collection;
            foreach ($animais as $animal) {
                $registros->push($animal->manejos()->create([
                    ...Arr::only($dados, ['medicamento_id', 'tipo', 'dosagem', 'data_aplicacao', 'proxima_aplicacao']),
                    'unidade' => $medicamento->unidade,
                    'chave_lancamento' => $chave, 'hash_lancamento' => $chave ? $hash : null,
                ]));
            }

            return $registros;
        });
    }
}
