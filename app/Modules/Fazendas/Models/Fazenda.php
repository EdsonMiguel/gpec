<?php

namespace App\Modules\Fazendas\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fazenda extends Model
{
    use BelongsToTenant;

    public const UFS = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

    protected $fillable = ['nome', 'codigo', 'municipio', 'uf', 'area_total_ha', 'observacao', 'ativo', 'geojson'];

    protected function casts(): array
    {
        return ['area_total_ha' => 'decimal:2', 'ativo' => 'boolean', 'geojson' => 'object'];
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }
}
