<?php

namespace App\Modules\Financeiro\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    use BelongsToTenant;

    public const UNIDADES = ['UN', 'KG', 'G', 'L', 'ML', 'DOSE'];

    protected $fillable = ['codigo', 'nome', 'unidade', 'descricao', 'ativo'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemTransacao::class);
    }
}
