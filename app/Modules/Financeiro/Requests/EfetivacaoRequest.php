<?php

namespace App\Modules\Financeiro\Requests;

use App\Modules\Financeiro\Models\Transacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EfetivacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'data_efetivacao' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.$this->route('transacao')->data_transacao->toDateString()],
            'forma_pagamento' => ['required', Rule::in(array_keys(Transacao::FORMAS))],
        ];
    }
}
