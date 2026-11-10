<?php

namespace App\Modules\Sanidade\Actions;

use App\Modules\Auth\Models\Tenant;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Sanidade\Models\CasoClinico;
use App\Modules\Sanidade\Models\Doenca;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AcompanharCasoClinico
{
    public function abrir(array $dados): CasoClinico
    {
        Gate::authorize('registrar-manejo');

        return DB::transaction(function () use ($dados): CasoClinico {
            Tenant::whereKey(Auth::user()->tenant_id)->lockForUpdate()->firstOrFail();
            $animal = Animal::ativos()->lockForUpdate()->find($dados['animal_id']);
            if ($animal === null) {
                throw ValidationException::withMessages(['animal_id' => 'Selecione um animal ativo desta fazenda.']);
            }
            if ($animal->data_nascimento !== null && $dados['data_inicio'] < $animal->data_nascimento->toDateString()) {
                throw ValidationException::withMessages(['data_inicio' => 'O início dos sintomas deve ser na data de nascimento ou depois dela.']);
            }

            return $animal->casosClinicos()->create($dados);
        });
    }

    public function avaliar(CasoClinico $caso, array $dados): void
    {
        Gate::authorize('registrar-manejo');

        DB::transaction(function () use ($caso, $dados): void {
            $registro = $this->bloquearCasoAtivo($caso);
            if ($dados['data_avaliacao'] < $registro->data_inicio->toDateString()) {
                throw ValidationException::withMessages(['data_avaliacao' => 'A avaliação não pode ser anterior ao início dos sintomas.']);
            }
            if (isset($dados['doenca_id']) && Doenca::where('ativo', true)->lockForUpdate()->find($dados['doenca_id']) === null) {
                throw ValidationException::withMessages(['doenca_id' => 'Selecione uma doença ativa cadastrada nesta fazenda.']);
            }

            $registro->avaliacoes()->create($dados);
            $registro->update(['status' => 'EM_ACOMPANHAMENTO']);
        });
    }

    public function encerrar(CasoClinico $caso, array $dados): void
    {
        Gate::authorize('registrar-manejo');

        DB::transaction(function () use ($caso, $dados): void {
            $registro = $this->bloquearCasoAtivo($caso);
            if ($dados['data_encerramento'] < $registro->data_inicio->toDateString()
                || $registro->avaliacoes()->whereDate('data_avaliacao', '>', $dados['data_encerramento'])->exists()) {
                throw ValidationException::withMessages(['data_encerramento' => 'O encerramento deve ser na data do último evento ou depois dela.']);
            }

            $registro->update([...$dados, 'status' => 'ENCERRADO']);
        });
    }

    private function bloquearCasoAtivo(CasoClinico $caso): CasoClinico
    {
        $registro = CasoClinico::lockForUpdate()->findOrFail($caso->id);
        if (! $registro->estaAtivo()) {
            throw ValidationException::withMessages(['caso' => 'O caso clínico já foi encerrado.']);
        }

        return $registro;
    }
}
