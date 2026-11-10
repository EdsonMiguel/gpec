<?php

namespace App\Modules\Sanidade\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicamento extends Model
{
    use BelongsToTenant;

    protected $fillable = ['nome', 'principio_ativo', 'unidade', 'ativo'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function manejos(): HasMany
    {
        return $this->hasMany(ManejoSanitario::class);
    }
}
