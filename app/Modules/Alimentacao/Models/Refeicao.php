<?php

namespace App\Modules\Alimentacao\Models;

use App\Modules\Rebanho\Models\Animal;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Refeicao extends Model
{
    use BelongsToTenant;

    public const TURNOS = ['MANHA' => 'Manhã', 'TARDE' => 'Tarde', 'NOITE' => 'Noite', 'OUTRO' => 'Outro'];

    protected $table = 'refeicoes';

    protected $fillable = ['animal_id', 'data_fornecimento', 'turno', 'observacao', 'chave_lancamento', 'hash_lancamento'];

    protected function casts(): array
    {
        return ['data_fornecimento' => 'datetime'];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class)->withTrashed();
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemRefeicao::class);
    }
}
