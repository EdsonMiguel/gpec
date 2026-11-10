<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Ponderal\Models\MotivoPesagem;
use App\Modules\Rebanho\Models\Animal;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MotivoPesagemMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_migration_preserves_legacy_weights_and_backfills_reason_per_tenant(): void
    {
        $migration = require database_path('migrations/2026_10_29_000005_create_motivos_pesagem_table.php');
        $migration->down();
        $users = [User::factory()->create(['role' => 'Admin']), User::factory()->create(['role' => 'Admin'])];
        $ids = [];
        foreach ($users as $user) {
            $this->actingAs($user);
            $animal = Animal::create(['brinco' => 'ANTIGO', 'categoria' => 'VACA', 'sexo' => 'FEMEA', 'status' => 'ATIVO']);
            $ids[] = DB::table('pesagens')->insertGetId(['tenant_id' => $user->tenant_id, 'animal_id' => $animal->id, 'peso' => '321.50', 'data_pesagem' => now()->subDay()]);
        }
        $migration->up();
        $this->assertDatabaseCount('pesagens', 2);
        $this->assertDatabaseCount('motivos_pesagem', 2);
        foreach ($users as $index => $user) {
            $this->actingAs($user);
            $motivo = MotivoPesagem::firstOrFail();
            $this->assertSame('LEGADO', $motivo->codigo);
            $this->assertFalse($motivo->ativo);
            $this->assertDatabaseHas('pesagens', ['id' => $ids[$index], 'tenant_id' => $user->tenant_id, 'motivo_pesagem_id' => $motivo->id, 'peso' => '321.50']);
        }
        $this->assertSame(0, DB::table('pesagens')->whereNull('motivo_pesagem_id')->count());
    }
}
