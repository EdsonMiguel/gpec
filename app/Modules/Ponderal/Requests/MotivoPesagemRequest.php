<?php

namespace App\Modules\Ponderal\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MotivoPesagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    public function rules(): array
    {
        $motivo = $this->route('motivo');

        return [
            'codigo' => ['required', 'string', 'max:40', Rule::unique('motivos_pesagem')->where('tenant_id', $this->user()->tenant_id)->ignore($motivo)],
            'nome' => ['required', 'string', 'max:100', Rule::unique('motivos_pesagem')->where('tenant_id', $this->user()->tenant_id)->ignore($motivo)],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'ativo' => ['required', 'boolean'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
