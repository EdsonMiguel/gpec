<?php

namespace App\Modules\Ponderal\Requests;

use App\Modules\Ponderal\Models\Pesagem;
use App\Support\Requests\AnimalEventRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PesagemRequest extends AnimalEventRequest
{
    protected function eventDateField(): string
    {
        return 'data_pesagem';
    }

    public function rules(): array
    {
        return [
            'animal_id' => ['required', 'integer', Rule::exists('animais', 'id')->where('tenant_id', $this->user()->tenant_id)->where('status', 'ATIVO')->whereNull('deleted_at')],
            'motivo_pesagem_id' => ['required', 'integer', Rule::exists('motivos_pesagem', 'id')->where('tenant_id', $this->user()->tenant_id)->where('ativo', true)],
            'peso' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'lt:100000'],
            'data_pesagem' => ['required', 'date_format:Y-m-d\TH:i', 'before_or_equal:now'],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'tenant_id' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [...parent::after(), function (Validator $validator): void {
            if ($validator->errors()->isEmpty() && Pesagem::where('animal_id', $this->integer('animal_id'))->where('data_pesagem', $this->date('data_pesagem'))->exists()) {
                $validator->errors()->add('data_pesagem', 'Já existe uma pesagem para este animal neste horário.');
            }
        }];
    }
}
