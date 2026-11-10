<?php

namespace App\Modules\Financeiro\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    public function rules(): array
    {
        return ['motivo_cancelamento' => ['required', 'string', 'min:3', 'max:2000']];
    }
}
