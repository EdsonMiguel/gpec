<?php

namespace App\Modules\Financeiro\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContaOrcamentariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:50', Rule::unique('contas_orcamentarias')->where('tenant_id', $this->user()->tenant_id)->ignore($this->route('conta'))],
            'nome' => ['required', 'string', 'max:150'],
            'natureza' => ['required', Rule::in(['RECEITA', 'DESPESA'])],
            'valor_orcado' => ['required', 'numeric', 'decimal:0,2', 'gte:0', 'lt:1000000000000'],
            'periodo_inicio' => ['required', 'date_format:Y-m-d'],
            'periodo_fim' => ['required', 'date_format:Y-m-d', 'after_or_equal:periodo_inicio'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'ativo' => ['required', 'boolean'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
