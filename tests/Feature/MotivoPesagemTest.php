<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Ponderal\Models\MotivoPesagem;
use App\Modules\Ponderal\Models\Pesagem;
use App\Modules\Rebanho\Models\Animal;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MotivoPesagemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private MotivoPesagem $motivo;

    private Animal $animal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($this->admin);
        $this->motivo = MotivoPesagem::create(['codigo' => 'ROTINA', 'nome' => 'Acompanhamento de rotina', 'ativo' => true]);
        $this->animal = Animal::create(['brinco' => 'ANIMAL-001', 'categoria' => 'VACA', 'sexo' => 'FEMEA', 'status' => 'ATIVO']);
    }

    private function pesagemData(array $attributes = []): array
    {
        return ['animal_id' => $this->animal->id, 'motivo_pesagem_id' => $this->motivo->id, 'peso' => '320.50', 'data_pesagem' => now()->subMinute()->format('Y-m-d\TH:i'), ...$attributes];
    }

    public function test_admin_can_create_read_update_and_delete_unused_reason(): void
    {
        $data = ['codigo' => 'DESMAMA', 'nome' => 'Desmama', 'descricao' => 'Peso na desmama', 'ativo' => 1];
        $this->post('/motivos-pesagem', $data)->assertSessionHasNoErrors()->assertRedirect();
        $motivo = MotivoPesagem::where('codigo', 'DESMAMA')->firstOrFail();
        $this->assertEquals($this->admin->tenant_id, $motivo->tenant_id);
        foreach (['/motivos-pesagem', '/motivos-pesagem/create', '/motivos-pesagem/'.$motivo->id, '/motivos-pesagem/'.$motivo->id.'/edit'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->put('/motivos-pesagem/'.$motivo->id, [...$data, 'nome' => 'Peso na desmama', 'ativo' => 0])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertFalse($motivo->fresh()->ativo);
        $this->assertSame('Peso na desmama', $motivo->fresh()->nome);
        $this->delete('/motivos-pesagem/'.$motivo->id)->assertRedirect('/motivos-pesagem');
        $this->assertDatabaseMissing('motivos_pesagem', ['id' => $motivo->id]);
    }

    public function test_reason_is_required_and_saved_in_each_weighing(): void
    {
        $data = $this->pesagemData();
        unset($data['motivo_pesagem_id']);
        $this->post('/pesagens', $data)->assertSessionHasErrors('motivo_pesagem_id');
        $this->assertSame(0, Pesagem::count());
        $this->post('/pesagens', $this->pesagemData())->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($this->motivo->id, Pesagem::firstOrFail()->motivo->id);
        $this->get('/pesagens')->assertOk()->assertSee($this->motivo->nome);
        $this->get('/animais/'.$this->animal->id)->assertOk()->assertSee($this->motivo->nome);
    }

    public function test_inactive_reason_is_hidden_and_rejected_but_history_is_kept(): void
    {
        $this->post('/pesagens', $this->pesagemData())->assertSessionHasNoErrors();
        $this->put('/motivos-pesagem/'.$this->motivo->id, ['codigo' => 'ROTINA', 'nome' => $this->motivo->nome, 'ativo' => 0])->assertSessionHasNoErrors();
        $this->post('/pesagens', $this->pesagemData(['data_pesagem' => now()->subMinutes(2)->format('Y-m-d\TH:i')]))->assertSessionHasErrors('motivo_pesagem_id');
        $this->get('/pesagens')->assertOk()->assertSee('Nenhum motivo de pesagem ativo')->assertSee($this->motivo->nome);
        $this->assertSame($this->motivo->id, Pesagem::firstOrFail()->motivo->id);
    }

    public function test_used_reason_cannot_be_deleted(): void
    {
        $this->post('/pesagens', $this->pesagemData())->assertSessionHasNoErrors();
        $this->delete('/motivos-pesagem/'.$this->motivo->id)->assertSessionHasErrors('motivo');
        $this->assertDatabaseHas('motivos_pesagem', ['id' => $this->motivo->id]);
        $this->assertSame(1, Pesagem::count());
    }

    public function test_vaqueiro_can_consult_reasons_and_record_weight_but_cannot_manage_reasons(): void
    {
        $this->actingAs(User::factory()->create(['tenant_id' => $this->admin->tenant_id, 'role' => 'Vaqueiro']));
        $this->get('/motivos-pesagem')->assertOk()->assertDontSee('Novo motivo');
        $this->get('/motivos-pesagem/'.$this->motivo->id)->assertOk()->assertDontSee('Editar motivo');
        $this->get('/motivos-pesagem/create')->assertForbidden();
        $this->post('/motivos-pesagem', [])->assertForbidden();
        $this->put('/motivos-pesagem/'.$this->motivo->id, [])->assertForbidden();
        $this->delete('/motivos-pesagem/'.$this->motivo->id)->assertForbidden();
        $this->post('/pesagens', $this->pesagemData())->assertSessionHasNoErrors();
    }

    public function test_other_tenant_reasons_are_inaccessible(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $foreign = MotivoPesagem::create(['codigo' => 'OUTRO', 'nome' => 'MOTIVO-OUTRO-TENANT', 'ativo' => true]);
        $this->actingAs($this->admin);
        $this->get('/motivos-pesagem')->assertOk()->assertDontSee($foreign->nome);
        $this->get('/pesagens')->assertOk()->assertDontSee($foreign->nome);
        $this->get('/motivos-pesagem/'.$foreign->id)->assertNotFound();
        $this->put('/motivos-pesagem/'.$foreign->id, [])->assertNotFound();
        $this->delete('/motivos-pesagem/'.$foreign->id)->assertNotFound();
        $this->post('/pesagens', $this->pesagemData(['motivo_pesagem_id' => $foreign->id]))->assertSessionHasErrors('motivo_pesagem_id');
    }

    public function test_code_and_name_are_unique_per_tenant_and_tenant_cannot_be_injected(): void
    {
        $data = ['codigo' => 'ROTINA', 'nome' => $this->motivo->nome, 'ativo' => 1];
        $this->post('/motivos-pesagem', $data)->assertSessionHasErrors(['codigo', 'nome']);
        $this->post('/motivos-pesagem', [...$data, 'tenant_id' => 999])->assertSessionHasErrors('tenant_id');
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->post('/motivos-pesagem', $data)->assertSessionHasNoErrors();
    }

    public function test_database_requires_reason_even_without_http_validation(): void
    {
        $this->expectException(QueryException::class);
        DB::transaction(fn () => DB::table('pesagens')->insert(['tenant_id' => $this->admin->tenant_id, 'animal_id' => $this->animal->id, 'peso' => 100, 'data_pesagem' => now()]));
    }

    public function test_database_rejects_cross_tenant_reason_foreign_key(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $foreign = MotivoPesagem::create(['codigo' => 'OUTRO', 'nome' => 'Outro motivo', 'ativo' => true]);
        $this->expectException(QueryException::class);
        DB::transaction(fn () => DB::table('pesagens')->insert(['tenant_id' => $this->admin->tenant_id, 'animal_id' => $this->animal->id, 'motivo_pesagem_id' => $foreign->id, 'peso' => 100, 'data_pesagem' => now()]));
    }
}
