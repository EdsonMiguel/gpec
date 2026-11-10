<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Rebanho\Models\Lote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
    }

    public function test_admin_can_create_and_edit_a_lot_without_rejecting_its_own_name(): void
    {
        $this->get('/lotes/create')->assertOk();
        $this->post('/lotes', ['nome' => 'Recria', 'descricao' => 'Lote de recria'])->assertRedirect('/lotes');
        $lote = Lote::firstOrFail();
        $this->get('/lotes/'.$lote->id.'/edit')->assertOk()->assertSee('Recria');
        $this->put('/lotes/'.$lote->id, ['nome' => 'Recria', 'descricao' => 'Descrição atualizada'])->assertSessionHasNoErrors()->assertRedirect('/lotes');
        $this->assertSame('Descrição atualizada', $lote->fresh()->descricao);
        $this->post('/lotes', ['nome' => 'Recria'])->assertSessionHasErrors('nome');
    }

    public function test_lot_link_filters_the_herd_and_search_preserves_the_selected_lot(): void
    {
        $lote = Lote::create(['nome' => 'Recria']);
        Animal::create(['brinco' => 'DENTRO', 'sexo' => 'MACHO', 'categoria' => 'GARROTE', 'status' => 'ATIVO', 'lote_id' => $lote->id]);
        Animal::create(['brinco' => 'FORA', 'sexo' => 'MACHO', 'categoria' => 'BOI', 'status' => 'ATIVO']);
        $this->get('/lotes')->assertOk()->assertSeeText('1 animal');
        $this->get('/animais?lote_id='.$lote->id)->assertOk()->assertSeeText('DENTRO')->assertDontSeeText('FORA');
        $this->get('/animais?lote_id='.$lote->id.'&busca=FORA')->assertOk()->assertSeeText('Nenhum animal encontrado');
    }

    public function test_other_tenant_lots_are_hidden_and_field_operator_only_has_read_access(): void
    {
        $admin = auth()->user();
        $lote = Lote::create(['nome' => 'Privado']);
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->get('/lotes/'.$lote->id.'/edit')->assertNotFound();
        $this->put('/lotes/'.$lote->id, ['nome' => 'Outro'])->assertNotFound();
        $this->get('/animais?lote_id='.$lote->id)->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => 'Vaqueiro', 'tenant_id' => $admin->tenant_id]));
        $this->get('/lotes')->assertOk()->assertSeeText('Privado');
        $this->get('/lotes/create')->assertForbidden();
        $this->put('/lotes/'.$lote->id, ['nome' => 'Outro'])->assertForbidden();
    }
}
