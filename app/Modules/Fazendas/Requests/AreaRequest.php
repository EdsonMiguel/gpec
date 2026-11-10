<?php

namespace App\Modules\Fazendas\Requests;

use App\Modules\Fazendas\Models\Area;
use App\Modules\Fazendas\Rules\GeoJsonArea;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AreaRequest extends GeoJsonRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    public function rules(): array
    {
        $fazenda = $this->route('fazenda');
        $area = $this->route('area');

        return [
            'nome' => ['required', 'string', 'max:100', Rule::unique('areas')->where('tenant_id', $this->user()->tenant_id)->where('fazenda_id', $fazenda->id)->ignore($area)],
            'codigo' => ['nullable', 'string', 'max:50', Rule::unique('areas')->where('tenant_id', $this->user()->tenant_id)->where('fazenda_id', $fazenda->id)->ignore($area)],
            'tipo' => ['required', Rule::in(array_keys(Area::TIPOS))],
            'area_ha' => ['nullable', 'numeric', 'decimal:0,2', 'gte:0', 'lt:10000000000'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'ativo' => ['required', 'boolean'],
            'geojson' => ['nullable', new GeoJsonArea],
            'tenant_id' => ['prohibited'],
            'fazenda_id' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->route('area') && ! $this->route('fazenda')->ativo) {
                $validator->errors()->add('fazenda', 'Reative a fazenda antes de cadastrar novas áreas.');
            }
        }];
    }
}
