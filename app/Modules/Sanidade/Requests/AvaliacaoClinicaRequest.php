<?php

namespace App\Modules\Sanidade\Requests;

use App\Modules\Sanidade\Models\AvaliacaoClinica;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvaliacaoClinicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('registrar-manejo') ?? false;
    }

    public function rules(): array
    {
        return [
            'data_avaliacao' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'veterinario' => ['required', 'string', 'max:150'],
            'sintomas' => ['nullable', 'string', 'max:5000'],
            'parecer' => ['required', 'string', 'max:5000'],
            'orientacao' => ['nullable', 'string', 'max:5000'],
            'doenca_id' => ['nullable', 'integer'],
            'situacao_diagnostico' => [
                'nullable', 'required_with:doenca_id', Rule::prohibitedIf(! $this->filled('doenca_id')),
                Rule::in(array_keys(AvaliacaoClinica::DIAGNOSTICOS)),
            ],
            'tenant_id' => ['prohibited'],
        ];
    }
}
