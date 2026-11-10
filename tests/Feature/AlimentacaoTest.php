<?php

namespace Tests\Feature;

use App\Modules\Alimentacao\Models\Alimento;
use App\Modules\Alimentacao\Models\ItemRefeicao;
use App\Modules\Alimentacao\Models\Refeicao;
use App\Modules\Alimentacao\Models\TipoAlimento;
use App\Modules\Auth\Models\User;
use App\Modules\Rebanho\Models\Animal;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlimentacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private TipoAlimento $tipo;

    private Alimento $alimento;

    private Animal $animal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($this->admin);
        $this->tipo = TipoAlimento::create(['codigo' => 'VOLUMOSO', 'nome' => 'Volumoso', 'ativo' => true]);
        $this->alimento = Alimento::create($this->alimentoData());
        $this->animal = Animal::create(['brinco' => 'ANIMAL-001', 'sexo' => 'FEMEA', 'categoria' => 'VACA', 'status' => 'ATIVO', 'data_nascimento' => '2020-01-01']);
    }

    private function alimentoData(array $attributes = []): array
    {
        return ['codigo' => 'SILAGEM', 'nome' => 'Silagem de teste', 'tipo_alimento_id' => $this->tipo->id, 'unidade' => 'KG', 'ativo' => 1, ...$attributes];
    }

    private function mealData(array $attributes = []): array
    {
        return [
            'animal_id' => $this->animal->id, 'data_fornecimento' => now()->subMinute()->format('Y-m-d\TH:i'), 'turno' => 'MANHA',
            'itens' => [['alimento_id' => $this->alimento->id, 'quantidade' => '1.250']], ...$attributes,
        ];
    }

    public function test_admin_can_create_update_and_delete_unused_types_and_foods(): void
    {
        $tipoData = ['codigo' => 'TESTE', 'nome' => 'Tipo de teste', 'ativo' => 1];
        $this->post('/tipos-alimento', $tipoData)->assertSessionHasNoErrors()->assertRedirect('/tipos-alimento');
        $tipo = TipoAlimento::where('codigo', 'TESTE')->firstOrFail();
        $this->put('/tipos-alimento/'.$tipo->id, [...$tipoData, 'descricao' => 'Descrição'])->assertSessionHasNoErrors();
        $foodData = $this->alimentoData(['codigo' => 'NOVO', 'tipo_alimento_id' => $tipo->id]);
        $this->post('/alimentos', $foodData)->assertSessionHasNoErrors()->assertRedirect('/alimentos');
        $food = Alimento::where('codigo', 'NOVO')->firstOrFail();
        $this->put('/alimentos/'.$food->id, [...$foodData, 'unidade' => 'G', 'composicao' => 'Composição descritiva'])->assertSessionHasNoErrors();
        $this->assertSame('G', $food->fresh()->unidade);
        $this->delete('/alimentos/'.$food->id)->assertRedirect('/alimentos');
        $this->delete('/tipos-alimento/'.$tipo->id)->assertRedirect('/tipos-alimento');
        $this->assertDatabaseMissing('alimentos', ['id' => $food->id]);
        $this->assertDatabaseMissing('tipos_alimento', ['id' => $tipo->id]);
    }

    public function test_views_render_and_meals_preserve_multiple_foods_and_units(): void
    {
        $second = Alimento::create($this->alimentoData(['codigo' => 'SUPLEMENTO', 'nome' => 'Suplemento de teste', 'unidade' => 'G']));
        $this->post('/refeicoes', $this->mealData(['brinco' => $this->animal->brinco, 'itens' => [
            ['alimento_id' => $this->alimento->id, 'quantidade' => '1.250'], ['alimento_id' => $second->id, 'quantidade' => '10.500'],
        ]]))->assertSessionHasNoErrors()->assertRedirect();
        $meal = Refeicao::firstOrFail();
        $this->assertSame(2, $meal->itens()->count());
        $this->assertSame('1.250', $meal->itens()->where('alimento_id', $this->alimento->id)->firstOrFail()->quantidade);
        $this->assertSame('G', $meal->itens()->where('alimento_id', $second->id)->firstOrFail()->unidade);
        foreach (['/refeicoes', '/refeicoes/create', '/refeicoes/'.$meal->id, '/alimentos', '/alimentos/create', '/alimentos/'.$this->alimento->id.'/edit', '/tipos-alimento', '/tipos-alimento/create', '/tipos-alimento/'.$this->tipo->id.'/edit', '/animais/'.$this->animal->id] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/refeicoes/'.$meal->id)->assertSee($second->nome)->assertSee('10,500');
    }

    public function test_missing_invalid_quantities_and_dates_are_rejected_atomically(): void
    {
        $this->post('/refeicoes', $this->mealData(['itens' => []]))->assertSessionHasErrors('itens');
        $this->post('/refeicoes', $this->mealData(['itens' => [['alimento_id' => $this->alimento->id, 'quantidade' => 0]]]))->assertSessionHasErrors('itens.0.quantidade');
        $this->post('/refeicoes', $this->mealData(['itens' => [['alimento_id' => $this->alimento->id, 'quantidade' => '1.1234']]]))->assertSessionHasErrors('itens.0.quantidade');
        $this->post('/refeicoes', $this->mealData(['data_fornecimento' => now()->addDay()->format('Y-m-d\TH:i')]))->assertSessionHasErrors('data_fornecimento');
        $this->post('/refeicoes', $this->mealData(['data_fornecimento' => '2019-01-01T07:00']))->assertSessionHasErrors('data_fornecimento');
        $this->assertSame(0, Refeicao::count());
        $this->assertSame(0, ItemRefeicao::count());
    }

    public function test_repeated_foods_and_duplicate_meal_timestamps_are_rejected(): void
    {
        $item = ['alimento_id' => $this->alimento->id, 'quantidade' => 1];
        $this->post('/refeicoes', $this->mealData(['itens' => [$item, $item]]))->assertSessionHasErrors('itens.0.alimento_id');
        $this->post('/refeicoes', $this->mealData(['itens' => array_fill(0, 21, $item)]))->assertSessionHasErrors('itens');
        $this->post('/refeicoes', $this->mealData())->assertSessionHasNoErrors();
        $this->post('/refeicoes', $this->mealData())->assertSessionHasErrors('data_fornecimento');
        $this->assertSame(1, Refeicao::count());
    }

    public function test_inactive_food_or_type_prevents_new_meals_but_preserves_history(): void
    {
        $this->post('/refeicoes', $this->mealData())->assertSessionHasNoErrors();
        $meal = Refeicao::firstOrFail();
        $this->tipo->update(['ativo' => false]);
        $this->post('/refeicoes', $this->mealData(['data_fornecimento' => now()->subMinutes(2)->format('Y-m-d\TH:i')]))->assertSessionHasErrors('itens.0.alimento_id');
        $this->get('/refeicoes/create')->assertOk()->assertDontSee($this->alimento->nome);
        $this->get('/refeicoes/'.$meal->id)->assertOk()->assertSee($this->alimento->nome);
        $this->tipo->update(['ativo' => true]);
        $this->alimento->update(['ativo' => false]);
        $this->post('/refeicoes', $this->mealData())->assertSessionHasErrors('itens.0.alimento_id');
        $this->assertSame(1, Refeicao::count());
    }

    public function test_used_foods_and_types_cannot_be_deleted_or_have_units_changed(): void
    {
        $this->post('/refeicoes', $this->mealData())->assertSessionHasNoErrors();
        $this->delete('/alimentos/'.$this->alimento->id)->assertSessionHasErrors('alimento');
        $this->delete('/tipos-alimento/'.$this->tipo->id)->assertSessionHasErrors('tipo');
        $this->put('/alimentos/'.$this->alimento->id, $this->alimentoData(['unidade' => 'G']))->assertSessionHasErrors('unidade');
        $this->put('/alimentos/'.$this->alimento->id, $this->alimentoData(['ativo' => 0]))->assertSessionHasNoErrors();
        $this->assertSame('KG', ItemRefeicao::firstOrFail()->unidade);
    }

    public function test_new_food_cannot_use_inactive_type_and_existing_food_can_be_inactivated(): void
    {
        $this->tipo->update(['ativo' => false]);
        $this->post('/alimentos', $this->alimentoData(['codigo' => 'NOVO']))->assertSessionHasErrors('tipo_alimento_id');
        $this->put('/alimentos/'.$this->alimento->id, $this->alimentoData(['ativo' => 0]))->assertSessionHasNoErrors();
    }

    public function test_vaqueiro_can_record_and_consult_but_cannot_manage_catalogs(): void
    {
        $this->actingAs(User::factory()->create(['tenant_id' => $this->admin->tenant_id, 'role' => 'Vaqueiro']));
        $this->get('/alimentos')->assertOk()->assertDontSee('Novo alimento');
        $this->get('/tipos-alimento')->assertOk()->assertDontSee('Novo tipo');
        foreach (['/alimentos/create', '/tipos-alimento/create'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/alimentos', $this->alimentoData())->assertForbidden();
        $this->post('/tipos-alimento', [])->assertForbidden();
        $this->delete('/alimentos/'.$this->alimento->id)->assertForbidden();
        $this->post('/refeicoes', $this->mealData())->assertSessionHasNoErrors();
    }

    public function test_foreign_tenant_records_are_invisible_and_cannot_be_used(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $tipo = TipoAlimento::create(['codigo' => 'OUTRO', 'nome' => 'TIPO-OUTRA-CONTA', 'ativo' => true]);
        $food = Alimento::create($this->alimentoData(['tipo_alimento_id' => $tipo->id, 'nome' => 'ALIMENTO-OUTRA-CONTA']));
        $this->actingAs($this->admin);
        $this->get('/alimentos')->assertOk()->assertDontSee($food->nome);
        $this->get('/tipos-alimento')->assertOk()->assertDontSee($tipo->nome);
        $this->get('/alimentos/'.$food->id.'/edit')->assertNotFound();
        $this->get('/tipos-alimento/'.$tipo->id.'/edit')->assertNotFound();
        $this->post('/alimentos', $this->alimentoData(['codigo' => 'NOVO', 'tipo_alimento_id' => $tipo->id]))->assertSessionHasErrors('tipo_alimento_id');
        $this->post('/refeicoes', $this->mealData(['itens' => [['alimento_id' => $food->id, 'quantidade' => 1]]]))->assertSessionHasErrors('itens.0.alimento_id');
    }

    public function test_tenant_and_unit_injection_are_rejected(): void
    {
        $this->post('/alimentos', $this->alimentoData(['codigo' => 'NOVO', 'tenant_id' => 999]))->assertSessionHasErrors('tenant_id');
        $this->post('/refeicoes', $this->mealData(['tenant_id' => 999]))->assertSessionHasErrors('tenant_id');
        $this->post('/refeicoes', $this->mealData(['itens' => [['alimento_id' => $this->alimento->id, 'quantidade' => 1, 'unidade' => 'ADULTERADA']]]))->assertSessionHasErrors('itens.0');
        $this->assertSame(0, Refeicao::count());
    }

    public function test_inactive_and_archived_animals_cannot_receive_new_meals(): void
    {
        $this->animal->update(['status' => 'VENDIDO']);
        $this->post('/refeicoes', $this->mealData())->assertSessionHasErrors('animal_id');
        $this->animal->update(['status' => 'ATIVO']);
        $this->animal->delete();
        $this->post('/refeicoes', $this->mealData())->assertSessionHasErrors('animal_id');
    }

    public function test_history_survives_archiving_and_prevents_invalid_birth_date_edits(): void
    {
        $this->post('/refeicoes', $this->mealData(['data_fornecimento' => now()->subDay()->format('Y-m-d\TH:i')]))->assertSessionHasNoErrors();
        $meal = Refeicao::firstOrFail();
        $this->put('/animais/'.$this->animal->id, ['brinco' => $this->animal->brinco, 'categoria' => 'VACA', 'sexo' => 'FEMEA', 'status' => 'ATIVO', 'data_nascimento' => today()->toDateString()])->assertSessionHasErrors('data_nascimento');
        $this->animal->delete();
        $this->get('/refeicoes/'.$meal->id)->assertOk();
        $this->get('/animais/'.$this->animal->id)->assertOk()->assertSee('Histórico de alimentação');
    }

    public function test_guest_queries_fail_closed_and_routes_require_authentication(): void
    {
        Auth::logout();
        $this->get('/refeicoes')->assertRedirect('/login');
        $this->get('/alimentos')->assertRedirect('/login');
        $this->post('/refeicoes', $this->mealData())->assertRedirect('/login');
        $this->assertSame(0, Alimento::count());
        $this->assertSame(0, TipoAlimento::count());
    }

    public function test_database_rejects_cross_tenant_food_in_meal_item(): void
    {
        $this->post('/refeicoes', $this->mealData())->assertSessionHasNoErrors();
        $meal = Refeicao::firstOrFail();
        $other = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($other);
        $tipo = TipoAlimento::create(['codigo' => 'OUTRO', 'nome' => 'Outro', 'ativo' => true]);
        $food = Alimento::create($this->alimentoData(['tipo_alimento_id' => $tipo->id]));
        $this->expectException(QueryException::class);
        DB::transaction(fn () => DB::table('itens_refeicao')->insert(['tenant_id' => $this->admin->tenant_id, 'refeicao_id' => $meal->id, 'alimento_id' => $food->id, 'quantidade' => 1, 'unidade' => 'KG']));
    }
}
