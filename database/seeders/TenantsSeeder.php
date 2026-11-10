<?php

namespace Database\Seeders;

use App\Modules\Auth\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Tenant::exists()) {
            Tenant::create(['nome' => 'Conta Demonstração']);
        }
    }
}
