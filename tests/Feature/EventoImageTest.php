<?php

namespace Tests\Feature;

use App\Models\{Evento, Grado};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\AuthenticatedTestCase as TestCase;

class EventoImageTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        $grado = Grado::firstOrCreate(['nombre' => 'Kyu']);
        return ['nombre' => 'Seminario Internacional', 'fecha_inicio' => '2026-12-02', 'fecha_final' => '2026-12-05', 'lugar' => 'Coliseo La Pampita', 'ciudad' => 'Trinidad, Beni', 'tipo_cambio' => '1', 'costos_usd' => [$grado->id => '75.00']];
    }

    public function test_image_upload_display_preservation_replacement_and_removal(): void
    {
        Storage::fake('local');
        $data = $this->data();
        $this->post('/eventos', $data + ['imagen' => UploadedFile::fake()->image('afiche.png')])->assertSessionHasNoErrors()->assertRedirect('/eventos');
        $evento = Evento::firstOrFail(); $original = $evento->imagen;
        Storage::disk('local')->assertExists($original);
        $this->get(route('eventos.imagen', $evento))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Content-Type', 'image/png');
        $this->get('/eventos')->assertOk()->assertSee('event-table')->assertSee(route('eventos.imagen', $evento), false);
        $this->get("/eventos/{$evento->id}/edit")->assertOk()->assertSee('multipart/form-data');
        $this->put("/eventos/{$evento->id}", $data)->assertSessionHasNoErrors();
        $this->assertSame($original, $evento->fresh()->imagen);
        $this->put("/eventos/{$evento->id}", $data + ['imagen' => UploadedFile::fake()->image('nuevo.jpg')])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($original);
        $replacement = $evento->fresh()->imagen;
        Storage::disk('local')->assertExists($replacement);
        $this->put("/eventos/{$evento->id}", $data + ['quitar_imagen' => '1'])->assertSessionHasNoErrors();
        $this->assertNull($evento->fresh()->imagen);
        Storage::disk('local')->assertMissing($replacement);
        $this->get(route('eventos.imagen', $evento))->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_invalid_or_oversized_uploads_do_not_change_existing_image(): void
    {
        Storage::fake('local'); $data = $this->data();
        $this->post('/eventos', $data + ['imagen' => UploadedFile::fake()->image('original.png')])->assertSessionHasNoErrors();
        $evento = Evento::firstOrFail(); $original = $evento->imagen;
        foreach ([UploadedFile::fake()->create('archivo.txt', 1, 'text/plain'), UploadedFile::fake()->image('grande.jpg')->size(10241), UploadedFile::fake()->image('ancha.png', 12001, 1)] as $file) {
            $this->put("/eventos/{$evento->id}", $data + ['imagen' => $file])->assertSessionHasErrors('imagen');
            $this->assertSame($original, $evento->fresh()->imagen);
        }
        $this->assertCount(1, Storage::disk('local')->allFiles('eventos'));
    }

    public function test_image_at_new_weight_and_dimension_limits_is_accepted(): void
    {
        Storage::fake('local');
        $this->post('/eventos', $this->data() + ['imagen' => UploadedFile::fake()->image('limite.png', 12000, 1)->size(10240)])
            ->assertSessionHasNoErrors()->assertRedirect('/eventos');
        Storage::disk('local')->assertExists(Evento::firstOrFail()->imagen);
        $this->get('/eventos/create')->assertOk()->assertSee('Máximo 10 MB y 12000 × 12000 píxeles');
    }

    public function test_edit_shows_costs_by_category_and_preserves_other_tariffs(): void
    {
        $grado = Grado::create(['nombre' => '1er Dan']);
        $data = $this->data();
        $customId = array_key_first($data['costos_usd']);
        $data['costos_usd'][$grado->id] = '50.00';
        $data['tipo_cambio'] = '11.70';
        $this->post('/eventos', $data)->assertSessionHasNoErrors();
        $evento = Evento::firstOrFail();
        $this->get("/eventos/{$evento->id}/edit")->assertOk()
            ->assertSee('Costo por categoría')->assertSeeInOrder(['Categoría', 'Costo $us', 'Costo Bs.-', 'Kyu', 'Dan', 'Jefes de Dojo'])
            ->assertDontSee('Costo en dólares</label>', false)->assertSee('value="75.00"', false);
        $data['tipo_cambio'] = '12.00';
        $this->put("/eventos/{$evento->id}", $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('evento_grado', ['evento_id' => $evento->id, 'grado_id' => $customId, 'costo_usd' => '75.00', 'costo' => '900.00']);
        $this->assertDatabaseHas('evento_grado', ['evento_id' => $evento->id, 'grado_id' => $grado->id, 'costo_usd' => '50.00', 'costo' => '600.00']);
    }

    public function test_new_event_shows_categories_and_saves_their_tariffs(): void
    {
        $kyu = Grado::create(['nombre' => '10mo Kyu']);
        $dan = Grado::create(['nombre' => '1er Dan']);
        $adicional = Grado::create(['nombre' => '8vo Kyu']);
        $this->get('/eventos/create')->assertOk()->assertSee('Costo por categoría')
            ->assertSeeInOrder(['Categoría', 'Costo $us', 'Costo Bs.-', 'Kyu', 'Dan', 'Jefes de Dojo'])
            ->assertDontSee('Costo en dólares</label>', false)
            ->assertSee('name="costos_usd['.$kyu->id.']" value="30.00"', false)
            ->assertSee('name="costos_usd['.$dan->id.']" value="50.00"', false)
            ->assertSee('name="costos_usd['.$adicional->id.']" value="30.00"', false);
        $data = $this->data();
        $data['tipo_cambio'] = '12.00';
        $data['costos_usd'] = [$kyu->id => '30.00', $dan->id => '50.00', $adicional->id => '30.00'];
        $this->post('/eventos', $data)->assertSessionHasNoErrors()->assertRedirect('/eventos');
        $evento = Evento::firstOrFail();
        foreach ([[$kyu, '30.00', '360.00'], [$dan, '50.00', '600.00'], [$adicional, '30.00', '360.00']] as [$grado, $usd, $bs]) {
            $this->assertDatabaseHas('evento_grado', ['evento_id' => $evento->id, 'grado_id' => $grado->id, 'costo_usd' => $usd, 'costo' => $bs]);
        }
    }

    public function test_event_deletion_removes_image_and_missing_file_has_fallback(): void
    {
        Storage::fake('local');
        $this->post('/eventos', $this->data() + ['imagen' => UploadedFile::fake()->image('afiche.png')])->assertSessionHasNoErrors();
        $evento = Evento::firstOrFail(); $path = $evento->imagen;
        Storage::disk('local')->delete($path);
        $this->get(route('eventos.imagen', $evento))->assertOk();
        Storage::disk('local')->put($path, 'imagen de prueba');
        $this->delete("/eventos/{$evento->id}")->assertRedirect('/eventos');
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('eventos', ['id' => $evento->id]);
    }

    public function test_status_filter_pagination_and_dashboard_use_real_records(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3));
        $data = $this->data();
        $this->post('/eventos', $data)->assertSessionHasNoErrors();
        $data['nombre'] = 'Evento finalizado'; $data['fecha_inicio'] = '2026-01-01'; $data['fecha_final'] = '2026-01-02';
        $this->post('/eventos', $data)->assertSessionHasNoErrors();
        $this->get('/eventos?estado=proximo&per_page=25')->assertOk()->assertSee('Seminario Internacional')->assertDontSee('Evento finalizado');
        $this->get('/eventos?estado=finalizado')->assertOk()->assertSee('Evento finalizado')->assertDontSee('Seminario Internacional');
        $this->get('/dashboard')->assertOk()->assertSee('Seminario Internacional')->assertDontSee('Evento finalizado')->assertSee('Limpiar caché');
        $this->travelBack();
    }
}
