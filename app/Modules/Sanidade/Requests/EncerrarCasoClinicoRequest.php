<?php

namespace App\Modules\Sanidade\Requests;

use App\Modules\Sanidade\Models\CasoClinico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EncerrarCasoClinicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('registrar-manejo') ?? false;
    }

    public function rules(): array
    {
        return [
            'data_encerramento' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'desfecho' => ['required', Rule::in(array_keys(CasoClinico::DESFECHOS))],
            'observacao_encerramento' => ['required', 'string', 'max:5000'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
