<?php

namespace App\Modules\Reproducao\Requests;

use App\Modules\Reproducao\Models\Gestacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GestacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('registrar-manejo') ?? false;
    }

    public function rules(): array
    {
        return [
            'matriz_id' => ['required', 'integer'],
            'tipo' => ['required', Rule::in(array_keys(Gestacao::TIPOS))],
            'data_cobertura' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reprodutor_id' => ['required_if:tipo,MONTA', 'nullable', 'integer'],
            'referencia_semen' => ['prohibited_if:tipo,MONTA', 'nullable', 'string', 'max:100'],
            'responsavel' => ['nullable', 'string', 'max:150'],
            'observacao' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
