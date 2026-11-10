<?php

namespace App\Modules\Financeiro\Requests;

use App\Modules\Financeiro\Models\Produto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:50', Rule::unique('produtos')->where('tenant_id', $this->user()->tenant_id)->ignore($this->route('produto'))],
            'nome' => ['required', 'string', 'max:150'],
            'unidade' => ['required', Rule::in(Produto::UNIDADES)],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'ativo' => ['required', 'boolean'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
