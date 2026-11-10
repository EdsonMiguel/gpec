<?php

namespace App\Modules\Financeiro\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaOrcamentaria extends Model
{
    use BelongsToTenant;

    protected $table = 'contas_orcamentarias';

    protected $fillable = ['codigo', 'nome', 'natureza', 'valor_orcado', 'periodo_inicio', 'periodo_fim', 'descricao', 'ativo'];

    protected function casts(): array
    {
        return ['valor_orcado' => 'decimal:2', 'periodo_inicio' => 'date', 'periodo_fim' => 'date', 'ativo' => 'boolean'];
    }

    public function transacoes(): HasMany
    {
        return $this->hasMany(Transacao::class);
    }
}
