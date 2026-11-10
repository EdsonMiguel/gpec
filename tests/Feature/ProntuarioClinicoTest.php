<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Sanidade\Models\CasoClinico;
use App\Modules\Sanidade\Models\Doenca;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProntuarioClinicoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Animal $animal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($this->admin);
        $this->animal = Animal::create([
            'brinco' => 'CLIN-01', 'sexo' => 'FEMEA', 'categoria' => 'VACA', 'status' => 'ATIVO',
            'data_nascimento' => today()->subYears(3)->toDateString(),
        ]);
    }

    public function test_case_preserves_symptoms_veterinary_evaluations_and_outcome(): void
    {
        $this->get('/sanidade')->assertOk()->assertSeeText('Prontuário')->assertSeeText('Doenças');
        $this->get('/doencas/create')->assertOk();
        $this->post('/doencas', ['nome' => 'Condição de teste', 'ativo' => 1])->assertSessionHasNoErrors();
        $doenca = Doenca::firstOrFail();
        $this->get('/sanidade/casos/create?animal_id='.$this->animal->id)->assertOk()->assertSee('CLIN-01');

        $caso = $this->abrirCaso();
        $url = '/sanidade/casos/'.$caso->id;
        $this->assertSame('ABERTO', $caso->status);
        $this->get('/animais')->assertOk()->assertSeeText('Caso clínico ativo');
        $this->get('/animais/'.$this->animal->id)->assertOk()->assertSeeText('1 caso clínico ativo');
        $this->post($url.'/avaliacoes', $this->avaliacao([
            'data_avaliacao' => today()->subDay()->toDateString(),
            'doenca_id' => $doenca->id,
            'situacao_diagnostico' => 'SUSPEITO',
        ]))->assertSessionHasNoErrors();
        $this->post($url.'/avaliacoes', $this->avaliacao([
            'parecer' => 'Avaliação de retorno', 'doenca_id' => $doenca->id, 'situacao_diagnostico' => 'CONFIRMADO',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('EM_ACOMPANHAMENTO', $caso->fresh()->status);
        $this->assertSame(2, $caso->avaliacoes()->count());
        $this->get($url)->assertOk()->assertSeeText('Sintoma informado')->assertSeeText('Veterinária de teste')
            ->assertSeeText('Avaliação de retorno')->assertSeeText('Parecer inicial')->assertSeeText('Orientação anotada')
            ->assertSeeText('Suspeito')->assertSeeText('Confirmado');

        $this->post($url.'/encerrar', $this->encerramento())->assertSessionHasNoErrors();
        $this->assertSame('ENCERRADO', $caso->fresh()->status);
        $this->get($url)->assertOk()->assertSeeText('Recuperação')->assertSeeText('Avaliação de retorno')
            ->assertDontSeeText('Salvar avaliação');
        $this->get('/sanidade/casos')->assertOk()->assertDontSeeText('Caso de teste');
        $this->get('/sanidade/casos?animal_id='.$this->animal->id)->assertOk()->assertSeeText('Caso de teste');
        $this->get('/animais')->assertOk()->assertDontSeeText('Caso clínico ativo');
    }

    public function test_chronology_and_terminal_state_are_enforced(): void
    {
        $caso = $this->abrirCaso();
        $url = '/sanidade/casos/'.$caso->id;
        $this->post($url.'/avaliacoes', $this->avaliacao(['data_avaliacao' => today()->subDays(3)->toDateString()]))->assertSessionHasErrors('data_avaliacao');
        $this->post($url.'/avaliacoes', $this->avaliacao())->assertSessionHasNoErrors();
        $this->post($url.'/encerrar', $this->encerramento(['data_encerramento' => today()->subDay()->toDateString()]))->assertSessionHasErrors('data_encerramento');
        $this->post($url.'/encerrar', $this->encerramento())->assertSessionHasNoErrors();
        $this->post($url.'/avaliacoes', $this->avaliacao())->assertSessionHasErrors('caso');
        $this->post($url.'/encerrar', $this->encerramento())->assertSessionHasErrors('caso');
        $this->assertSame(1, $caso->avaliacoes()->count());
    }

    public function test_disease_can_be_inactivated_without_erasing_previous_diagnoses(): void
    {
        $caso = $this->abrirCaso();
        $url = '/sanidade/casos/'.$caso->id;
        $doenca = Doenca::create(['nome' => 'Condição cadastrada', 'ativo' => true]);
        $this->post($url.'/avaliacoes', $this->avaliacao(['doenca_id' => $doenca->id]))->assertSessionHasErrors('situacao_diagnostico');
        $this->post($url.'/avaliacoes', $this->avaliacao(['situacao_diagnostico' => 'SUSPEITO']))->assertSessionHasErrors('situacao_diagnostico');
        $dados = $this->avaliacao(['doenca_id' => $doenca->id, 'situacao_diagnostico' => 'SUSPEITO']);
        $this->post($url.'/avaliacoes', $dados)->assertSessionHasNoErrors();
        $this->get('/doencas/'.$doenca->id.'/edit')->assertOk();
        $this->put('/doencas/'.$doenca->id, ['nome' => $doenca->nome, 'ativo' => 0])->assertSessionHasNoErrors();
        $this->post($url.'/avaliacoes', $dados)->assertSessionHasErrors('doenca_id');
        $this->get($url)->assertOk()->assertSeeText('Condição cadastrada')->assertSeeText('Suspeito');
        $this->assertSame(1, $caso->avaliacoes()->count());
    }

    public function test_case_access_and_disease_references_are_isolated_by_tenant(): void
    {
        $caso = $this->abrirCaso();
        $doenca = Doenca::create(['nome' => 'Doença isolada', 'ativo' => true]);
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->get('/sanidade/casos/'.$caso->id)->assertNotFound();
        $this->post('/sanidade/casos/'.$caso->id.'/avaliacoes', $this->avaliacao())->assertNotFound();
        $this->get('/doencas/'.$doenca->id.'/edit')->assertNotFound();
        $this->post('/sanidade/casos', $this->dadosCaso())->assertSessionHasErrors('animal_id');
        $this->animal = Animal::create(['brinco' => 'CLIN-02', 'sexo' => 'FEMEA', 'categoria' => 'VACA', 'status' => 'ATIVO']);
        $casoLocal = $this->abrirCaso();
        $this->post('/sanidade/casos/'.$casoLocal->id.'/avaliacoes', $this->avaliacao([
            'doenca_id' => $doenca->id, 'situacao_diagnostico' => 'SUSPEITO',
        ]))->assertSessionHasErrors('doenca_id');
    }

    public function test_field_operator_can_open_cases_and_log_evaluations_but_not_manage_diseases(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Vaqueiro', 'tenant_id' => $this->admin->tenant_id]));
        $this->get('/doencas')->assertForbidden();
        $this->post('/doencas', ['nome' => 'Condição', 'ativo' => 1])->assertForbidden();
        $caso = $this->abrirCaso();
        $this->post('/sanidade/casos/'.$caso->id.'/avaliacoes', $this->avaliacao())->assertSessionHasNoErrors();
        $this->assertNull($caso->avaliacoes()->firstOrFail()->doenca_id);
    }

    private function dadosCaso(): array
    {
        return [
            'brinco' => $this->animal->brinco, 'titulo' => 'Caso de teste',
            'data_inicio' => today()->subDays(2)->toDateString(), 'sintomas_iniciais' => 'Sintoma informado',
        ];
    }

    private function abrirCaso(): CasoClinico
    {
        $this->post('/sanidade/casos', $this->dadosCaso())->assertSessionHasNoErrors();

        return CasoClinico::firstOrFail();
    }

    private function avaliacao(array $dados = []): array
    {
        return [
            'data_avaliacao' => today()->toDateString(), 'veterinario' => 'Veterinária de teste',
            'parecer' => 'Parecer inicial', 'orientacao' => 'Orientação anotada', ...$dados,
        ];
    }

    private function encerramento(array $dados = []): array
    {
        return [
            'data_encerramento' => today()->toDateString(), 'desfecho' => 'RECUPERADO',
            'observacao_encerramento' => 'Motivo registrado', ...$dados,
        ];
    }
}
