<?php

namespace App\Modules\Rebanho\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100', Rule::unique('lotes')->where('tenant_id', $this->user()->tenant_id)->ignore($this->route('lote'))],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
