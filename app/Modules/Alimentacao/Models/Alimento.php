<?php

namespace App\Modules\Alimentacao\Models;

use App\Modules\Financeiro\Models\ItemTransacao;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alimento extends Model
{
    use BelongsToTenant;

    public const UNIDADES = ['KG', 'G', 'L', 'ML'];

    protected $fillable = ['tipo_alimento_id', 'codigo', 'nome', 'unidade', 'composicao', 'descricao', 'ativo'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoAlimento::class, 'tipo_alimento_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemRefeicao::class);
    }

    public function itensFinanceiros(): HasMany
    {
        return $this->hasMany(ItemTransacao::class);
    }

    public function scopeDisponiveis(Builder $query): Builder
    {
        return $query->where('ativo', true)->whereHas('tipo', fn (Builder $tipo): Builder => $tipo->where('ativo', true));
    }
}
