<?php

namespace App\Modules\Sanidade\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DoencaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150', Rule::unique('doencas')->where('tenant_id', $this->user()->tenant_id)->ignore($this->route('doenca'))],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'ativo' => ['required', 'boolean'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
