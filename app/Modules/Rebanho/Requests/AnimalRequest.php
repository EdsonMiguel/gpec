<?php

namespace App\Modules\Rebanho\Requests;

use App\Modules\Rebanho\Models\Animal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnimalRequest extends FormRequest
{
    public function after(): array
    {
        return [function (Validator $validator): void {
            $animal = $this->route('animal');
            if ($validator->errors()->isNotEmpty() || ! $animal instanceof Animal || ! $this->filled('data_nascimento')) {
                return;
            }

            $firstWeight = $animal->pesagens()->min('data_pesagem');
            $firstApplication = $animal->manejos()->min('data_aplicacao');
            $firstMeal = $animal->refeicoes()->min('data_fornecimento');
            foreach ([$firstWeight, $firstApplication, $firstMeal] as $eventDate) {
                if ($eventDate !== null && $this->date('data_nascimento')->toDateString() > substr($eventDate, 0, 10)) {
                    $validator->errors()->add('data_nascimento', 'O nascimento não pode ser posterior ao histórico de manejo.');

                    return;
                }
            }
        }];
    }

    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    public function rules(): array
    {
        $animal = $this->route('animal');

        return [
            'brinco' => ['required', 'string', 'max:50', Rule::unique('animais')->where('tenant_id', $this->user()->tenant_id)->ignore($animal)],
            'nome_ou_numero' => ['nullable', 'string', 'max:100'],
            'categoria' => ['required', Rule::in(Animal::CATEGORIAS)],
            'raca' => ['nullable', 'string', 'max:100'],
            'sexo' => ['required', Rule::in(['MACHO', 'FEMEA'])],
            'data_nascimento' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status' => ['required', Rule::in(Animal::STATUS)],
            'lote_id' => ['nullable', 'integer', Rule::exists('lotes', 'id')->where('tenant_id', $this->user()->tenant_id), Rule::prohibitedIf($this->input('status') !== 'ATIVO')],
            'tenant_id' => ['prohibited'],
            'pai_id' => ['nullable', 'integer', Rule::exists('animais', 'id')->where('tenant_id', $this->user()->tenant_id)],
            'mae_id' => ['nullable', 'integer', Rule::exists('animais', 'id')->where('tenant_id', $this->user()->tenant_id)],
        ];
    }
}
