<?php

namespace App\Modules\Alimentacao\Requests;

use App\Modules\Alimentacao\Models\Refeicao;
use App\Support\Requests\AnimalOuLoteRequest;
use Illuminate\Validation\Rule;

class RefeicaoRequest extends AnimalOuLoteRequest
{
    protected function eventDateField(): string
    {
        return 'data_fornecimento';
    }

    public function rules(): array
    {
        return [
            ...$this->destinatarioRules(),
            'data_fornecimento' => ['required', 'date_format:Y-m-d\TH:i', 'before_or_equal:now'],
            'turno' => ['required', Rule::in(array_keys(Refeicao::TURNOS))],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'itens' => ['required', 'array', 'min:1', 'max:20'],
            'itens.*' => ['required', 'array:alimento_id,quantidade'],
            'itens.*.alimento_id' => ['required', 'integer', 'distinct', Rule::exists('alimentos', 'id')->where('tenant_id', $this->user()->tenant_id)->where('ativo', true)],
            'itens.*.quantidade' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'lt:1000000000'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
