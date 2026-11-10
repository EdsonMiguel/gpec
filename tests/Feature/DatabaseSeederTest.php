<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\Tenant;
use App\Modules\Auth\Models\User;
use App\Modules\Fazendas\Models\Area;
use App\Modules\Fazendas\Models\Fazenda;
use App\Modules\Ponderal\Models\MotivoPesagem;
use App\Modules\Ponderal\Models\Pesagem;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Rebanho\Models\Lote;
use App\Modules\Sanidade\Models\ManejoSanitario;
use App\Modules\Sanidade\Models\Medicamento;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    private const TABLES = ['tenants', 'users', 'fazendas', 'areas', 'lotes', 'animais', 'medicamentos', 'motivos_pesagem', 'pesagens', 'manejos_sanitarios', 'tipos_alimento', 'alimentos', 'refeicoes', 'itens_refeicao', 'contas_orcamentarias', 'produtos', 'transacoes', 'itens_transacao', 'gestacoes', 'exames_gestacao', 'doencas', 'casos_clinicos', 'avaliacoes_clinicas', 'compromissos'];

    private function counts(): array
    {
        $counts = [];
        foreach (self::TABLES as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }

    public function test_seed_populates_every_business_table_and_restores_guest_context(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertSame([
            'tenants' => 1, 'users' => 2, 'fazendas' => 2, 'areas' => 8, 'lotes' => 3,
            'animais' => 16, 'medicamentos' => 3, 'motivos_pesagem' => 6, 'pesagens' => 48, 'manejos_sanitarios' => 32,
            'tipos_alimento' => 4, 'alimentos' => 3, 'refeicoes' => 30, 'itens_refeicao' => 60,
            'contas_orcamentarias' => 6, 'produtos' => 2, 'transacoes' => 5, 'itens_transacao' => 5,
            'gestacoes' => 2, 'exames_gestacao' => 2,
            'doencas' => 1, 'casos_clinicos' => 1, 'avaliacoes_clinicas' => 1,
            'compromissos' => 36,
        ], $this->counts());
        $this->assertGuest();
        $this->assertSame(0, Animal::count());

        $admin = User::where('role', 'Admin')->firstOrFail();
        $this->assertTrue(Hash::check(config('demo.password'), $admin->password));
        $this->actingAs($admin);
        $this->assertSame(15, Animal::ativos()->count());
        $this->assertSame(8, Animal::distinct()->count('categoria'));
        $this->assertSame(8, Area::count());
        $this->assertSame(15, ManejoSanitario::pendentes()->count());
        $this->assertTrue(ManejoSanitario::pendentes()->whereDate('proxima_aplicacao', '<', today())->exists());
        $this->assertTrue(ManejoSanitario::pendentes()->whereBetween('proxima_aplicacao', [today(), today()->addDays(7)])->exists());

        foreach (Pesagem::with(['animal', 'motivo'])->get() as $pesagem) {
            $this->assertSame('ACOMPANHAMENTO', $pesagem->motivo->codigo);
            $this->assertTrue($pesagem->data_pesagem->gte($pesagem->animal->data_nascimento));
            $this->assertTrue($pesagem->data_pesagem->lte(now()));
        }
        foreach (ManejoSanitario::with(['animal', 'medicamento'])->get() as $manejo) {
            $this->assertTrue($manejo->data_aplicacao->gte($manejo->animal->data_nascimento));
            $this->assertTrue($manejo->data_aplicacao->lte(today()));
            $this->assertTrue($manejo->proxima_aplicacao->gt($manejo->data_aplicacao));
            $this->assertSame($manejo->medicamento->unidade, $manejo->unidade);
        }
    }

    public function test_seed_is_idempotent_on_subsequent_days_and_preserves_edited_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('role', 'Admin')->firstOrFail();
        $this->actingAs($admin);
        $animal = Animal::where('brinco', 'DEMO-0001')->firstOrFail();
        $animal->update(['nome_ou_numero' => 'Nome alterado pelo usuário']);
        $animal->delete();
        $fazenda = Fazenda::where('codigo', 'DEMO-BOA-VISTA')->firstOrFail();
        $fazenda->update(['observacao' => 'Anotação preservada']);
        $pesagem = Pesagem::firstOrFail();
        $pesagem->update(['peso' => '123.45']);
        $password = $admin->password;
        $counts = $this->counts();

        $this->travel(3)->days();
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($counts, $this->counts());
        $this->assertSame('Nome alterado pelo usuário', Animal::withTrashed()->findOrFail($animal->id)->nome_ou_numero);
        $this->assertTrue(Animal::withTrashed()->findOrFail($animal->id)->trashed());
        $this->assertSame('Anotação preservada', $fazenda->fresh()->observacao);
        $this->assertSame('123.45', $pesagem->fresh()->peso);
        $this->assertSame($password, $admin->fresh()->password);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_seed_supports_multiple_existing_tenants_and_keeps_their_data_isolated(): void
    {
        $firstAdmin = User::factory()->create(['role' => 'Admin']);
        $secondAdmin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($firstAdmin);
        $custom = Animal::create(['brinco' => 'MANUAL-001', 'categoria' => 'VACA', 'sexo' => 'FEMEA', 'status' => 'ATIVO']);
        $this->seed(DatabaseSeeder::class);
        $this->assertAuthenticatedAs($firstAdmin);
        $this->assertSame(2, Tenant::count());
        $this->assertSame(4, User::count());
        $this->assertSame(17, Animal::count());
        $this->assertSame(2, Fazenda::count());
        $this->assertSame(3, Lote::count());
        $this->assertSame(3, Medicamento::count());
        $this->assertSame(6, MotivoPesagem::count());
        $this->assertSame(48, Pesagem::count());
        $firstAnimalId = Animal::where('brinco', 'DEMO-0001')->value('id');
        $this->assertNotNull($custom->fresh());

        $this->actingAs($secondAdmin);
        $this->assertSame(16, Animal::count());
        $this->assertNull(Animal::find($custom->id));
        $this->assertNotEquals($firstAnimalId, Animal::where('brinco', 'DEMO-0001')->value('id'));
        foreach (Area::with('fazenda')->get() as $area) {
            $this->assertEquals($secondAdmin->tenant_id, $area->fazenda->tenant_id);
        }
        foreach (Pesagem::with('animal')->get() as $pesagem) {
            $this->assertEquals($secondAdmin->tenant_id, $pesagem->animal->tenant_id);
        }
    }

    public function test_seed_does_not_modify_infrastructure_tables(): void
    {
        $this->seed(DatabaseSeeder::class);
        foreach (['sessions', 'password_reset_tokens', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertNull(Auth::user());
    }

    public function test_seed_is_atomic_and_restores_authentication_when_a_user_email_conflicts(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $other = User::factory()->create(['role' => 'Admin', 'email' => 'vaqueiro.'.$admin->tenant_id.'@campo.test']);
        $this->actingAs($other);
        $counts = $this->counts();

        try {
            $this->seed(DatabaseSeeder::class);
            $this->fail('O seed deveria recusar o e-mail pertencente a outro tenant.');
        } catch (QueryException) {
            $this->assertSame($counts, $this->counts());
            $this->assertAuthenticatedAs($other);
        }
    }

    public function test_demo_seeding_is_refused_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Os seeds de demonstração só podem ser executados');
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->run();
    }
}
