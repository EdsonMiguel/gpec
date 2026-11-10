<?php

namespace App\Modules\Alimentacao\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoAlimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:40', Rule::unique('tipos_alimento')->where('tenant_id', $this->user()->tenant_id)->ignore($this->route('tipo'))],
            'nome' => ['required', 'string', 'max:100', Rule::unique('tipos_alimento')->where('tenant_id', $this->user()->tenant_id)->ignore($this->route('tipo'))],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'ativo' => ['required', 'boolean'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
