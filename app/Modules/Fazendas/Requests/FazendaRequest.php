<?php

namespace App\Modules\Fazendas\Requests;

use App\Modules\Fazendas\Models\Fazenda;
use App\Modules\Fazendas\Rules\GeoJsonArea;
use Illuminate\Validation\Rule;

class FazendaRequest extends GeoJsonRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    public function rules(): array
    {
        $fazenda = $this->route('fazenda');

        return [
            'nome' => ['required', 'string', 'max:150', Rule::unique('fazendas')->where('tenant_id', $this->user()->tenant_id)->ignore($fazenda)],
            'codigo' => ['nullable', 'string', 'max:50', Rule::unique('fazendas')->where('tenant_id', $this->user()->tenant_id)->ignore($fazenda)],
            'municipio' => ['nullable', 'string', 'max:150'],
            'uf' => ['nullable', Rule::in(Fazenda::UFS)],
            'area_total_ha' => ['nullable', 'numeric', 'decimal:0,2', 'gte:0', 'lt:10000000000'],
            'observacao' => ['nullable', 'string', 'max:5000'],
            'ativo' => ['required', 'boolean'],
            'geojson' => ['nullable', new GeoJsonArea],
            'tenant_id' => ['prohibited'],
        ];
    }
}
