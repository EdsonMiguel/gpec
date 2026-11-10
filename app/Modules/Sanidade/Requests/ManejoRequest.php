<?php

namespace App\Modules\Sanidade\Requests;

use App\Support\Requests\AnimalOuLoteRequest;
use Illuminate\Validation\Rule;

class ManejoRequest extends AnimalOuLoteRequest
{
    protected function eventDateField(): string
    {
        return 'data_aplicacao';
    }

    public function rules(): array
    {
        return [
            ...$this->destinatarioRules(),
            'medicamento_id' => ['required', 'integer', Rule::exists('medicamentos', 'id')->where('tenant_id', $this->user()->tenant_id)->where('ativo', true)],
            'tipo' => ['required', Rule::in(['VACINA', 'VERMIFUGO', 'TRATAMENTO', 'OUTRO'])],
            'dosagem' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'lt:10000000'],
            'data_aplicacao' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'proxima_aplicacao' => ['nullable', 'date_format:Y-m-d', 'after:data_aplicacao'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
