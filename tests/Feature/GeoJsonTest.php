<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Fazendas\Models\Area;
use App\Modules\Fazendas\Models\Fazenda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GeoJsonTest extends TestCase
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

    private function geometry(): array
    {
        return ['type' => 'Polygon', 'coordinates' => [[[-48, -20], [-47.99, -20], [-47.99, -19.99], [-48, -20]]]];
    }

    private function geojson(): string
    {
        return json_encode(['type' => 'FeatureCollection', 'features' => [['type' => 'Feature', 'properties' => (object) [], 'geometry' => $this->geometry()]]]);
    }

    private function farm(array $attributes = []): Fazenda
    {
        return Fazenda::create(['nome' => 'Fazenda de teste', 'ativo' => true, ...$attributes]);
    }

    public function test_farm_stores_raw_json_object_and_reloads_the_geometry_for_editing(): void
    {
        $this->post('/fazendas', ['nome' => 'Fazenda geográfica', 'ativo' => 1, 'geojson' => $this->geojson()])->assertSessionHasNoErrors()->assertRedirect();
        $farm = Fazenda::firstOrFail();
        $this->assertEquals(json_decode($this->geojson()), $farm->geojson);
        $this->assertIsObject($farm->geojson->features[0]->properties);
        $raw = DB::table('fazendas')->where('id', $farm->id)->value('geojson');
        $this->assertSame('FeatureCollection', json_decode($raw)->type);
        $this->get('/fazendas/'.$farm->id.'/edit')->assertOk()->assertSee('data-geojson-map', false)->assertSee('FeatureCollection');
        $this->get('/fazendas/'.$farm->id)->assertOk()->assertSee('data-readonly="true"', false);
    }

    public function test_area_stores_its_geometry_and_receives_farm_outline_as_context(): void
    {
        $farm = $this->farm(['geojson' => json_decode($this->geojson())]);
        $this->post('/fazendas/'.$farm->id.'/areas', ['nome' => 'Pasto mapeado', 'tipo' => 'PASTO', 'ativo' => 1, 'geojson' => $this->geojson()])->assertSessionHasNoErrors();
        $area = Area::firstOrFail();
        $this->assertEquals($farm->geojson, $area->geojson);
        $this->get('/fazendas/'.$farm->id.'/areas/'.$area->id.'/edit')->assertOk()->assertSee('FeatureCollection')->assertSee('data-context=', false);
    }

    public function test_geojson_is_optional_can_be_cleared_and_is_preserved_when_omitted(): void
    {
        $farm = $this->farm(['geojson' => json_decode($this->geojson())]);
        $this->put('/fazendas/'.$farm->id, ['nome' => $farm->nome, 'ativo' => 1])->assertSessionHasNoErrors();
        $this->assertNotNull($farm->fresh()->geojson);
        $this->put('/fazendas/'.$farm->id, ['nome' => $farm->nome, 'ativo' => 1, 'geojson' => ''])->assertSessionHasNoErrors();
        $this->assertNull($farm->fresh()->geojson);
        $this->post('/fazendas/'.$farm->id.'/areas', ['nome' => 'Sem contorno', 'tipo' => 'CURRAL', 'ativo' => 1])->assertSessionHasNoErrors();
        $this->assertNull(Area::firstOrFail()->geojson);
    }

    public function test_feature_polygon_and_multipolygon_documents_are_accepted(): void
    {
        $farm = $this->farm();
        foreach ([$this->geometry(), ['type' => 'Feature', 'properties' => (object) [], 'geometry' => $this->geometry()], ['type' => 'MultiPolygon', 'coordinates' => [$this->geometry()['coordinates']]]] as $document) {
            $this->put('/fazendas/'.$farm->id, ['nome' => $farm->nome, 'ativo' => 1, 'geojson' => json_encode($document)])->assertSessionHasNoErrors();
            $this->assertSame($document['type'], $farm->fresh()->geojson->type);
        }
    }

    public function test_malformed_or_non_area_geojson_is_rejected_without_changing_saved_geometry(): void
    {
        $farm = $this->farm(['geojson' => json_decode($this->geojson())]);
        foreach (['{invalid', '"text"', '42', '[]', '{"type":"Point","coordinates":[-48,-20]}', '{"type":"FeatureCollection","features":[]}', '{"type":"Feature","geometry":null}', '{"type":"Feature","properties":[],"geometry":'.json_encode($this->geometry()).'}', '{"type":"Feature","properties":{"value":1e10000},"geometry":'.json_encode($this->geometry()).'}'] as $document) {
            $this->put('/fazendas/'.$farm->id, ['nome' => $farm->nome, 'ativo' => 1, 'geojson' => $document])->assertSessionHasErrors('geojson');
        }
        $this->assertEquals(json_decode($this->geojson()), $farm->fresh()->geojson);
    }

    public function test_out_of_range_unclosed_degenerate_and_non_numeric_rings_are_rejected(): void
    {
        $farm = $this->farm();
        foreach ([
            [[[-200, -20], [-48, -20], [-48, -19], [-200, -20]]],
            [[[-48, -100], [-47, -100], [-47, -20], [-48, -100]]],
            [[[-48, -20], [-47, -20], [-47, -19]]],
            [[[-48, -20], [-47, -20], [-47, -19], [-48, -19]]],
            [[[1, 1], [2, 2], [3, 3], [1, 1]]],
            [[['-48', -20], [-47, -20], [-47, -19], ['-48', -20]]],
        ] as $coordinates) {
            $this->put('/fazendas/'.$farm->id, ['nome' => $farm->nome, 'ativo' => 1, 'geojson' => json_encode(['type' => 'Polygon', 'coordinates' => $coordinates])])->assertSessionHasErrors('geojson');
        }
    }

    public function test_payload_size_and_vertex_limits_are_enforced(): void
    {
        $farm = $this->farm();
        $large = ['type' => 'Feature', 'properties' => ['text' => str_repeat('a', 512001)], 'geometry' => $this->geometry()];
        $this->put('/fazendas/'.$farm->id, ['nome' => $farm->nome, 'ativo' => 1, 'geojson' => json_encode($large)])->assertSessionHasErrors('geojson');
        $ring = array_fill(0, 10001, [-48, -20]);
        $ring[1] = [-47, -20];
        $ring[2] = [-47, -19];
        $this->put('/fazendas/'.$farm->id, ['nome' => $farm->nome, 'ativo' => 1, 'geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [$ring]])])->assertSessionHasErrors('geojson');
    }

    public function test_geojson_updates_respect_tenant_and_role_permissions(): void
    {
        $farm = $this->farm();
        $area = $farm->areas()->create(['nome' => 'Pasto', 'tipo' => 'PASTO', 'ativo' => true]);
        $this->actingAs(User::factory()->create(['tenant_id' => $this->admin->tenant_id, 'role' => 'Vaqueiro']));
        $this->put('/fazendas/'.$farm->id, ['nome' => $farm->nome, 'ativo' => 1, 'geojson' => $this->geojson()])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->put('/fazendas/'.$farm->id, ['nome' => $farm->nome, 'ativo' => 1, 'geojson' => $this->geojson()])->assertNotFound();
        $this->put('/fazendas/'.$farm->id.'/areas/'.$area->id, ['nome' => 'Pasto', 'tipo' => 'PASTO', 'ativo' => 1, 'geojson' => $this->geojson()])->assertNotFound();
    }
}
