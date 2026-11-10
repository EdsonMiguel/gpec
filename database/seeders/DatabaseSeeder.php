<?php

namespace Database\Seeders;

use App\Modules\Auth\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Os seeds de demonstração só podem ser executados em ambiente local ou de testes.');
        }

        $guard = Auth::guard('web');
        $previousUser = $guard->user();

        try {
            DB::transaction(function () use ($guard): void {
                $this->call(TenantsSeeder::class);

                foreach (Tenant::orderBy('id')->get() as $tenant) {
                    $this->callWith(UsersSeeder::class, ['tenant' => $tenant]);
                    $guard->setUser($tenant->users()->where('role', 'Admin')->firstOrFail());

                    $this->call([
                        FazendasSeeder::class,
                        AreasSeeder::class,
                        LotesSeeder::class,
                        AnimaisSeeder::class,
                        MedicamentosSeeder::class,
                        MotivosPesagemSeeder::class,
                        PesagensSeeder::class,
                        ManejosSanitariosSeeder::class,
                        TiposAlimentoSeeder::class,
                        AlimentosSeeder::class,
                        RefeicoesSeeder::class,
                        FinanceiroSeeder::class,
                        ReproducaoSeeder::class,
                        ProntuarioSeeder::class,
                    ]);
                }
            });
        } finally {
            if ($previousUser !== null) {
                $guard->setUser($previousUser);
            } else {
                $guard->forgetUser();
            }
        }
    }
}
