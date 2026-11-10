<?php

namespace App\Modules\Reproducao\Actions;

use App\Modules\Auth\Models\Tenant;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Reproducao\Models\Gestacao;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AcompanharGestacao
{
    public function iniciar(array $dados): Gestacao
    {
        Gate::authorize('registrar-manejo');

        return DB::transaction(function () use ($dados): Gestacao {
            $this->bloquearConta();
            $matriz = Animal::ativos()->where('sexo', 'FEMEA')->find($dados['matriz_id']);
            if ($matriz === null) {
                throw ValidationException::withMessages(['matriz_id' => 'Selecione uma fêmea ativa desta conta.']);
            }
            $this->validarNascimento($matriz, $dados['data_cobertura']);

            if (Gestacao::where('matriz_id', $matriz->id)->whereIn('status', Gestacao::ABERTOS)->exists()) {
                throw ValidationException::withMessages(['matriz_id' => 'A matriz já tem um ciclo aberto. Registre o resultado ou encerre o ciclo anterior.']);
            }
            if (Gestacao::where('matriz_id', $matriz->id)->whereDate('data_encerramento', '>', $dados['data_cobertura'])->exists()) {
                throw ValidationException::withMessages(['data_cobertura' => 'A cobertura não pode ser anterior ao encerramento de outro ciclo da matriz.']);
            }

            if (isset($dados['reprodutor_id'])) {
                $machos = $dados['tipo'] === 'MONTA' ? Animal::ativos() : Animal::withTrashed();
                $reprodutor = $machos->where('sexo', 'MACHO')->find($dados['reprodutor_id']);
                if ($reprodutor === null) {
                    throw ValidationException::withMessages(['reprodutor_id' => 'Selecione um reprodutor válido desta conta.']);
                }
                $this->validarNascimento($reprodutor, $dados['data_cobertura']);
            }

            return Gestacao::create([...$dados, 'status' => 'AGUARDANDO_EXAME']);
        });
    }

    public function registrarExame(Gestacao $gestacao, array $dados): void
    {
        Gate::authorize('registrar-manejo');

        DB::transaction(function () use ($gestacao, $dados): void {
            $this->bloquearConta();
            $ciclo = Gestacao::findOrFail($gestacao->id);
            $this->validarEvento($ciclo, 'data_exame', $dados['data_exame']);

            $ciclo->exames()->create(Arr::except($dados, ['data_prevista_parto']));

            if ($dados['resultado'] === 'POSITIVO') {
                $ciclo->update([
                    'status' => 'CONFIRMADA',
                    'data_confirmacao' => $ciclo->data_confirmacao ?? $dados['data_exame'],
                    'data_prevista_parto' => $dados['data_prevista_parto'] ?? $ciclo->data_prevista_parto,
                ]);
            } elseif ($dados['resultado'] === 'NEGATIVO' && $ciclo->status === 'AGUARDANDO_EXAME') {
                $ciclo->update(['status' => 'SEM_PRENHEZ', 'data_encerramento' => $dados['data_exame']]);
            }
        });
    }

    public function encerrar(Gestacao $gestacao, array $dados): void
    {
        Gate::authorize('registrar-manejo');

        DB::transaction(function () use ($gestacao, $dados): void {
            $this->bloquearConta();
            $ciclo = Gestacao::findOrFail($gestacao->id);
            $this->validarEvento($ciclo, 'data_encerramento', $dados['data_encerramento']);

            $permitidos = $ciclo->status === 'CONFIRMADA' ? ['PARTO', 'PERDA'] : ['ENCERRADA'];
            if (! in_array($dados['status'], $permitidos, true)) {
                throw ValidationException::withMessages(['status' => 'Selecione um desfecho compatível com a situação do ciclo.']);
            }

            $ciclo->update($dados);
        });
    }

    private function bloquearConta(): void
    {
        Tenant::whereKey(Auth::user()->tenant_id)->lockForUpdate()->firstOrFail();
    }

    private function validarNascimento(Animal $animal, string $data): void
    {
        if ($animal->data_nascimento !== null && $data <= $animal->data_nascimento->toDateString()) {
            throw ValidationException::withMessages(['data_cobertura' => 'A cobertura deve ser posterior ao nascimento da matriz e do reprodutor.']);
        }
    }

    private function validarEvento(Gestacao $gestacao, string $campo, string $data): void
    {
        if (! $gestacao->estaAberta()) {
            throw ValidationException::withMessages([$campo => 'O ciclo já está encerrado.']);
        }
        $ultimoExame = $gestacao->exames()->orderByDesc('data_exame')->first();
        $dataMinima = ($ultimoExame?->data_exame ?? $gestacao->data_cobertura)->toDateString();
        if ($data < $dataMinima) {
            throw ValidationException::withMessages([$campo => 'A data deve ser igual ou posterior ao último evento registrado.']);
        }
    }
}
