<?php

namespace App\Modules\Alimentacao\Requests;

use App\Modules\Alimentacao\Models\Alimento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'tipo_alimento_id' => ['required', 'integer', Rule::exists('tipos_alimento', 'id')->where('tenant_id', $this->user()->tenant_id)],
            'codigo' => ['required', 'string', 'max:50', Rule::unique('alimentos')->where('tenant_id', $this->user()->tenant_id)->ignore($this->route('alimento'))],
            'nome' => ['required', 'string', 'max:150'],
            'unidade' => ['required', Rule::in(Alimento::UNIDADES)],
            'composicao' => ['nullable', 'string', 'max:5000'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'ativo' => ['required', 'boolean'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
