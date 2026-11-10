<?php

namespace App\Modules\Ponderal\Models;

use App\Modules\Rebanho\Models\Animal;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pesagem extends Model
{
    use BelongsToTenant;

    protected $table = 'pesagens';

    protected $fillable = ['animal_id', 'motivo_pesagem_id', 'peso', 'data_pesagem', 'observacao'];

    protected function casts(): array
    {
        return ['peso' => 'decimal:2', 'data_pesagem' => 'datetime'];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class)->withTrashed();
    }

    public function motivo(): BelongsTo
    {
        return $this->belongsTo(MotivoPesagem::class, 'motivo_pesagem_id');
    }
}
