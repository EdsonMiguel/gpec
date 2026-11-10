<?php

namespace App\Modules\Fazendas\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Area extends Model
{
    use BelongsToTenant;

    public const TIPOS = [
        'PASTO' => 'Pasto', 'PIQUETE' => 'Piquete', 'CURRAL' => 'Curral',
        'CONFINAMENTO' => 'Confinamento', 'BAIA' => 'Baia', 'RECEPCAO' => 'Recepção',
        'EMBARQUE' => 'Embarque', 'OUTRO' => 'Outro',
    ];

    protected $fillable = ['nome', 'codigo', 'tipo', 'area_ha', 'descricao', 'ativo', 'geojson'];

    protected function casts(): array
    {
        return ['area_ha' => 'decimal:2', 'ativo' => 'boolean', 'geojson' => 'object'];
    }

    public function fazenda(): BelongsTo
    {
        return $this->belongsTo(Fazenda::class);
    }
}
