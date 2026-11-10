<?php

namespace App\Modules\Financeiro\Models;

use App\Modules\Alimentacao\Models\Alimento;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Sanidade\Models\Medicamento;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemTransacao extends Model
{
    use BelongsToTenant;

    public const TIPOS = ['ALIMENTO' => 'Alimento', 'MEDICAMENTO' => 'Medicamento', 'ANIMAL' => 'Animal', 'PRODUTO' => 'Outro produto cadastrado', 'OUTRO' => 'Outro / descrição livre'];

    protected $table = 'itens_transacao';

    protected $guarded = ['id', 'tenant_id', 'transacao_id'];

    protected function casts(): array
    {
        return ['quantidade' => 'decimal:3', 'valor_unitario' => 'decimal:2', 'valor_total' => 'decimal:2'];
    }

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class);
    }

    public function alimento(): BelongsTo
    {
        return $this->belongsTo(Alimento::class);
    }

    public function medicamento(): BelongsTo
    {
        return $this->belongsTo(Medicamento::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class)->withTrashed();
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
