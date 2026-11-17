<?php
namespace Tests\Feature;

use App\Models\{Evento, Grado, Inscripcion, Persona};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\AuthenticatedTestCase as TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    private function personaData(int $grado): array
    {
        return ['primer_nombre' => 'Ana', 'segundo_nombre' => 'María', 'primer_apellido' => 'Pérez', 'segundo_apellido' => 'López', 'documento_identidad' => '1234567', 'sexo' => 'Femenino', 'fecha_nacimiento' => '1995-05-20', 'telefono' => '70012345', 'grado_id' => $grado];
    }
    private function eventoData(int $grado): array
    {
        return ['nombre' => 'Encuentro anual', 'fecha_inicio' => '2026-12-10', 'fecha_final' => '2026-12-12', 'lugar' => 'Centro de convenciones', 'ciudad' => 'La Paz', 'tipo_cambio' => '1', 'costos_usd' => [$grado => '120.50']];
    }
    private function fixtures(): array
    {
        $grado = Grado::create(['nombre' => 'Grado de prueba']);
        $persona = Persona::create($this->personaData($grado->id));
        $data = $this->eventoData($grado->id); unset($data['costos_usd']);
        $evento = Evento::create($data);
        $evento->grados()->attach($grado, ['costo' => '120.50']);
        return [$grado, $persona, $evento];
    }
    public function test_all_empty_screens_render(): void
    {
        foreach (['/personas', '/personas/create', '/eventos', '/eventos/create', '/inscripciones', '/inscripciones/create', '/grados'] as $url) $this->get($url)->assertOk();
    }
    public function test_complete_registration_flow_and_edit_screens(): void
    {
        Storage::fake('local');
        $this->post('/grados', ['nombre' => 'Inicial'])->assertSessionHasNoErrors();
        $grado = Grado::firstOrFail();
        $this->post('/personas', $this->personaData($grado->id) + ['foto' => UploadedFile::fake()->image('persona.jpg')])->assertRedirect('/personas')->assertSessionHasNoErrors();
        $this->post('/eventos', $this->eventoData($grado->id))->assertRedirect('/eventos')->assertSessionHasNoErrors();
        $persona = Persona::firstOrFail(); $evento = Evento::firstOrFail();
        $this->post('/inscripciones', ['evento_id' => $evento->id, 'persona_id' => $persona->id, 'cargo' => 'Expositora'])->assertRedirect('/inscripciones')->assertSessionHasNoErrors();
        $inscripcion = Inscripcion::firstOrFail();
        foreach (["/personas/{$persona->id}/edit", "/eventos/{$evento->id}/edit", "/inscripciones/{$inscripcion->id}/edit", '/inscripciones/create'] as $url) $this->get($url)->assertOk();
        $this->get('/personas')->assertSee('Ana María Pérez López');
        $this->get('/eventos')->assertSee('Encuentro anual')->assertSee('120,50');
        $this->get('/inscripciones')->assertSee('Expositora')->assertSee('120,50');
    }
    public function test_duplicates_are_rejected_and_existing_person_can_be_edited(): void
    {
        [$grado, $persona, $evento] = $this->fixtures();
        $this->post('/personas', $this->personaData($grado->id))->assertSessionHasErrors('documento_identidad');
        $data = $this->personaData($grado->id); $data['primer_nombre'] = 'Andrea';
        $this->put("/personas/{$persona->id}", $data)->assertSessionHasNoErrors();
        $registration = ['evento_id' => $evento->id, 'persona_id' => $persona->id, 'cargo' => 'Participante'];
        $this->post('/inscripciones', $registration)->assertSessionHasNoErrors();
        $this->post('/inscripciones', $registration)->assertSessionHasErrors('persona_id');
        $inscripcion = Inscripcion::firstOrFail(); $registration['cargo'] = 'Coordinadora';
        $this->put("/inscripciones/{$inscripcion->id}", $registration)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('inscripciones', ['id' => $inscripcion->id, 'cargo' => 'Coordinadora']);
        $this->assertDatabaseCount('inscripciones', 1);
    }
    public function test_invalid_dates_and_negative_costs_are_rejected(): void
    {
        $grado = Grado::create(['nombre' => 'Inicial']);
        $data = $this->eventoData($grado->id); $data['fecha_final'] = '2026-01-01';
        $this->post('/eventos', $data)->assertSessionHasErrors('fecha_final');
        $data = $this->eventoData($grado->id); $data['costos_usd'][$grado->id] = '-1';
        $this->post('/eventos', $data)->assertSessionHasErrors('costos_usd.'.$grado->id);
        $data = $this->personaData($grado->id); $data['fecha_nacimiento'] = now()->addDay()->toDateString();
        $this->post('/personas', $data)->assertSessionHasErrors('fecha_nacimiento');
        $this->assertDatabaseCount('eventos', 0);
    }
    public function test_free_tariff_is_valid_and_missing_tariff_is_rejected(): void
    {
        [$grado, $persona, $evento] = $this->fixtures();
        $other = Grado::create(['nombre' => 'Otro grado']);
        $persona->update(['grado_id' => $other->id]);
        $data = ['evento_id' => $evento->id, 'persona_id' => $persona->id, 'cargo' => 'Participante'];
        $this->post('/inscripciones', $data)->assertSessionHasErrors('persona_id');
        $eventData = $this->eventoData($grado->id); $eventData['costos_usd'][$other->id] = '0';
        $this->put("/eventos/{$evento->id}", $eventData)->assertSessionHasNoErrors();
        $this->post('/inscripciones', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('inscripciones', 1);
    }
    public function test_related_records_are_protected_and_can_be_deleted_in_order(): void
    {
        [$grado, $persona, $evento] = $this->fixtures();
        $inscripcion = Inscripcion::create(['evento_id' => $evento->id, 'persona_id' => $persona->id, 'cargo' => 'Participante']);
        foreach (["/personas/{$persona->id}", "/eventos/{$evento->id}", "/grados/{$grado->id}"] as $url) $this->delete($url)->assertSessionHas('error');
        $other = Grado::create(['nombre' => 'Nuevo grado']);
        $data = $this->personaData($other->id);
        $this->put("/personas/{$persona->id}", $data)->assertSessionHasErrors('grado_id');
        $data = $this->eventoData($other->id);
        $this->put("/eventos/{$evento->id}", $data)->assertSessionHasErrors('costos_usd');
        $this->delete("/inscripciones/{$inscripcion->id}")->assertSessionHas('success');
        $this->delete("/personas/{$persona->id}")->assertSessionHas('success');
        $this->delete("/eventos/{$evento->id}")->assertSessionHas('success');
        $this->delete("/grados/{$grado->id}")->assertSessionHas('success');
        $this->assertDatabaseCount('inscripciones', 0); $this->assertDatabaseCount('evento_grado', 0);
    }
    public function test_new_registration_excludes_people_already_registered_in_selected_event(): void
    {
        [$grado, $persona, $evento] = $this->fixtures();
        Inscripcion::create(['evento_id' => $evento->id, 'persona_id' => $persona->id, 'cargo' => 'Participante']);
        $data = $this->personaData($grado->id); $data['documento_identidad'] = '7654321';
        $disponible = Persona::create($data);
        $this->get('/inscripciones/create?evento_id='.$evento->id)->assertOk()
            ->assertViewHas('personas', fn ($items) => !$items->contains('id', $persona->id) && $items->contains('id', $disponible->id));
        $data = $this->eventoData($grado->id); unset($data['costos_usd']);
        $otroEvento = Evento::create($data);
        $this->get('/inscripciones/create?evento_id='.$otroEvento->id)->assertOk()
            ->assertViewHas('personas', fn ($items) => $items->contains('id', $persona->id));
    }

    public function test_edit_keeps_current_person_and_excludes_other_existing_registrations(): void
    {
        [$grado, $persona, $evento] = $this->fixtures();
        $actual = Inscripcion::create(['evento_id' => $evento->id, 'persona_id' => $persona->id, 'cargo' => 'Participante']);
        $data = $this->personaData($grado->id); $data['documento_identidad'] = '7654321';
        $otraPersona = Persona::create($data);
        Inscripcion::create(['evento_id' => $evento->id, 'persona_id' => $otraPersona->id, 'cargo' => 'Organizador']);
        $this->get('/inscripciones/'.$actual->id.'/edit')->assertOk()
            ->assertViewHas('personas', fn ($items) => $items->contains('id', $persona->id) && !$items->contains('id', $otraPersona->id));
    }

    public function test_search_filters_records(): void
    {
        [$grado, $persona, $evento] = $this->fixtures();
        Inscripcion::create(['evento_id' => $evento->id, 'persona_id' => $persona->id, 'cargo' => 'Expositora']);
        $this->get('/personas?q=1234567')->assertSee('Ana María Pérez López');
        $this->get('/personas?q=inexistente')->assertDontSee('Ana María Pérez López');
        $this->get('/eventos?q=La%20Paz')->assertSee('Encuentro anual');
        $this->get('/eventos?q=inexistente')->assertDontSee('Encuentro anual');
        $this->get('/inscripciones?evento_id='.$evento->id)->assertSee('Expositora');
        $this->get('/inscripciones?evento_id=999')->assertDontSee('Expositora');
    }

    public function test_registrations_are_alphabetical_before_pagination_and_with_filters(): void
    {
        [$grado, $persona, $evento] = $this->fixtures();
        for ($i = 14; $i >= 1; $i--) {
            $data = $this->personaData($grado->id);
            $data['primer_nombre'] = 'Participante '.str_pad($i, 2, '0', STR_PAD_LEFT);
            $data['documento_identidad'] = 'orden-'.$i;
            $participante = Persona::create($data);
            Inscripcion::create(['evento_id' => $evento->id, 'persona_id' => $participante->id, 'cargo' => 'Participante']);
        }
        foreach (['/inscripciones', '/inscripciones?evento_id='.$evento->id.'&q=Participante'] as $url) {
            $this->get($url)->assertOk()->assertViewHas('inscripciones', fn ($rows) =>
                $rows->count() === 12 && $rows->first()->persona->primer_nombre === 'Participante 01'
                && $rows->last()->persona->primer_nombre === 'Participante 12');
        }
        $this->get('/inscripciones?page=2')->assertOk()->assertViewHas('inscripciones', fn ($rows) =>
            $rows->count() === 2 && $rows->first()->persona->primer_nombre === 'Participante 13'
            && $rows->last()->persona->primer_nombre === 'Participante 14');
    }

    public function test_dojo_leader_uses_100_dollars_in_registration_report_payments_and_discount(): void
    {
        [$grado, $persona, $evento] = $this->fixtures();
        $evento->update(['tipo_cambio' => '11.81']);
        $data = ['evento_id' => $evento->id, 'persona_id' => $persona->id, 'cargo' => 'Organizador', 'jefe_dojo' => '1'];
        $this->get('/inscripciones/create')->assertOk()->assertSee('Jefe de Dojo');
        $this->post('/inscripciones', $data)->assertSessionHasNoErrors();
        $inscripcion = Inscripcion::firstOrFail();
        $this->assertTrue($inscripcion->jefe_dojo);
        $this->assertSame('1181.00', $inscripcion->tarifa());
        foreach (['/inscripciones', '/inscripciones/imprimir', route('inscripciones.pagos.create', $inscripcion)] as $url) {
            $this->get($url)->assertOk()->assertSee('Bs 1.181,00');
        }
        $this->get(route('inscripciones.edit', $inscripcion))->assertOk()->assertSee('value="1" selected', false);
        $data['jefe_dojo'] = '0';
        $this->put(route('inscripciones.update', $inscripcion), $data)->assertSessionHasNoErrors();
        $this->assertSame('120.50', $inscripcion->fresh()->tarifa());
        $data['jefe_dojo'] = '1';
        $this->put(route('inscripciones.update', $inscripcion), $data)->assertSessionHasNoErrors();
        $this->post(route('inscripciones.pagos.store', $inscripcion), [
            'monto' => '1000', 'fecha' => '2026-01-01', 'pago_total' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pagos', ['inscripcion_id' => $inscripcion->id, 'descuento' => '181.00']);
        $this->get(route('inscripciones.pagos.create', $inscripcion))->assertViewHas('saldoPendiente', '0.00');
    }

    public function test_guest_is_free_with_observations_and_cannot_be_charged(): void
    {
        [$grado, $persona, $evento] = $this->fixtures();
        $data = ['evento_id' => $evento->id, 'persona_id' => $persona->id, 'cargo' => 'Participante', 'jefe_dojo' => '1', 'invitado_sin_costo' => '1'];
        $this->get('/inscripciones/create')->assertOk()->assertSee('id="invitado_sin_costo"', false)->assertSee('Observaciones');
        $this->post('/inscripciones', $data)->assertRedirect('/inscripciones')->assertSessionHasNoErrors();
        $inscripcion = Inscripcion::firstOrFail();
        $this->assertTrue($inscripcion->invitado_sin_costo);
        $this->assertSame('0.00', $inscripcion->tarifa());
        $this->assertSame('invitado sin costo', $inscripcion->observaciones);
        foreach (['/inscripciones', '/inscripciones/imprimir'] as $url) {
            $this->get($url)->assertOk()->assertSee('invitado sin costo')->assertSee('Bs 0,00');
        }
        $this->get(route('inscripciones.edit', $inscripcion))->assertOk()->assertSee('invitado sin costo');
        $this->get(route('inscripciones.pagos.create', $inscripcion))->assertOk()
            ->assertViewHas('tarifa', '0.00')->assertViewHas('saldoPendiente', '0.00')
            ->assertSee('invitado sin costo')->assertDontSee('Guardar pago');
        foreach (['BOB', 'USD'] as $moneda) {
            $this->post(route('inscripciones.pagos.store', $inscripcion), [
                'monto' => '10', 'fecha' => '2026-01-01', 'moneda' => $moneda, 'pago_total' => '1',
            ])->assertSessionHasErrors('monto');
        }
        $this->assertDatabaseCount('pagos', 0);
        $this->post('/inscripciones', $data)->assertSessionHasErrors('persona_id');
        $data['invitado_sin_costo'] = '0';
        $this->put(route('inscripciones.update', $inscripcion), $data)->assertSessionHasNoErrors();
        $this->assertSame('100.00', $inscripcion->fresh()->tarifa());
        $this->assertNull($inscripcion->fresh()->observaciones);
        $data['jefe_dojo'] = '0';
        $this->put(route('inscripciones.update', $inscripcion), $data)->assertSessionHasNoErrors();
        $this->assertSame('120.50', $inscripcion->fresh()->tarifa());
    }

    public function test_guest_needs_no_exchange_rate_or_grade_tariff_and_can_be_enabled_on_edit(): void
    {
        [$grado, $persona, $evento] = $this->fixtures();
        $data = ['evento_id' => $evento->id, 'persona_id' => $persona->id, 'cargo' => 'Participante'];
        $this->post('/inscripciones', $data)->assertSessionHasNoErrors();
        $inscripcion = Inscripcion::firstOrFail();
        $evento->update(['tipo_cambio' => null]);
        $evento->grados()->detach();
        $data += ['jefe_dojo' => '1', 'invitado_sin_costo' => '1'];
        $this->put(route('inscripciones.update', $inscripcion), $data)->assertSessionHasNoErrors();
        $this->assertSame('0.00', $inscripcion->fresh()->tarifa());
        $this->assertSame('invitado sin costo', $inscripcion->fresh()->observaciones);
        $data['cargo'] = 'Invitado';
        unset($data['invitado_sin_costo']);
        $this->put(route('inscripciones.update', $inscripcion), $data)->assertSessionHasNoErrors();
        $this->assertTrue($inscripcion->fresh()->invitado_sin_costo);
        $data['invitado_sin_costo'] = 'incorrecto';
        $this->put(route('inscripciones.update', $inscripcion), $data)->assertSessionHasErrors('invitado_sin_costo');
        $this->delete(route('inscripciones.destroy', $inscripcion))->assertSessionHas('success');
        $data['invitado_sin_costo'] = '1';
        $this->post('/inscripciones', $data)->assertSessionHasNoErrors();
        $this->assertSame('0.00', Inscripcion::firstOrFail()->tarifa());
    }

    public function test_dojo_leader_requires_exchange_rate_and_works_without_a_grade_tariff(): void
    {
        [$grado, $persona, $evento] = $this->fixtures();
        $evento->update(['tipo_cambio' => null]);
        $data = ['evento_id' => $evento->id, 'persona_id' => $persona->id, 'cargo' => 'Organizador', 'jefe_dojo' => '1'];
        $this->post('/inscripciones', $data)->assertSessionHasErrors('jefe_dojo');
        $this->assertDatabaseCount('inscripciones', 0);
        $evento->update(['tipo_cambio' => '11.81']);
        $evento->grados()->detach();
        $this->post('/inscripciones', $data)->assertSessionHasNoErrors();
        $inscripcion = Inscripcion::firstOrFail();
        $this->post(route('inscripciones.pagos.store', $inscripcion), [
            'monto' => '100', 'moneda' => 'USD', 'fecha' => '2026-01-01',
        ])->assertSessionHasNoErrors();
        $this->get(route('inscripciones.pagos.create', $inscripcion))->assertViewHas('totalPagado', '1181.00')->assertViewHas('saldoPendiente', '0.00');
        $data['jefe_dojo'] = '0';
        $this->put(route('inscripciones.update', $inscripcion), $data)->assertSessionHasErrors('persona_id');
    }
}
