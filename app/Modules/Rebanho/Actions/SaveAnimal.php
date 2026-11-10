<?php

namespace App\Modules\Rebanho\Actions;

use App\Modules\Auth\Models\Tenant;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Reproducao\Models\Gestacao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SaveAnimal
{
    public function handle(array $attributes, ?Animal $animal = null): Animal
    {
        Gate::authorize('administrar');

        return DB::transaction(function () use ($attributes, $animal): Animal {
            /** Serializa alterações genealógicas da conta para impedir ciclos por gravações concorrentes. */
            Tenant::whereKey(Auth::user()->tenant_id)->lockForUpdate()->firstOrFail();
            $record = $animal === null ? new Animal : Animal::findOrFail($animal->id);
            $record->fill($attributes);
            $this->validateParent($record, 'pai_id', 'MACHO');
            $this->validateParent($record, 'mae_id', 'FEMEA');
            $this->validateChildren($record);
            $this->validateReproductiveHistory($record);
            if ($record->exists && $record->isDirty('data_nascimento') && $record->data_nascimento !== null
                && $record->casosClinicos()->whereDate('data_inicio', '<', $record->data_nascimento)->exists()) {
                throw ValidationException::withMessages(['data_nascimento' => 'O nascimento não pode ser posterior ao início de um caso clínico registrado.']);
            }
            $record->save();

            return $record;
        });
    }

    private function validateParent(Animal $animal, string $field, string $sexo): void
    {
        if ($animal->{$field} === null) {
            return;
        }

        $parent = Animal::withTrashed()->find($animal->{$field});
        if ($parent === null) {
            throw ValidationException::withMessages([$field => 'O animal selecionado não está disponível nesta conta.']);
        }
        if ($animal->exists && (int) $parent->id === (int) $animal->id) {
            throw ValidationException::withMessages([$field => 'O animal não pode ser pai ou mãe de si mesmo.']);
        }
        if ($parent->sexo !== $sexo) {
            throw ValidationException::withMessages([$field => $field === 'pai_id' ? 'Selecione um macho como pai.' : 'Selecione uma fêmea como mãe.']);
        }
        if ($parent->data_nascimento && $animal->data_nascimento && $parent->data_nascimento->gte($animal->data_nascimento)) {
            throw ValidationException::withMessages([$field => 'O pai ou a mãe deve ter nascido antes do animal.']);
        }
        if ($animal->exists && $this->hasAncestor($parent, $animal->id)) {
            throw ValidationException::withMessages([$field => 'Este vínculo criaria um ciclo na genealogia. Selecione um animal que não seja descendente deste.']);
        }
    }

    private function hasAncestor(Animal $parent, int $animalId): bool
    {
        $pending = [$parent->id];
        $visited = [];

        while ($pending !== []) {
            if (in_array($animalId, $pending, true)) {
                return true;
            }
            $visited = array_merge($visited, $pending);
            $ancestors = Animal::withTrashed()->whereIn('id', $pending)->get(['id', 'pai_id', 'mae_id']);
            $pending = [];
            foreach ($ancestors as $ancestor) {
                foreach ([$ancestor->pai_id, $ancestor->mae_id] as $id) {
                    if ($id !== null && ! in_array($id, $visited, true)) {
                        $pending[] = $id;
                    }
                }
            }
            $pending = array_values(array_unique($pending));
        }

        return false;
    }

    private function validateChildren(Animal $animal): void
    {
        if (! $animal->exists) {
            return;
        }

        if ($animal->isDirty('sexo')) {
            $field = $animal->sexo === 'MACHO' ? 'mae_id' : 'pai_id';
            if (Animal::withTrashed()->where($field, $animal->id)->exists()) {
                throw ValidationException::withMessages(['sexo' => 'O sexo não pode ser alterado porque o animal já está vinculado como pai ou mãe.']);
            }
        }

        if ($animal->isDirty('data_nascimento') && $animal->data_nascimento !== null
            && Animal::withTrashed()->where(fn (Builder $query): Builder => $query->where('pai_id', $animal->id)->orWhere('mae_id', $animal->id))
                ->whereNotNull('data_nascimento')->whereDate('data_nascimento', '<=', $animal->data_nascimento)->exists()) {
            throw ValidationException::withMessages(['data_nascimento' => 'O nascimento deve ser anterior ao nascimento dos filhos já cadastrados.']);
        }
    }

    private function validateReproductiveHistory(Animal $animal): void
    {
        if (! $animal->exists || ! $animal->isDirty(['sexo', 'data_nascimento'])) {
            return;
        }

        $ciclos = Gestacao::where(fn (Builder $query): Builder => $query->where('matriz_id', $animal->id)->orWhere('reprodutor_id', $animal->id));
        if ($animal->isDirty('sexo') && (clone $ciclos)->exists()) {
            throw ValidationException::withMessages(['sexo' => 'O animal já tem histórico como matriz ou reprodutor.']);
        }
        if ($animal->isDirty('data_nascimento')) {
            if ($animal->gestacao_nascimento_id !== null
                && $animal->data_nascimento?->toDateString() !== $animal->gestacaoNascimento->data_encerramento->toDateString()) {
                throw ValidationException::withMessages(['data_nascimento' => 'Mantenha a data de nascimento do parto vinculado.']);
            }
            if ($animal->data_nascimento !== null && $ciclos->whereDate('data_cobertura', '<=', $animal->data_nascimento)->exists()) {
                throw ValidationException::withMessages(['data_nascimento' => 'O nascimento deve ser anterior à primeira cobertura registrada.']);
            }
        }
    }
}
