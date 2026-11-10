<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Ponderal\Models\MotivoPesagem;
use App\Modules\Rebanho\Models\Animal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimalWeightHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Animal $animal;

    private MotivoPesagem $motivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->animal = Animal::create(['brinco' => 'PESO-1', 'sexo' => 'MACHO', 'categoria' => 'BEZERRO', 'status' => 'ATIVO']);
        $this->motivo = MotivoPesagem::create(['codigo' => 'ACOMP', 'nome' => 'Acompanhamento', 'ativo' => true]);
    }

    public function test_weight_chart_and_daily_gain_use_the_whole_history_even_on_later_pages(): void
    {
        $this->pesar('2025-01-21 08:00:00', '230.00');
        $this->pesar('2025-01-01 15:00:00', '200.00');
        for ($day = 2; $day <= 11; $day++) {
            $this->pesar(sprintf('2025-01-%02d 10:00:00', $day), '240.00');
        }

        $this->get('/animais/'.$this->animal->id.'?pesagens=2')
            ->assertOk()
            ->assertViewHas('evolucaoPeso', fn (array $series): bool => count($series) === 12
                && $series[0]['peso'] === 200.0 && $series[11]['peso'] === 230.0)
            ->assertViewHas('diasAcompanhados', 20)
            ->assertViewHas('ganhoMedioDiario', 1.5)
            ->assertSeeText('Evolução do peso')
            ->assertSeeText('1,50')
            ->assertSeeText('kg/dia');
    }

    public function test_daily_gain_preserves_weight_loss(): void
    {
        $this->pesar('2025-01-01 08:00:00', '300.00');
        $this->pesar('2025-01-11 08:00:00', '295.00');

        $this->get('/animais/'.$this->animal->id)->assertOk()
            ->assertViewHas('ganhoMedioDiario', -0.5)
            ->assertSeeText('-0,50');
    }

    public function test_readings_from_one_day_do_not_produce_a_daily_gain(): void
    {
        $this->pesar('2025-01-01 08:00:00', '300.00');
        $this->pesar('2025-01-01 15:00:00', '301.00');

        $this->get('/animais/'.$this->animal->id)->assertOk()
            ->assertViewHas('ganhoMedioDiario', null)
            ->assertSeeText('Registre pesagens em datas diferentes');
    }

    private function pesar(string $data, string $peso): void
    {
        $this->animal->pesagens()->create([
            'data_pesagem' => $data,
            'peso' => $peso,
            'motivo_pesagem_id' => $this->motivo->id,
        ]);
    }
}
