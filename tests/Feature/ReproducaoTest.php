<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Reproducao\Models\Gestacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReproducaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
    }

    public function test_coverage_exam_birth_and_genealogy_are_linked(): void
    {
        $avo = $this->animal('AVO', 'MACHO');
        $matriz = $this->animal('MATRIZ', 'FEMEA', ['pai_id' => $avo->id]);
        $touro = $this->animal('TOURO', 'MACHO');
        $cria = $this->animal('CRIA', 'FEMEA', ['data_nascimento' => today()->toDateString()]);

        $this->get('/gestacoes')->assertOk();
        $this->get('/gestacoes/create?matriz_id='.$matriz->id)->assertOk()->assertSee('MATRIZ');
        $ciclo = $this->cobertura($matriz, $touro);
        $url = '/gestacoes/'.$ciclo->id;
        $this->get($url)->assertOk()->assertSee('Aguardando confirmação')->assertSee('Monta natural');
        $this->post($url.'/exames', $this->exame(['resultado' => 'INCONCLUSIVO']))->assertSessionHasNoErrors();
        $this->assertSame('AGUARDANDO_EXAME', $ciclo->fresh()->status);
        $this->post($url.'/exames', $this->exame(['data_prevista_parto' => today()->addDays(3)->toDateString()]))
            ->assertSessionHasNoErrors();
        $this->assertSame('CONFIRMADA', $ciclo->fresh()->status);
        $this->assertSame(today()->subDay()->toDateString(), $ciclo->fresh()->data_confirmacao->toDateString());
        $this->get($url)->assertOk()->assertSee('Gestação confirmada')->assertSee('Inconclusivo')->assertSee('Positivo');
        $this->post($url.'/encerrar', ['status' => 'PARTO', 'data_encerramento' => today()->toDateString()])
            ->assertSessionHasNoErrors();
        $this->get($url)->assertOk()->assertSee('Vincular cria ao parto')->assertSee('CRIA');
        $this->post($url.'/crias', ['cria_id' => $cria->id])->assertSessionHasNoErrors();
        $this->post($url.'/crias', ['cria_id' => $cria->id])->assertSessionHasNoErrors();
        $this->assertSame($ciclo->id, $cria->fresh()->gestacao_nascimento_id);
        $this->assertSame($matriz->id, $cria->fresh()->mae_id);
        $this->assertSame($touro->id, $cria->fresh()->pai_id);
        $this->get('/animais/'.$cria->id.'/genealogia')->assertOk()->assertSee('AVO')->assertSee('Ver gestação de origem');
        $this->get('/animais/'.$matriz->id.'/genealogia')->assertOk()->assertSee('CRIA');
        $this->get('/gestacoes?animal_id='.$touro->id)->assertOk()->assertSee('MATRIZ');
    }

    public function test_negative_exam_closes_an_unconfirmed_cycle_and_allows_a_new_attempt(): void
    {
        $matriz = $this->animal('MATRIZ', 'FEMEA');
        $ciclo = $this->cobertura($matriz);
        $url = '/gestacoes/'.$ciclo->id;

        $this->assertNull($ciclo->reprodutor_id);
        $this->post('/gestacoes', $this->dadosCobertura($matriz))->assertSessionHasErrors('matriz_id');
        $this->post($url.'/exames', $this->exame(['resultado' => 'NEGATIVO']))->assertSessionHasNoErrors();
        $this->assertSame('SEM_PRENHEZ', $ciclo->fresh()->status);
        $this->assertNull($ciclo->fresh()->data_confirmacao);
        $this->post($url.'/exames', $this->exame())->assertSessionHasErrors('data_exame');
        $this->post('/gestacoes', $this->dadosCobertura($matriz))->assertSessionHasErrors('data_cobertura');
        $this->post('/gestacoes', $this->dadosCobertura($matriz, ['data_cobertura' => today()->subDay()->toDateString()]))->assertSessionHasNoErrors();
        $this->assertSame(2, Gestacao::count());
    }

    public function test_event_chronology_and_closure_are_validated(): void
    {
        $matriz = $this->animal('MATRIZ', 'FEMEA');
        $url = '/gestacoes/'.$this->cobertura($matriz)->id;
        $this->post($url.'/exames', $this->exame(['data_exame' => today()->subDays(301)->toDateString()]))->assertSessionHasErrors('data_exame');
        $this->post($url.'/encerrar', ['status' => 'PARTO', 'data_encerramento' => today()->toDateString()])->assertSessionHasErrors('status');
        $this->post($url.'/exames', $this->exame())->assertSessionHasNoErrors();
        $this->post($url.'/exames', $this->exame(['data_exame' => today()->subDays(2)->toDateString()]))->assertSessionHasErrors('data_exame');
        $this->post($url.'/encerrar', ['status' => 'PERDA', 'data_encerramento' => today()->toDateString()])->assertSessionHasErrors('observacao_encerramento');
        $this->post($url.'/encerrar', ['status' => 'PERDA', 'data_encerramento' => today()->toDateString(), 'observacao_encerramento' => 'Perda registrada'])->assertSessionHasNoErrors();
        $this->assertSame('PERDA', Gestacao::firstOrFail()->status);
        $this->assertSame(1, Gestacao::firstOrFail()->exames()->count());
    }

    public function test_cross_tenant_animals_and_cycles_are_inaccessible(): void
    {
        $matriz = $this->animal('MATRIZ', 'FEMEA');
        $ciclo = $this->cobertura($matriz);
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $minhaMatriz = $this->animal('OUTRA-MATRIZ', 'FEMEA');

        $this->get('/gestacoes/'.$ciclo->id)->assertNotFound();
        $this->get('/animais/'.$matriz->id.'/genealogia')->assertNotFound();
        $this->post('/gestacoes/'.$ciclo->id.'/exames', $this->exame())->assertNotFound();
        $this->post('/gestacoes', $this->dadosCobertura($matriz))->assertSessionHasErrors('matriz_id');
        $this->post('/gestacoes', $this->dadosCobertura($minhaMatriz, ['reprodutor_id' => $matriz->id]))->assertSessionHasErrors('reprodutor_id');
        $this->assertSame(0, Gestacao::count());
    }

    public function test_field_operator_can_record_exams_but_cannot_change_genealogy(): void
    {
        $matriz = $this->animal('MATRIZ', 'FEMEA');
        $admin = auth()->user();
        $this->actingAs(User::factory()->create(['role' => 'Vaqueiro', 'tenant_id' => $admin->tenant_id]));
        $ciclo = $this->cobertura($matriz);
        $this->post('/gestacoes/'.$ciclo->id.'/exames', $this->exame())->assertSessionHasNoErrors();
        $this->post('/gestacoes/'.$ciclo->id.'/crias', ['cria_id' => $matriz->id])->assertForbidden();
    }

    public function test_linking_a_birth_does_not_overwrite_conflicting_parentage_or_allow_a_different_birth_date(): void
    {
        $matriz = $this->animal('MATRIZ', 'FEMEA');
        $outraMatriz = $this->animal('OUTRA-MATRIZ', 'FEMEA');
        $cria = $this->animal('CRIA', 'FEMEA', ['mae_id' => $outraMatriz->id, 'data_nascimento' => today()->toDateString()]);
        $ciclo = $this->cobertura($matriz);
        $url = '/gestacoes/'.$ciclo->id;

        $this->post($url.'/exames', $this->exame())->assertSessionHasNoErrors();
        $this->post($url.'/encerrar', ['status' => 'PARTO', 'data_encerramento' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->post($url.'/crias', ['cria_id' => $cria->id])->assertSessionHasErrors('cria_id');
        $this->assertSame($outraMatriz->id, $cria->fresh()->mae_id);

        $cria->update(['mae_id' => null]);
        $this->post($url.'/crias', ['cria_id' => $cria->id])->assertSessionHasNoErrors();
        $this->put('/animais/'.$cria->id, [
            'brinco' => 'CRIA', 'sexo' => 'FEMEA', 'status' => 'ATIVO', 'categoria' => 'BEZERRA',
            'mae_id' => $matriz->id, 'data_nascimento' => today()->subDay()->toDateString(),
        ])->assertSessionHasErrors('data_nascimento');
        $this->assertSame(today()->toDateString(), $cria->fresh()->data_nascimento->toDateString());
    }

    private function animal(string $brinco, string $sexo, array $dados = []): Animal
    {
        return Animal::create([
            'brinco' => $brinco, 'sexo' => $sexo, 'categoria' => $sexo === 'MACHO' ? 'TOURO' : 'VACA',
            'status' => 'ATIVO', 'data_nascimento' => today()->subYears(4)->toDateString(), ...$dados,
        ]);
    }

    private function dadosCobertura(Animal $matriz, array $dados = []): array
    {
        return [
            'matriz_id' => $matriz->id, 'tipo' => 'INSEMINACAO', 'data_cobertura' => today()->subDays(300)->toDateString(), ...$dados,
        ];
    }

    private function cobertura(Animal $matriz, ?Animal $touro = null): Gestacao
    {
        $dados = $touro === null ? ['referencia_semen' => 'LOTE-1'] : ['tipo' => 'MONTA', 'reprodutor_id' => $touro->id];
        $this->post('/gestacoes', $this->dadosCobertura($matriz, $dados))->assertSessionHasNoErrors();

        return Gestacao::where('matriz_id', $matriz->id)->firstOrFail();
    }

    private function exame(array $dados = []): array
    {
        return ['data_exame' => today()->subDay()->toDateString(), 'resultado' => 'POSITIVO', 'metodo' => 'Ultrassonografia', ...$dados];
    }
}
