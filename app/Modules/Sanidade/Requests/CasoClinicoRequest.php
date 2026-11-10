<?php

namespace App\Modules\Sanidade\Requests;

use App\Support\Requests\AnimalEventRequest;
use Illuminate\Validation\Rule;

class CasoClinicoRequest extends AnimalEventRequest
{
    protected function eventDateField(): string
    {
        return 'data_inicio';
    }

    public function rules(): array
    {
        return [
            'animal_id' => ['required', 'integer', Rule::exists('animais', 'id')->where('tenant_id', $this->user()->tenant_id)->where('status', 'ATIVO')->whereNull('deleted_at')],
            'titulo' => ['required', 'string', 'max:150'],
            'data_inicio' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'sintomas_iniciais' => ['required', 'string', 'max:5000'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
