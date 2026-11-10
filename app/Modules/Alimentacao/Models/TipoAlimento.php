<?php

namespace App\Modules\Alimentacao\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoAlimento extends Model
{
    use BelongsToTenant;

    protected $table = 'tipos_alimento';

    protected $fillable = ['codigo', 'nome', 'descricao', 'ativo'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function alimentos(): HasMany
    {
        return $this->hasMany(Alimento::class);
    }
}
