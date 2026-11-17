<?php

namespace Tests\Feature;

use App\Models\{Evento, Grado, Inscripcion, Persona};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\AuthenticatedTestCase as TestCase;

class InscripcionIntegradaTest extends TestCase
{
    use RefreshDatabase;

    private function datos(): array
    {
        Storage::fake('local');
        $grado = Grado::create(['nombre' => 'Dan']);
        $evento = Evento::create(['nombre' => 'Seminario', 'fecha_inicio' => '2026-12-01', 'fecha_final' => '2026-12-02', 'lugar' => 'Dojo', 'ciudad' => 'La Paz', 'tipo_cambio' => '6.96']);
        $evento->grados()->attach($grado, ['costo' => '200.00']);
        return ['registro_integrado' => '1', 'primer_nombre' => 'Ana', 'segundo_nombre' => 'María', 'primer_apellido' => 'Pérez', 'segundo_apellido' => 'López', 'documento_identidad' => 'CI-123', 'sexo' => 'Femenino', 'fecha_nacimiento' => '1995-05-20', 'telefono' => '70012345', 'grado_id' => $grado->id, 'evento_id' => $evento->id, 'cargo' => 'Participante', 'jefe_dojo' => '0', 'invitado_sin_costo' => '0'];
    }

    public function test_one_form_creates_person_photo_and_registration(): void
    {
        $data = $this->datos();
        $this->get('/inscripciones/create')->assertOk()->assertSee('multipart/form-data')
            ->assertSee('name="primer_nombre"', false)->assertSee('name="foto"', false)
            ->assertSee('name="grado_id"', false)->assertSee('name="cargo"', false)
            ->assertDontSee('> Persona <', false);
        $this->post('/inscripciones', $data + ['foto' => UploadedFile::fake()->image('ana.jpg')])->assertRedirect('/inscripciones')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('personas', 1);
        $this->assertDatabaseCount('inscripciones', 1);
        $persona = Persona::firstOrFail();
        Storage::disk('local')->assertExists($persona->foto);
        $this->assertSame($persona->id, Inscripcion::firstOrFail()->persona_id);
        $this->assertSame('200.00', Inscripcion::firstOrFail()->tarifa());
        $this->get('/inscripciones?evento_id='.$data['evento_id'])->assertOk()->assertSee($persona->foto_url, false)->assertSee('70012345')->assertSee('CI-123')->assertSee('Ana María Pérez López')
            ->assertSee('data-bs-target="#ver-participante-'.Inscripcion::firstOrFail()->id.'"', false)
            ->assertSee('Datos del participante')->assertSee('20/05/1995')->assertSee('Segundo nombre')
            ->assertSee('Datos de la inscripción')->assertSee('Descuento');
    }

    public function test_failed_registration_rolls_back_new_person_and_photo(): void
    {
        $data = $this->datos();
        Evento::firstOrFail()->grados()->detach();
        $this->post('/inscripciones', $data + ['foto' => UploadedFile::fake()->image('ana.jpg')])->assertSessionHasErrors('persona_id');
        $this->assertDatabaseCount('personas', 0);
        $this->assertDatabaseCount('inscripciones', 0);
        $this->assertEmpty(Storage::disk('local')->allFiles('personas'));
        $data['invitado_sin_costo'] = '1';
        $this->post('/inscripciones', $data + ['foto' => UploadedFile::fake()->image('ana.jpg')])->assertSessionHasNoErrors();
        $this->assertSame('0.00', Inscripcion::firstOrFail()->tarifa());
        $this->assertSame('invitado sin costo', Inscripcion::firstOrFail()->observaciones);
    }

    public function test_existing_participant_can_join_another_event_without_losing_photo_or_payments(): void
    {
        $data = $this->datos();
        $this->post('/inscripciones', $data + ['foto' => UploadedFile::fake()->image('ana.jpg')])->assertSessionHasNoErrors();
        $primera = Inscripcion::firstOrFail();
        $foto = $primera->persona->foto;
        $this->post(route('inscripciones.pagos.store', $primera), ['monto' => '50', 'fecha' => '2026-01-01'])->assertSessionHasNoErrors();
        $otro = Evento::create(['nombre' => 'Otro evento', 'fecha_inicio' => '2026-12-05', 'fecha_final' => '2026-12-06', 'lugar' => 'Dojo', 'ciudad' => 'La Paz']);
        $otro->grados()->attach($data['grado_id'], ['costo' => '300']);
        $data['evento_id'] = $otro->id;
        $data['persona_id'] = $primera->persona_id;
        $this->post('/inscripciones', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('personas', 1);
        $this->assertDatabaseCount('inscripciones', 2);
        $this->assertDatabaseHas('pagos', ['inscripcion_id' => $primera->id, 'monto' => 50]);
        $this->assertSame($foto, $primera->persona->fresh()->foto);
        Storage::disk('local')->assertExists($foto);
        $this->post('/inscripciones', $data)->assertSessionHasErrors('persona_id');
        $this->assertDatabaseCount('inscripciones', 2);
        $this->get('/inscripciones?evento_id='.$otro->id)->assertOk()->assertSee($primera->persona->foto_url, false)
            ->assertViewHas('inscripciones', fn ($rows) => $rows->count() === 1 && $rows->first()->evento_id === $otro->id);
        $this->get('/inscripciones/imprimir?evento_id='.$otro->id)->assertOk()
            ->assertViewHas('inscripciones', fn ($rows) => $rows->count() === 1 && $rows->first()->evento_id === $otro->id)
            ->assertViewHas('totalPagadoBs', '0.00');
    }

    public function test_integrated_edit_preserves_or_replaces_photo_and_rolls_back_invalid_changes(): void
    {
        $data = $this->datos();
        $this->post('/inscripciones', $data + ['foto' => UploadedFile::fake()->image('ana.jpg')])->assertSessionHasNoErrors();
        $inscripcion = Inscripcion::firstOrFail();
        $data['persona_id'] = $inscripcion->persona_id;
        $data['telefono'] = '71111111';
        $foto = $inscripcion->persona->foto;
        $this->put(route('inscripciones.update', $inscripcion), $data)->assertSessionHasNoErrors();
        $this->assertSame($foto, $inscripcion->persona->fresh()->foto);
        $this->get(route('inscripciones.edit', $inscripcion))->assertOk()->assertSee('71111111')->assertSee($inscripcion->persona->foto_url, false);
        $data['cargo'] = '';
        $data['primer_nombre'] = 'Cambio inválido';
        $this->put(route('inscripciones.update', $inscripcion), $data + ['foto' => UploadedFile::fake()->image('rechazada.jpg')])->assertSessionHasErrors('cargo');
        $this->assertSame('Ana', $inscripcion->persona->fresh()->primer_nombre);
        $this->assertSame([$foto], Storage::disk('local')->allFiles('personas'));
        $data['cargo'] = 'Organizadora';
        $data['primer_nombre'] = 'Andrea';
        $this->put(route('inscripciones.update', $inscripcion), $data + ['foto' => UploadedFile::fake()->image('nueva.jpg')])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($foto);
        Storage::disk('local')->assertExists($inscripcion->persona->fresh()->foto);
        $this->assertSame('Andrea', $inscripcion->persona->fresh()->primer_nombre);
    }

    public function test_invalid_person_data_and_duplicate_document_do_not_create_registrations(): void
    {
        $data = $this->datos();
        $this->post('/inscripciones', $data)->assertSessionHasErrors('foto');
        $this->post('/inscripciones', $data + ['foto' => UploadedFile::fake()->create('foto.txt')])->assertSessionHasErrors('foto');
        $this->post('/inscripciones', $data + ['foto' => UploadedFile::fake()->image('foto.jpg')])->assertSessionHasNoErrors();
        $this->post('/inscripciones', $data + ['foto' => UploadedFile::fake()->image('duplicada.jpg')])->assertSessionHasErrors('documento_identidad');
        $this->assertDatabaseCount('personas', 1);
        $this->assertDatabaseCount('inscripciones', 1);
        $data['persona_id'] = Persona::firstOrFail()->id;
        $data['grado_id'] = Grado::create(['nombre' => 'Kyu'])->id;
        $this->put(route('inscripciones.update', Inscripcion::firstOrFail()), $data)->assertSessionHasErrors('grado_id');
    }

    public function test_participant_country_is_saved_loaded_and_validated(): void
    {
        $data = $this->datos();
        $bolivia = \App\Models\Pais::where('nombre', 'Bolivia')->firstOrFail();
        $data['pais_id'] = $bolivia->id;
        $this->post('/inscripciones', $data + ['foto' => UploadedFile::fake()->image('foto.jpg')])->assertSessionHasNoErrors();
        $inscripcion = Inscripcion::firstOrFail();
        $this->assertSame($bolivia->id, $inscripcion->persona->pais_id);
        $this->get('/inscripciones?evento_id='.$data['evento_id'])->assertOk()->assertSeeInOrder(['>Participante</small>', '>Bolivia</small>'], false);
        $this->get(route('inscripciones.edit', $inscripcion))->assertOk()->assertSee('País del participante')
            ->assertSee('value="'.$bolivia->id.'" selected', false);
        $data['persona_id'] = $inscripcion->persona_id;
        $data['pais_id'] = 99999;
        $this->put(route('inscripciones.update', $inscripcion), $data)->assertSessionHasErrors('pais_id');
        $this->assertSame($bolivia->id, $inscripcion->persona->fresh()->pais_id);
        $this->delete(route('paises.destroy', $bolivia))->assertSessionHas('error');
        $data['pais_id'] = \App\Models\Pais::where('nombre', 'Chile')->value('id');
        $this->put(route('inscripciones.update', $inscripcion), $data)->assertSessionHasNoErrors();
        $this->assertSame($data['pais_id'], $inscripcion->persona->fresh()->pais_id);
    }

    public function test_country_migration_assigns_bolivia_without_changing_existing_data(): void
    {
        $data = $this->datos();
        $this->post('/inscripciones', $data + ['foto' => UploadedFile::fake()->image('foto.jpg')])->assertSessionHasNoErrors();
        $inscripcion = Inscripcion::firstOrFail();
        $original = $inscripcion->persona->getAttributes();
        unset($original['pais_id']);
        $migration = require database_path('migrations/2026_11_17_000002_add_pais_to_personas.php');
        $migration->asignarBolivia();
        $persona = $inscripcion->persona->fresh();
        $this->assertSame('Bolivia', $persona->pais->nombre);
        $actual = $persona->getAttributes();
        unset($actual['pais_id']);
        $this->assertEquals($original, $actual);
        $this->assertDatabaseCount('inscripciones', 1);
        Storage::disk('local')->assertExists($persona->foto);
    }
}
