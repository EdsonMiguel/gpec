<?php

namespace App\Support\Requests;

use App\Modules\Rebanho\Models\Animal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class AnimalEventRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('brinco') && is_string($this->input('brinco'))) {
            $this->merge(['animal_id' => Animal::ativos()->where('brinco', trim($this->input('brinco')))->value('id')]);
        }
    }

    abstract protected function eventDateField(): string;

    public function authorize(): bool
    {
        return $this->user()?->can('registrar-manejo') ?? false;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $animal = Animal::find($this->integer('animal_id'));
            $field = $this->eventDateField();
            if ($animal?->data_nascimento && $this->date($field)->startOfDay()->lt($animal->data_nascimento)) {
                $validator->errors()->add($field, 'A data deve ser igual ou posterior ao nascimento.');
            }
        }];
    }
}
