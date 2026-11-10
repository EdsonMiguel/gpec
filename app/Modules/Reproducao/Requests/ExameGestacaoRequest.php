<?php

namespace App\Modules\Reproducao\Requests;

use App\Modules\Reproducao\Models\ExameGestacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExameGestacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('registrar-manejo') ?? false;
    }

    public function rules(): array
    {
        return [
            'data_exame' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'metodo' => ['required', 'string', 'max:100'],
            'resultado' => ['required', Rule::in(array_keys(ExameGestacao::RESULTADOS))],
            'responsavel' => ['nullable', 'string', 'max:150'],
            'observacao' => ['nullable', 'string', 'max:5000'],
            'data_prevista_parto' => [Rule::prohibitedIf($this->input('resultado') !== 'POSITIVO'), 'nullable', 'date_format:Y-m-d', 'after_or_equal:data_exame'],
        ];
    }
}
