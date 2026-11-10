<?php

namespace App\Modules\Reproducao\Actions;

use App\Modules\Auth\Models\Tenant;
use App\Modules\Rebanho\Actions\SaveAnimal;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Reproducao\Models\Gestacao;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VincularCria
{
    public function __construct(private SaveAnimal $saveAnimal) {}

    public function handle(Gestacao $gestacao, int $animalId): void
    {
        Gate::authorize('administrar');

        DB::transaction(function () use ($gestacao, $animalId): void {
            Tenant::whereKey(Auth::user()->tenant_id)->lockForUpdate()->firstOrFail();
            $ciclo = Gestacao::findOrFail($gestacao->id);
            $cria = Animal::find($animalId);

            if ($ciclo->status !== 'PARTO' || $cria === null
                || $cria->data_nascimento?->toDateString() !== $ciclo->data_encerramento?->toDateString()) {
                throw ValidationException::withMessages(['cria_id' => 'Selecione um animal desta conta com data de nascimento igual à data do parto.']);
            }
            if ($cria->gestacao_nascimento_id === $ciclo->id) {
                return;
            }
            if ($cria->gestacao_nascimento_id !== null) {
                throw ValidationException::withMessages(['cria_id' => 'O animal já está vinculado a outro parto.']);
            }
            if (($cria->mae_id !== null && $cria->mae_id !== $ciclo->matriz_id)
                || ($ciclo->reprodutor_id !== null && $cria->pai_id !== null && $cria->pai_id !== $ciclo->reprodutor_id)) {
                throw ValidationException::withMessages(['cria_id' => 'A filiação cadastrada diverge do ciclo. Revise a ficha do animal antes de vincular.']);
            }

            $cria = $this->saveAnimal->handle([
                'mae_id' => $ciclo->matriz_id,
                'pai_id' => $ciclo->reprodutor_id ?? $cria->pai_id,
            ], $cria);
            $cria->gestacaoNascimento()->associate($ciclo);
            $cria->save();
        });
    }
}
