<?php

namespace App\Modules\Sanidade\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Doenca extends Model
{
    use BelongsToTenant;

    protected $table = 'doencas';

    protected $fillable = ['nome', 'descricao', 'ativo'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }
}
