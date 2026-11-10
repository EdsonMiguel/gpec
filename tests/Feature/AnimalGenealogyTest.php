<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Rebanho\Models\Animal;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnimalGenealogyTest extends TestCase
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

    private function animal(string $brinco, string $sexo = 'MACHO', array $attributes = []): Animal
    {
        return Animal::create([
            'brinco' => $brinco, 'sexo' => $sexo, 'categoria' => $sexo === 'MACHO' ? 'TOURO' : 'VACA',
            'status' => 'ATIVO', ...$attributes,
        ]);
    }

    private function payload(array $attributes = []): array
    {
        return ['brinco' => 'CRIA-001', 'categoria' => 'BEZERRO', 'sexo' => 'MACHO', 'status' => 'ATIVO', ...$attributes];
    }

    public function test_parents_are_optional_and_can_be_set_independently(): void
    {
        $father = $this->animal('PAI');
        $mother = $this->animal('MAE', 'FEMEA');
        $this->post('/animais', $this->payload())->assertSessionHasNoErrors()->assertRedirect();
        $child = Animal::where('brinco', 'CRIA-001')->firstOrFail();
        $this->assertNull($child->pai_id);
        $this->assertNull($child->mae_id);
        $this->put('/animais/'.$child->id, $this->payload(['pai_id' => $father->id]))->assertSessionHasNoErrors();
        $this->assertSame($father->id, $child->fresh()->pai->id);
        $this->assertNull($child->fresh()->mae_id);
        $this->put('/animais/'.$child->id, $this->payload(['mae_id' => $mother->id]))->assertSessionHasNoErrors();
        $this->assertSame($father->id, $child->fresh()->pai_id);
        $this->assertSame($mother->id, $child->fresh()->mae->id);
        $this->put('/animais/'.$child->id, $this->payload(['pai_id' => '', 'mae_id' => '']))->assertSessionHasNoErrors();
        $this->assertNull($child->fresh()->pai_id);
        $this->assertNull($child->fresh()->mae_id);
    }

    public function test_creation_accepts_both_parents_and_displays_links_in_animal_details(): void
    {
        $father = $this->animal('PAI-IDENTIFICADO', 'MACHO', ['data_nascimento' => '2020-01-01']);
        $mother = $this->animal('MAE-IDENTIFICADA', 'FEMEA', ['data_nascimento' => '2021-01-01']);
        $this->post('/animais', $this->payload(['pai_id' => $father->id, 'mae_id' => $mother->id, 'data_nascimento' => '2025-01-01']))->assertSessionHasNoErrors()->assertRedirect();
        $child = Animal::where('brinco', 'CRIA-001')->firstOrFail();
        $this->get('/animais/'.$child->id)->assertOk()->assertSee('PAI-IDENTIFICADO')->assertSee('MAE-IDENTIFICADA');
        $this->get('/animais/'.$child->id.'/edit')->assertOk()->assertSee('pai_id')->assertSee('mae_id');
    }

    public function test_sex_of_selected_parents_is_validated(): void
    {
        $father = $this->animal('PAI');
        $mother = $this->animal('MAE', 'FEMEA');
        $this->post('/animais', $this->payload(['pai_id' => $mother->id]))->assertSessionHasErrors('pai_id');
        $this->post('/animais', $this->payload(['mae_id' => $father->id]))->assertSessionHasErrors('mae_id');
        $this->assertSame(2, Animal::count());
    }

    public function test_animal_cannot_be_its_own_parent(): void
    {
        $animal = $this->animal('CRIA-001');
        $this->put('/animais/'.$animal->id, $this->payload(['pai_id' => $animal->id]))->assertSessionHasErrors('pai_id');
        $this->assertNull($animal->fresh()->pai_id);
        $mother = $this->animal('MATRIZ', 'FEMEA');
        $this->put('/animais/'.$mother->id, $this->payload(['brinco' => 'MATRIZ', 'sexo' => 'FEMEA', 'mae_id' => $mother->id]))->assertSessionHasErrors('mae_id');
    }

    public function test_direct_and_indirect_cycles_are_rejected_when_dates_are_unknown(): void
    {
        $grandfather = $this->animal('AVO');
        $mother = $this->animal('MAE', 'FEMEA', ['pai_id' => $grandfather->id]);
        $child = $this->animal('CRIA-001', 'MACHO', ['mae_id' => $mother->id]);
        $this->put('/animais/'.$grandfather->id, $this->payload(['brinco' => 'AVO', 'pai_id' => $child->id]))->assertSessionHasErrors('pai_id');
        $this->put('/animais/'.$mother->id, $this->payload(['brinco' => 'MAE', 'sexo' => 'FEMEA', 'pai_id' => $child->id]))->assertSessionHasErrors('pai_id');
        $this->assertNull($grandfather->fresh()->pai_id);
        $this->assertSame($grandfather->id, $mother->fresh()->pai_id);
    }

    public function test_parents_must_be_older_when_birth_dates_are_known(): void
    {
        $father = $this->animal('PAI', 'MACHO', ['data_nascimento' => '2025-01-01']);
        $mother = $this->animal('MAE', 'FEMEA', ['data_nascimento' => '2024-01-01']);
        $this->post('/animais', $this->payload(['pai_id' => $father->id, 'data_nascimento' => '2025-01-01']))->assertSessionHasErrors('pai_id');
        $this->post('/animais', $this->payload(['mae_id' => $mother->id, 'data_nascimento' => '2023-01-01']))->assertSessionHasErrors('mae_id');
        $this->post('/animais', $this->payload(['pai_id' => $father->id, 'mae_id' => $mother->id]))->assertSessionHasNoErrors();
    }

    public function test_edits_cannot_invalidate_existing_children_even_if_archived(): void
    {
        $father = $this->animal('PAI', 'MACHO', ['data_nascimento' => '2020-01-01']);
        $mother = $this->animal('MAE', 'FEMEA', ['data_nascimento' => '2020-01-01']);
        $child = $this->animal('CRIA-001', 'MACHO', ['pai_id' => $father->id, 'mae_id' => $mother->id, 'data_nascimento' => '2025-01-01']);
        $child->delete();
        $this->put('/animais/'.$father->id, $this->payload(['brinco' => 'PAI', 'sexo' => 'FEMEA']))->assertSessionHasErrors('sexo');
        $this->put('/animais/'.$mother->id, $this->payload(['brinco' => 'MAE', 'sexo' => 'MACHO']))->assertSessionHasErrors('sexo');
        $this->put('/animais/'.$father->id, $this->payload(['brinco' => 'PAI', 'data_nascimento' => '2025-01-01']))->assertSessionHasErrors('data_nascimento');
        $this->assertSame('MACHO', $father->fresh()->sexo);
        $this->assertSame('2020-01-01', $father->fresh()->data_nascimento->toDateString());
    }

    public function test_changing_child_birth_date_respects_existing_parent_relationship(): void
    {
        $father = $this->animal('PAI', 'MACHO', ['data_nascimento' => '2020-01-01']);
        $child = $this->animal('CRIA-001', 'MACHO', ['pai_id' => $father->id, 'data_nascimento' => '2025-01-01']);
        $this->put('/animais/'.$child->id, $this->payload(['data_nascimento' => '2019-01-01']))->assertSessionHasErrors('pai_id');
    }

    public function test_archived_and_inactive_parents_are_available_and_preserved(): void
    {
        $father = $this->animal('PAI-ARQUIVADO', 'MACHO', ['status' => 'MORTO']);
        $mother = $this->animal('MAE-VENDIDA', 'FEMEA', ['status' => 'VENDIDO']);
        $father->delete();
        $this->get('/animais/create')->assertOk()->assertSee('PAI-ARQUIVADO')->assertSee('MAE-VENDIDA');
        $this->post('/animais', $this->payload(['pai_id' => $father->id, 'mae_id' => $mother->id]))->assertSessionHasNoErrors();
        $child = Animal::where('brinco', 'CRIA-001')->firstOrFail();
        $this->assertSame($father->id, $child->pai->id);
        $this->assertTrue($child->pai->trashed());
        $this->get('/animais/'.$child->id)->assertOk()->assertSee('PAI-ARQUIVADO')->assertSee('Arquivado');
    }

    public function test_other_tenant_parents_are_not_listed_and_cannot_be_submitted(): void
    {
        $other = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($other);
        $father = $this->animal('PAI-OUTRO-TENANT');
        $mother = $this->animal('MAE-OUTRO-TENANT', 'FEMEA');
        $this->actingAs($this->admin);
        $this->get('/animais/create')->assertOk()->assertDontSee($father->brinco)->assertDontSee($mother->brinco);
        $this->post('/animais', $this->payload(['pai_id' => $father->id, 'mae_id' => $mother->id]))->assertSessionHasErrors(['pai_id', 'mae_id']);
        $this->assertSame(0, Animal::count());
    }

    public function test_database_rejects_cross_tenant_parent_foreign_key(): void
    {
        $father = $this->animal('PAI');
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $child = $this->animal('FILHO');
        $this->expectException(QueryException::class);
        DB::transaction(fn () => DB::table('animais')->where('id', $child->id)->update(['pai_id' => $father->id]));
    }
}
