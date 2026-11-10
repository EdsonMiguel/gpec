<?php

namespace App\Modules\Sanidade\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150', Rule::unique('medicamentos')->where('tenant_id', $this->user()->tenant_id)],
            'principio_ativo' => ['nullable', 'string', 'max:150'],
            'unidade' => ['required', Rule::in(['ML', 'G', 'MG', 'DOSE'])],
            'tenant_id' => ['prohibited'],
        ];
    }
}
