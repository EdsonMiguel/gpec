<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = ['nome', 'documento'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
