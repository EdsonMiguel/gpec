<?php

namespace App\Modules\Alimentacao\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemRefeicao extends Model
{
    use BelongsToTenant;

    protected $table = 'itens_refeicao';

    protected $fillable = ['alimento_id', 'quantidade', 'unidade'];

    protected function casts(): array
    {
        return ['quantidade' => 'decimal:3'];
    }

    public function alimento(): BelongsTo
    {
        return $this->belongsTo(Alimento::class);
    }

    public function refeicao(): BelongsTo
    {
        return $this->belongsTo(Refeicao::class);
    }
}
