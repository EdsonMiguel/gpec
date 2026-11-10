<?php

namespace App\Console\Commands;

use App\Modules\Auth\Models\Tenant;
use App\Modules\Auth\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateFarmAdmin extends Command
{
    protected $signature = 'pecuaria:admin {fazenda : Nome da fazenda} {email : E-mail único do administrador} {--nome=Administrador} {--documento=}';

    protected $description = 'Cria uma fazenda e seu administrador, solicitando a senha de forma oculta';

    public function handle(): int
    {
        $data = [
            'fazenda' => $this->argument('fazenda'),
            'email' => $this->argument('email'),
            'nome' => $this->option('nome'),
            'documento' => $this->option('documento'),
            'password' => $this->secret('Senha do administrador (mínimo de 12 caracteres)'),
            'password_confirmation' => $this->secret('Confirme a senha'),
        ];
        $validator = Validator::make($data, [
            'fazenda' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nome' => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:30', 'unique:tenants,documento'],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        DB::transaction(function () use ($data): void {
            $tenant = Tenant::create(['nome' => $data['fazenda'], 'documento' => $data['documento']]);
            $user = new User(['name' => $data['nome'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->forceFill(['tenant_id' => $tenant->id, 'role' => 'Admin'])->save();
        });
        $this->info('Fazenda e administrador criados. Acesse /login.');

        return self::SUCCESS;
    }
}
