<?php

namespace App\Modules\Ponderal\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MotivoPesagem extends Model
{
    use BelongsToTenant;

    protected $table = 'motivos_pesagem';

    protected $fillable = ['codigo', 'nome', 'descricao', 'ativo'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function pesagens(): HasMany
    {
        return $this->hasMany(Pesagem::class, 'motivo_pesagem_id');
    }
}
