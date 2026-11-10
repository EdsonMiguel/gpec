<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Fazendas\Models\Area;
use App\Modules\Fazendas\Models\Fazenda;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FazendasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($this->admin);
    }

    private function fazenda(string $nome = 'Fazenda Boa Vista'): Fazenda
    {
        return Fazenda::create(['nome' => $nome, 'ativo' => true]);
    }

    private function area(Fazenda $fazenda, string $nome = 'Pasto 1'): Area
    {
        return $fazenda->areas()->create(['nome' => $nome, 'tipo' => 'PASTO', 'ativo' => true]);
    }

    public function test_admin_creates_and_updates_farm_and_area(): void
    {
        $farmData = ['nome' => 'Boa Vista', 'codigo' => 'BV', 'municipio' => 'Uberaba', 'uf' => 'MG', 'area_total_ha' => '250.50', 'ativo' => 1];
        $this->post('/fazendas', $farmData)->assertSessionHasNoErrors()->assertRedirect();
        $fazenda = Fazenda::firstOrFail();
        $this->assertEquals($this->admin->tenant_id, $fazenda->tenant_id);
        $this->put('/fazendas/'.$fazenda->id, [...$farmData, 'observacao' => 'Cadastro revisado'])->assertSessionHasNoErrors()->assertRedirect('/fazendas/'.$fazenda->id);
        $areaData = ['nome' => 'Piquete Norte', 'tipo' => 'PIQUETE', 'area_ha' => '12.75', 'ativo' => 1];
        $this->post('/fazendas/'.$fazenda->id.'/areas', $areaData)->assertSessionHasNoErrors()->assertRedirect('/fazendas/'.$fazenda->id);
        $area = Area::firstOrFail();
        $this->assertEquals($fazenda->id, $area->fazenda_id);
        $this->assertEquals($this->admin->tenant_id, $area->tenant_id);
        $this->assertSame('12.75', $area->area_ha);
        $this->put('/fazendas/'.$fazenda->id.'/areas/'.$area->id, [...$areaData, 'nome' => 'Piquete Sul', 'ativo' => 0])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertFalse($area->fresh()->ativo);
        $this->assertSame('Piquete Sul', $area->fresh()->nome);
    }

    public function test_views_render_with_existing_records(): void
    {
        $fazenda = $this->fazenda();
        $area = $this->area($fazenda);
        foreach (['/fazendas', '/fazendas/create', '/fazendas/'.$fazenda->id, '/fazendas/'.$fazenda->id.'/edit', '/fazendas/'.$fazenda->id.'/areas/create', '/fazendas/'.$fazenda->id.'/areas/'.$area->id.'/edit'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/fazendas/'.$fazenda->id)->assertSee('Pasto 1');
        $this->get('/usuarios')->assertOk()->assertSee('Fazendas e áreas');
    }

    public function test_guest_cannot_access_farm_records(): void
    {
        Auth::logout();
        $this->get('/fazendas')->assertRedirect('/login');
        $this->post('/fazendas', [])->assertRedirect('/login');
        $this->assertSame(0, Fazenda::count());
        $this->assertSame(0, Area::count());
    }

    public function test_vaqueiro_can_consult_but_cannot_change_records(): void
    {
        $fazenda = $this->fazenda();
        $area = $this->area($fazenda);
        $this->actingAs(User::factory()->create(['tenant_id' => $this->admin->tenant_id, 'role' => 'Vaqueiro']));
        $this->get('/fazendas')->assertOk();
        $this->get('/fazendas/'.$fazenda->id)->assertOk()->assertSee($area->nome)->assertDontSee('Editar fazenda')->assertDontSee('Nova área');
        $this->get('/fazendas/create')->assertForbidden();
        $this->post('/fazendas', [])->assertForbidden();
        $this->put('/fazendas/'.$fazenda->id, [])->assertForbidden();
        $this->post('/fazendas/'.$fazenda->id.'/areas', [])->assertForbidden();
        $this->put('/fazendas/'.$fazenda->id.'/areas/'.$area->id, [])->assertForbidden();
    }

    public function test_farms_and_areas_of_another_tenant_are_inaccessible(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $foreign = $this->fazenda('FAZENDA-OUTRO-TENANT');
        $area = $this->area($foreign, 'AREA-OUTRO-TENANT');
        $this->actingAs($this->admin);
        $this->get('/fazendas')->assertOk()->assertDontSee($foreign->nome);
        $this->assertSame(0, Fazenda::count());
        $this->assertSame(0, Area::count());
        $this->get('/fazendas/'.$foreign->id)->assertNotFound();
        $this->get('/fazendas/'.$foreign->id.'/edit')->assertNotFound();
        $this->post('/fazendas/'.$foreign->id.'/areas', ['nome' => 'Teste', 'tipo' => 'PASTO', 'ativo' => 1])->assertNotFound();
        $this->get('/fazendas/'.$foreign->id.'/areas/'.$area->id.'/edit')->assertNotFound();
    }

    public function test_area_binding_requires_the_correct_farm_even_within_same_tenant(): void
    {
        $first = $this->fazenda('Fazenda A');
        $second = $this->fazenda('Fazenda B');
        $area = $this->area($first);
        $this->get('/fazendas/'.$second->id.'/areas/'.$area->id.'/edit')->assertNotFound();
        $this->put('/fazendas/'.$second->id.'/areas/'.$area->id, ['nome' => 'Alterado', 'tipo' => 'CURRAL', 'ativo' => 1])->assertNotFound();
        $this->assertSame('Pasto 1', $area->fresh()->nome);
        $this->get('/fazendas/'.$second->id)->assertOk()->assertDontSee('Pasto 1');
    }

    public function test_payload_cannot_change_tenant_or_area_parent(): void
    {
        $fazenda = $this->fazenda();
        $this->post('/fazendas', ['nome' => 'Tentativa', 'ativo' => 1, 'tenant_id' => 999])->assertSessionHasErrors('tenant_id');
        $this->post('/fazendas/'.$fazenda->id.'/areas', ['nome' => 'Teste', 'tipo' => 'PASTO', 'ativo' => 1, 'tenant_id' => 999, 'fazenda_id' => 999])->assertSessionHasErrors(['tenant_id', 'fazenda_id']);
        $this->assertSame(0, Area::count());
    }

    public function test_names_are_unique_in_their_scope_and_edits_keep_current_values(): void
    {
        $first = $this->fazenda('Fazenda A');
        $second = $this->fazenda('Fazenda B');
        $this->post('/fazendas', ['nome' => 'Fazenda A', 'ativo' => 1])->assertSessionHasErrors('nome');
        $this->put('/fazendas/'.$first->id, ['nome' => 'Fazenda A', 'ativo' => 1])->assertSessionHasNoErrors();
        $this->area($first);
        $areaData = ['nome' => 'Pasto 1', 'tipo' => 'PASTO', 'ativo' => 1];
        $this->post('/fazendas/'.$first->id.'/areas', $areaData)->assertSessionHasErrors('nome');
        $this->post('/fazendas/'.$second->id.'/areas', $areaData)->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->post('/fazendas', ['nome' => 'Fazenda A', 'ativo' => 1])->assertSessionHasNoErrors();
    }

    public function test_invalid_measurements_types_and_location_are_rejected(): void
    {
        $this->post('/fazendas', ['nome' => 'Teste', 'ativo' => 1, 'uf' => 'XX', 'area_total_ha' => '-1'])->assertSessionHasErrors(['uf', 'area_total_ha']);
        $fazenda = $this->fazenda();
        $this->post('/fazendas/'.$fazenda->id.'/areas', ['nome' => 'Teste', 'ativo' => 1, 'tipo' => 'INVALIDO', 'area_ha' => '-1'])->assertSessionHasErrors(['tipo', 'area_ha']);
    }

    public function test_inactive_farm_preserves_areas_and_prevents_new_registrations(): void
    {
        $fazenda = $this->fazenda();
        $area = $this->area($fazenda);
        $this->put('/fazendas/'.$fazenda->id, ['nome' => $fazenda->nome, 'ativo' => 0])->assertSessionHasNoErrors();
        $this->get('/fazendas/'.$fazenda->id)->assertOk()->assertSee($area->nome)->assertSee('Fazenda inativa');
        $this->post('/fazendas/'.$fazenda->id.'/areas', ['nome' => 'Novo pasto', 'tipo' => 'PASTO', 'ativo' => 1])->assertSessionHasErrors('fazenda');
        $this->assertSame(1, $fazenda->areas()->count());
        $this->put('/fazendas/'.$fazenda->id, ['nome' => $fazenda->nome, 'ativo' => 1])->assertSessionHasNoErrors();
        $this->post('/fazendas/'.$fazenda->id.'/areas', ['nome' => 'Novo pasto', 'tipo' => 'PASTO', 'ativo' => 1])->assertSessionHasNoErrors();
    }

    public function test_database_rejects_cross_tenant_area_relationship(): void
    {
        $fazenda = $this->fazenda();
        $other = User::factory()->create();
        $this->expectException(QueryException::class);
        DB::transaction(fn () => DB::table('areas')->insert(['tenant_id' => $other->tenant_id, 'fazenda_id' => $fazenda->id, 'nome' => 'Tentativa', 'tipo' => 'PASTO']));
    }
}
