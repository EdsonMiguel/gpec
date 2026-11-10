<?php

namespace App\Modules\Reproducao\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EncerrarGestacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('registrar-manejo') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['ENCERRADA', 'PERDA', 'PARTO'])],
            'data_encerramento' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'observacao_encerramento' => ['required_if:status,PERDA,ENCERRADA', 'nullable', 'string', 'max:5000'],
        ];
    }
}
