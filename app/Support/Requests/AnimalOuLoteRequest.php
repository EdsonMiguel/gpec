<?php

namespace App\Support\Requests;

use Illuminate\Validation\Rule;

abstract class AnimalOuLoteRequest extends AnimalEventRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['destino' => $this->input('destino', 'ANIMAL')]);
        if ($this->input('destino') === 'ANIMAL') {
            parent::prepareForValidation();
        }
    }

    protected function destinatarioRules(): array
    {
        $porLote = $this->input('destino') === 'LOTE';

        return [
            'destino' => ['required', Rule::in(['ANIMAL', 'LOTE'])],
            'animal_id' => [
                Rule::requiredIf(! $porLote), Rule::prohibitedIf($porLote), 'nullable', 'integer',
                Rule::exists('animais', 'id')->where('tenant_id', $this->user()->tenant_id)->where('status', 'ATIVO')->whereNull('deleted_at'),
            ],
            'brinco' => [Rule::prohibitedIf($porLote), 'nullable', 'string', 'max:50'],
            'lote_id' => [
                Rule::requiredIf($porLote), Rule::prohibitedIf(! $porLote), 'nullable', 'integer',
                Rule::exists('lotes', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'chave_lancamento' => ['required_if:destino,LOTE', 'nullable', 'uuid'],
        ];
    }

    public function after(): array
    {
        return $this->input('destino') === 'LOTE' ? [] : parent::after();
    }
}
