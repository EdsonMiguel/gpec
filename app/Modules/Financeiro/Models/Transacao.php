<?php

namespace App\Modules\Financeiro\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transacao extends Model
{
    use BelongsToTenant;

    public const FORMAS = ['PIX' => 'Pix', 'DINHEIRO' => 'Dinheiro', 'CARTAO' => 'Cartão', 'BOLETO' => 'Boleto', 'TRANSFERENCIA' => 'Transferência', 'OUTRO' => 'Outro'];

    protected $table = 'transacoes';

    protected $guarded = ['id', 'tenant_id'];

    protected function casts(): array
    {
        return ['valor_total' => 'decimal:2', 'data_transacao' => 'date', 'data_vencimento' => 'date', 'data_efetivacao' => 'date', 'data_cancelamento' => 'date'];
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(ContaOrcamentaria::class, 'conta_orcamentaria_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemTransacao::class);
    }
}
