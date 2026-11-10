<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Ponderal\Models\MotivoPesagem;
use App\Modules\Ponderal\Models\Pesagem;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Rebanho\Models\Lote;
use App\Modules\Sanidade\Models\ManejoSanitario;
use App\Modules\Sanidade\Models\Medicamento;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PecuariaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private MotivoPesagem $motivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($this->admin);
        $this->motivo = MotivoPesagem::create(['codigo' => 'ROTINA', 'nome' => 'Rotina', 'ativo' => true]);
    }

    private function animal(array $attributes = []): Animal
    {
        return Animal::create(array_merge([
            'brinco' => 'BR-001', 'categoria' => 'VACA', 'sexo' => 'FEMEA',
            'status' => 'ATIVO', 'data_nascimento' => today()->subYears(2)->toDateString(),
        ], $attributes));
    }

    private function medicamento(): Medicamento
    {
        return Medicamento::create(['nome' => 'Produto de teste', 'unidade' => 'ML']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        Auth::logout();
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/animais')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Entrar na fazenda');
    }

    public function test_login_and_logout(): void
    {
        Auth::logout();
        $this->post('/login', ['email' => $this->admin->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->admin);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_wrong_password_and_throttling(): void
    {
        Auth::logout();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $this->admin->email, 'password' => 'errada'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $this->admin->email, 'password' => 'errada'])->assertStatus(429);
    }

    public function test_all_main_views_render_with_records(): void
    {
        $animal = $this->animal();
        Lote::create(['nome' => 'Lote A']);
        $medicamento = $this->medicamento();
        Pesagem::create(['animal_id' => $animal->id, 'motivo_pesagem_id' => $this->motivo->id, 'peso' => '350.25', 'data_pesagem' => now()->subHour()]);
        ManejoSanitario::create(['animal_id' => $animal->id, 'medicamento_id' => $medicamento->id, 'tipo' => 'VACINA', 'dosagem' => '2.5', 'unidade' => 'ML', 'data_aplicacao' => today()->subDays(10), 'proxima_aplicacao' => today()]);
        foreach (['/dashboard', '/animais', '/animais/create', "/animais/{$animal->id}", "/animais/{$animal->id}/edit", '/lotes', '/pesagens', '/sanidade', '/medicamentos', '/usuarios'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_creates_updates_and_archives_animal(): void
    {
        $data = ['brinco' => 'NOVO', 'categoria' => 'BOI', 'sexo' => 'MACHO', 'status' => 'ATIVO'];
        $this->post('/animais', $data)->assertSessionHasNoErrors()->assertRedirect();
        $animal = Animal::firstOrFail();
        $this->assertEquals($this->admin->tenant_id, $animal->tenant_id);
        $this->put("/animais/{$animal->id}", [...$data, 'status' => 'VENDIDO'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('VENDIDO', $animal->fresh()->status);
        $this->delete("/animais/{$animal->id}")->assertRedirect('/animais');
        $this->assertSoftDeleted('animais', ['id' => $animal->id]);
    }

    public function test_vaqueiro_can_record_but_cannot_administer(): void
    {
        $animal = $this->animal();
        $operator = User::factory()->create(['tenant_id' => $this->admin->tenant_id, 'role' => 'Vaqueiro']);
        $this->actingAs($operator);
        $this->get('/animais')->assertOk();
        $this->get('/animais/create')->assertForbidden();
        $this->post('/animais', [])->assertForbidden();
        $this->delete("/animais/{$animal->id}")->assertForbidden();
        $this->post('/lotes', ['nome' => 'Proibido'])->assertForbidden();
        $this->post('/medicamentos', [])->assertForbidden();
        $this->get('/usuarios')->assertForbidden();
        $this->post('/usuarios', [])->assertForbidden();
        $this->post('/pesagens', ['brinco' => $animal->brinco, 'motivo_pesagem_id' => $this->motivo->id, 'peso' => '420.25', 'data_pesagem' => now()->subMinute()->format('Y-m-d\TH:i')])->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_foreign_tenant_is_invisible_in_search_binding_and_dashboard(): void
    {
        $other = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($other);
        $foreign = $this->animal(['brinco' => 'SEGREDO-OUTRA-FAZENDA']);
        $this->actingAs($this->admin);
        $this->assertSame(0, Animal::count());
        $this->get('/animais?busca=SEGREDO')->assertOk()->assertDontSee('SEGREDO-OUTRA-FAZENDA');
        $this->get('/dashboard')->assertOk()->assertDontSee('SEGREDO-OUTRA-FAZENDA');
        $this->get("/animais/{$foreign->id}")->assertNotFound();
        $this->get("/animais/{$foreign->id}/edit")->assertNotFound();
        $this->delete("/animais/{$foreign->id}")->assertNotFound();
        $this->get('/pesagens?animal_id='.$foreign->id)->assertNotFound();
        $this->post('/pesagens', ['animal_id' => $foreign->id, 'peso' => 100, 'data_pesagem' => now()->subMinute()->format('Y-m-d\TH:i')])->assertSessionHasErrors('animal_id');
    }

    public function test_foreign_lote_and_medicine_are_rejected(): void
    {
        $other = User::factory()->create();
        $this->actingAs($other);
        $lote = Lote::create(['nome' => 'Outro lote']);
        $medicamento = $this->medicamento();
        $this->actingAs($this->admin);
        $animal = $this->animal();
        $this->post('/animais', ['brinco' => 'NOVO', 'categoria' => 'BOI', 'sexo' => 'MACHO', 'status' => 'ATIVO', 'lote_id' => $lote->id])->assertSessionHasErrors('lote_id');
        $this->post('/sanidade', ['animal_id' => $animal->id, 'medicamento_id' => $medicamento->id, 'tipo' => 'VACINA', 'dosagem' => 2, 'data_aplicacao' => today()->toDateString()])->assertSessionHasErrors('medicamento_id');
    }

    public function test_tenant_injection_is_rejected(): void
    {
        $this->post('/animais', ['tenant_id' => 999, 'brinco' => 'NOVO', 'categoria' => 'BOI', 'sexo' => 'MACHO', 'status' => 'ATIVO'])->assertSessionHasErrors('tenant_id');
        $this->assertSame(0, Animal::count());
    }

    public function test_queries_without_authentication_fail_closed(): void
    {
        $this->animal();
        $this->medicamento();
        Auth::logout();
        $this->assertSame(0, Animal::count());
        $this->assertSame(0, Medicamento::count());
        $this->expectException(AuthorizationException::class);
        $this->animal(['brinco' => 'SEM-AUTH']);
    }

    public function test_loaded_model_cannot_be_moved_to_another_tenant(): void
    {
        $animal = $this->animal();
        $animal->tenant_id = User::factory()->create()->tenant_id;
        $this->expectException(AuthorizationException::class);
        $animal->save();
    }

    public function test_loaded_foreign_model_cannot_be_deleted(): void
    {
        $animal = $this->animal();
        $this->actingAs(User::factory()->create());
        $this->expectException(AuthorizationException::class);
        $animal->delete();
    }

    public function test_database_rejects_cross_tenant_foreign_key(): void
    {
        $animal = $this->animal();
        $other = User::factory()->create();
        $this->expectException(QueryException::class);
        DB::transaction(fn () => DB::table('pesagens')->insert(['tenant_id' => $other->tenant_id, 'animal_id' => $animal->id, 'motivo_pesagem_id' => $this->motivo->id, 'peso' => 300, 'data_pesagem' => now()]));
    }

    public function test_brinco_is_unique_per_tenant_including_archived_animals(): void
    {
        $animal = $this->animal();
        $animal->delete();
        $data = ['brinco' => $animal->brinco, 'categoria' => 'BOI', 'sexo' => 'MACHO', 'status' => 'ATIVO'];
        $this->post('/animais', $data)->assertSessionHasErrors('brinco');
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->post('/animais', $data)->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_weight_requires_positive_value_valid_dates_and_no_duplicate(): void
    {
        $animal = $this->animal();
        $data = ['animal_id' => $animal->id, 'motivo_pesagem_id' => $this->motivo->id, 'peso' => '312.25', 'data_pesagem' => now()->subMinute()->format('Y-m-d\TH:i')];
        $this->post('/pesagens', [...$data, 'peso' => 0])->assertSessionHasErrors('peso');
        $this->post('/pesagens', [...$data, 'data_pesagem' => now()->addDay()->format('Y-m-d\TH:i')])->assertSessionHasErrors('data_pesagem');
        $this->post('/pesagens', [...$data, 'data_pesagem' => today()->subYears(3)->format('Y-m-d\TH:i')])->assertSessionHasErrors('data_pesagem');
        $this->post('/pesagens', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->post('/pesagens', $data)->assertSessionHasErrors('data_pesagem');
        $this->assertSame(1, Pesagem::count());
    }

    public function test_inactive_or_archived_animal_cannot_receive_new_events(): void
    {
        $animal = $this->animal(['status' => 'VENDIDO']);
        $data = ['animal_id' => $animal->id, 'peso' => 100, 'data_pesagem' => now()->subMinute()->format('Y-m-d\TH:i')];
        $this->post('/pesagens', $data)->assertSessionHasErrors('animal_id');
        $animal->update(['status' => 'ATIVO']);
        $animal->delete();
        $this->post('/pesagens', $data)->assertSessionHasErrors('animal_id');
    }

    public function test_sanitary_application_preserves_unit_and_validates_chronology(): void
    {
        $animal = $this->animal();
        $medicamento = $this->medicamento();
        $data = ['brinco' => $animal->brinco, 'medicamento_id' => $medicamento->id, 'tipo' => 'VACINA', 'dosagem' => '2.500', 'data_aplicacao' => today()->toDateString(), 'proxima_aplicacao' => today()->addMonth()->toDateString()];
        $this->post('/sanidade', [...$data, 'proxima_aplicacao' => today()->toDateString()])->assertSessionHasErrors('proxima_aplicacao');
        $this->post('/sanidade', [...$data, 'dosagem' => 0])->assertSessionHasErrors('dosagem');
        $this->post('/sanidade', [...$data, 'data_aplicacao' => today()->subYears(3)->toDateString()])->assertSessionHasErrors('data_aplicacao');
        $this->post('/sanidade', [...$data, 'unidade' => 'ADULTERADA'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('ML', ManejoSanitario::firstOrFail()->unidade);
        $medicamento->update(['ativo' => false]);
        $this->post('/sanidade', $data)->assertSessionHasErrors('medicamento_id');
    }

    public function test_new_application_supersedes_previous_reminder_without_erasing_history(): void
    {
        $animal = $this->animal();
        $medicamento = $this->medicamento();
        $data = ['animal_id' => $animal->id, 'medicamento_id' => $medicamento->id, 'tipo' => 'VACINA', 'dosagem' => 2, 'unidade' => 'ML'];
        ManejoSanitario::create([...$data, 'data_aplicacao' => today()->subMonth(), 'proxima_aplicacao' => today()->subDay()]);
        $this->assertSame(1, ManejoSanitario::pendentes()->count());
        ManejoSanitario::create([...$data, 'data_aplicacao' => today(), 'proxima_aplicacao' => null]);
        $this->assertSame(0, ManejoSanitario::pendentes()->count());
        $this->assertSame(2, ManejoSanitario::count());
    }

    public function test_archiving_preserves_history_and_removes_pending_alerts(): void
    {
        $animal = $this->animal();
        $medicamento = $this->medicamento();
        $pesagem = Pesagem::create(['animal_id' => $animal->id, 'motivo_pesagem_id' => $this->motivo->id, 'peso' => 100, 'data_pesagem' => now()]);
        ManejoSanitario::create(['animal_id' => $animal->id, 'medicamento_id' => $medicamento->id, 'tipo' => 'VACINA', 'dosagem' => 2, 'unidade' => 'ML', 'data_aplicacao' => today()->subMonth(), 'proxima_aplicacao' => today()]);
        $animal->delete();
        $this->assertSame($animal->id, $pesagem->fresh()->animal->id);
        $this->assertSame(0, ManejoSanitario::pendentes()->count());
        $this->get('/pesagens')->assertOk()->assertSee('Arquivado');
        $this->get('/animais/'.$animal->id)->assertOk()->assertSee('consulta do histórico');
        $this->get('/animais/'.$animal->id.'/edit')->assertNotFound();
    }

    public function test_farm_admin_command_provisions_hashed_password(): void
    {
        $this->artisan('pecuaria:admin', ['fazenda' => 'Fazenda nova', 'email' => 'admin@fazenda.test'])
            ->expectsQuestion('Senha do administrador (mínimo de 12 caracteres)', 'senha-segura-123')
            ->expectsQuestion('Confirme a senha', 'senha-segura-123')
            ->assertSuccessful();
        $user = User::where('email', 'admin@fazenda.test')->firstOrFail();
        $this->assertTrue($user->isAdmin());
        $this->assertSame('Fazenda nova', $user->tenant->nome);
        $this->assertNotSame('senha-segura-123', $user->password);
    }

    public function test_birth_date_edit_cannot_invalidate_existing_history(): void
    {
        $animal = $this->animal();
        Pesagem::create(['animal_id' => $animal->id, 'motivo_pesagem_id' => $this->motivo->id, 'peso' => 100, 'data_pesagem' => today()->subMonth()]);
        $this->put('/animais/'.$animal->id, [
            'brinco' => $animal->brinco, 'categoria' => 'VACA', 'sexo' => 'FEMEA', 'status' => 'ATIVO',
            'data_nascimento' => today()->subDay()->toDateString(),
        ])->assertSessionHasErrors('data_nascimento');
    }

    public function test_admin_creates_operator_only_in_current_farm(): void
    {
        $otherUser = User::factory()->create(['name' => 'USUARIO-DE-OUTRO-TENANT']);
        $this->get('/usuarios')->assertOk()->assertDontSee($otherUser->name);
        $data = ['name' => 'Operador de campo', 'email' => 'operador@fazenda.test', 'role' => 'Vaqueiro', 'password' => 'senha-inicial-123', 'password_confirmation' => 'senha-inicial-123'];
        $this->post('/usuarios', [...$data, 'tenant_id' => $otherUser->tenant_id])->assertSessionHasErrors('tenant_id');
        $this->post('/usuarios', $data)->assertSessionHasNoErrors()->assertRedirect('/usuarios');
        $user = User::where('email', $data['email'])->firstOrFail();
        $this->assertEquals($this->admin->tenant_id, $user->tenant_id);
        $this->assertSame('Vaqueiro', $user->role);
        $this->assertNotSame($data['password'], $user->password);
    }
}
