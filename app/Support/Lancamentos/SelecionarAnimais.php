<?php

namespace App\Support\Lancamentos;

use App\Modules\Rebanho\Models\Animal;
use App\Modules\Rebanho\Models\Lote;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class SelecionarAnimais
{
    /** @return Collection<int, Animal> */
    public function paraLancamento(array $dados, string $campoData): Collection
    {
        $porLote = $dados['destino'] === 'LOTE';
        $query = Animal::ativos()->orderBy('id')->lockForUpdate();
        if ($porLote) {
            Lote::lockForUpdate()->findOrFail($dados['lote_id']);
            $query->where('lote_id', $dados['lote_id']);
        } else {
            $query->whereKey($dados['animal_id']);
        }
        $animais = $query->get();
        if ($animais->isEmpty()) {
            throw ValidationException::withMessages([
                $porLote ? 'lote_id' : 'animal_id' => 'Nenhum animal ativo disponível para este lançamento.',
            ]);
        }
        $data = substr($dados[$campoData], 0, 10);
        foreach ($animais as $animal) {
            if ($animal->data_nascimento && $data < $animal->data_nascimento->toDateString()) {
                throw ValidationException::withMessages([$campoData => 'A data é anterior ao nascimento do animal '.$animal->brinco.'. Revise o lançamento.']);
            }
        }

        return $animais;
    }
}
