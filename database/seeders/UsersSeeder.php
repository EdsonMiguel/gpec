<?php

namespace Database\Seeders;

use App\Modules\Auth\Models\Tenant;
use App\Modules\Auth\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(Tenant $tenant): void
    {
        if (! $tenant->users()->where('role', 'Admin')->exists()) {
            $this->createUser($tenant, 'Administrador de demonstração', "admin.{$tenant->id}@campo.test", 'Admin');
        }

        $email = "vaqueiro.{$tenant->id}@campo.test";
        if (! $tenant->users()->where('email', $email)->exists()) {
            $this->createUser($tenant, 'Vaqueiro de demonstração', $email, 'Vaqueiro');
        }
    }

    private function createUser(Tenant $tenant, string $name, string $email, string $role): void
    {
        $user = new User(['name' => $name, 'email' => $email, 'password' => config('demo.password')]);
        $user->forceFill(['tenant_id' => $tenant->id, 'role' => $role])->save();
    }
}
