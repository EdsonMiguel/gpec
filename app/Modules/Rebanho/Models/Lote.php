<?php

namespace App\Modules\Rebanho\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lote extends Model
{
    use BelongsToTenant;

    protected $fillable = ['nome', 'descricao'];

    public function animais(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
