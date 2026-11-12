<?php

namespace Tests\Feature;

use App\Models\{Evento, Grado, Inscripcion, Persona};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\AuthenticatedTestCase as TestCase;

class CredencialTest extends TestCase
{
    use RefreshDatabase;

    private function persona(string $documento = '1234567'): Persona
    {
        $grado = Grado::firstOrCreate(['nombre' => 'Dan']);
        return Persona::create(['primer_nombre' => 'Ana', 'primer_apellido' => 'Pérez', 'documento_identidad' => $documento, 'sexo' => 'Femenino', 'fecha_nacimiento' => '1995-01-01', 'telefono' => '70012345', 'grado_id' => $grado->id]);
    }

    private function inscripcion(Persona $persona, string $nombre = 'Seminario Internacional'): Inscripcion
    {
        $evento = Evento::create(['nombre' => $nombre, 'fecha_inicio' => '2026-12-02', 'fecha_final' => '2026-12-05', 'lugar' => 'Coliseo La Pampita', 'ciudad' => 'Trinidad, Beni']);
        return Inscripcion::create(['persona_id' => $persona->id, 'evento_id' => $evento->id, 'cargo' => 'Participante']);
    }

    public function test_credential_button_is_only_in_registration_actions(): void
    {
        $persona = $this->persona();
        $this->withSession(['success' => 'Persona actualizada.'])->get('/personas')->assertOk()
            ->assertDontSee('fa-regular fa-id-badge')
            ->assertDontSee('overview-banner')->assertSee('Persona actualizada.')->assertSee('id="app-notifications"', false);
        $inscripcion = $this->inscripcion($persona);
        $this->get('/inscripciones')->assertOk()->assertSee('fa-regular fa-id-badge')
            ->assertSee(route('inscripciones.credencial', $inscripcion), false);
    }

    public function test_credential_uses_the_exact_registration_selected_in_the_list(): void
    {
        $persona = $this->persona();
        $primera = $this->inscripcion($persona, 'Evento seleccionado');
        $primera->update(['cargo' => 'Organizador']);
        $this->inscripcion($persona, 'Otro evento más reciente');
        $response = $this->get(route('inscripciones.credencial', $primera));
        $response->assertOk()->assertViewIs('personas.vercred')
            ->assertViewHas('inscripcion', fn ($item) => $item->id === $primera->id)
            ->assertSee('Evento seleccionado')->assertSee('Organizador')->assertSee('Ana Pérez')
            ->assertDontSee('Otro evento más reciente')->assertSee(route('inscripciones.index'), false);
        $this->get($response->viewData('verificacionUrl'))->assertOk()->assertSee('Evento seleccionado')->assertSee('Organizador');
        $this->get('/inscripciones/999999/credencial')->assertNotFound();
    }

    public function test_credential_requires_a_real_registration(): void
    {
        $persona = $this->persona();
        $this->get(route('personas.credencial', $persona))->assertOk()->assertViewIs('personas.vercred')
            ->assertSee('La persona no tiene inscripciones')->assertDontSee('id="print-credential"', false)->assertDontSee('VERIFICACIÓN DIGITAL');
    }

    public function test_three_sides_contain_real_person_and_event_data_and_qr(): void
    {
        $persona = $this->persona(); $inscripcion = $this->inscripcion($persona);
        $response = $this->get(route('personas.credencial', $persona));
        $response->assertOk()->assertViewIs('personas.vercred')->assertSee('Anverso')->assertSee('Reverso')->assertSee('Ficha')
            ->assertSee('Ana Pérez')->assertSee('1234567')->assertSee('Dan')->assertSee('Participante')
            ->assertSee('Seminario Internacional')->assertSee('02/12/2026')->assertSee('05/12/2026')
            ->assertSee('Coliseo La Pampita')->assertSee('Trinidad, Beni')->assertSee('data:image/svg+xml;base64,', false)->assertSee('Imprimir')
            ->assertSee('images/anverso.png', false)->assertSee('images/reverso.png', false)->assertSee('images/Ficha1.png', false);
        $qr = $response->viewData('qr');
        $this->assertStringContainsString('<svg', base64_decode(explode(',', $qr, 2)[1]));
        $this->get($response->viewData('verificacionUrl'))->assertOk()->assertSee('mobile-ficha')->assertSee('Ana Pérez');
        $this->get(route('credenciales.verificar', $inscripcion))->assertForbidden();
    }

    public function test_event_can_be_selected_but_other_person_registration_is_rejected(): void
    {
        $persona = $this->persona();
        $first = $this->inscripcion($persona, 'Primer evento');
        $this->inscripcion($persona, 'Segundo evento');
        $this->get(route('personas.credencial', $persona))->assertOk()->assertViewHas('inscripcion', fn ($item) => $item->evento->nombre === 'Segundo evento');
        $this->get(route('personas.credencial', [$persona, 'inscripcion' => $first->id]))->assertOk()->assertViewHas('inscripcion', fn ($item) => $item->id === $first->id);
        $other = $this->inscripcion($this->persona('7654321'));
        $this->get(route('personas.credencial', [$persona, 'inscripcion' => $other->id]))->assertNotFound();
    }

    public function test_qr_is_different_for_each_person_and_stable_when_reopened(): void
    {
        $primera = $this->inscripcion($this->persona());
        $segunda = Inscripcion::create(['persona_id' => $this->persona('7654321')->id, 'evento_id' => $primera->evento_id, 'cargo' => 'Organizador']);
        $primeraVista = $this->get(route('inscripciones.credencial', $primera))->assertOk();
        $segundaVista = $this->get(route('inscripciones.credencial', $segunda))->assertOk();
        $this->assertNotSame($primeraVista->viewData('qr'), $segundaVista->viewData('qr'));
        $this->assertNotSame($primeraVista->viewData('verificacionUrl'), $segundaVista->viewData('verificacionUrl'));
        $reabierta = $this->get(route('inscripciones.credencial', $primera))->assertOk();
        $this->assertSame($primeraVista->viewData('qr'), $reabierta->viewData('qr'));
        $this->get($primeraVista->viewData('verificacionUrl'))->assertOk()->assertSee('Participante');
        $this->get($segundaVista->viewData('verificacionUrl'))->assertOk()->assertSee('Organizador');
        $this->assertSame(3, substr_count($primeraVista->getContent(), 'data-credential-zoom role="button"'));
    }

    public function test_deleted_registration_cannot_be_verified_with_an_old_qr(): void
    {
        $inscripcion = $this->inscripcion($this->persona());
        $url = URL::signedRoute('credenciales.verificar', ['inscripcion' => $inscripcion->id]);
        $inscripcion->delete();
        $this->get($url)->assertNotFound();
    }
}
