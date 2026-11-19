<?php

namespace Tests\Feature;

use App\Models\{Evento, Grado, Inscripcion, Persona};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\AuthenticatedTestCase as TestCase;

class CredencialTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_groups_two_complete_credentials_per_page_with_an_odd_remainder(): void
    {
        $first = $this->inscripcion($this->persona());
        $second = Inscripcion::create(['persona_id' => $this->persona('7654321')->id, 'evento_id' => $first->evento_id, 'cargo' => 'Participante']);
        $third = Inscripcion::create(['persona_id' => $this->persona('5555555')->id, 'evento_id' => $first->evento_id, 'cargo' => 'Participante']);
        $response = $this->post(route('inscripciones.credenciales-lote'), [
            'evento_id' => $first->evento_id, 'inscripciones' => [$first->id, $second->id, $third->id],
        ])->assertOk();

        $html = new \DOMDocument;
        @$html->loadHTML($response->getContent());
        $xpath = new \DOMXPath($html);
        $pages = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " batch-page ")]');
        $this->assertCount(2, $pages);
        $this->assertSame(2, $xpath->query('./div[contains(@class, "credential-sheet")]', $pages->item(0))->length);
        $this->assertSame(1, $xpath->query('./div[contains(@class, "credential-sheet")]', $pages->item(1))->length);
        $this->assertSame(6, $xpath->query('//div[contains(@class, "batch-page")]//article[contains(@class, "credential-card")]')->length);
        $pdf = $this->post(route('inscripciones.credenciales-lote'), [
            'evento_id' => $first->evento_id, 'inscripciones' => [$first->id, $second->id, $third->id], 'pdf' => 1,
        ])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertSame(2, preg_match_all('/\/Type\s*\/Page\b/', $pdf->getContent()));
        $this->assertStringContainsString('/BM /Multiply', $pdf->getContent());
    }

    public function test_batch_requires_distinct_registrations_from_the_selected_event_and_preserves_individual_printing(): void
    {
        $first = $this->inscripcion($this->persona());
        $second = Inscripcion::create(['persona_id' => $this->persona('7654321')->id, 'evento_id' => $first->evento_id, 'cargo' => 'Organizador']);
        $other = $this->inscripcion($this->persona('5555555'), 'Otro evento');
        $url = route('inscripciones.credenciales-lote');
        $this->get(route('inscripciones.index', ['evento_id' => $first->evento_id]))->assertOk()
            ->assertSeeInOrder(['id="imprimir-lote"', route('inscripciones.imprimir')], false)
            ->assertSee('seleccionar-credencial', false)->assertDontSee('target="_blank"', false);
        $this->post($url, ['evento_id' => $first->evento_id, 'inscripciones' => [$first->id]])->assertSessionHasErrors('inscripciones');
        $this->post($url, ['evento_id' => $first->evento_id, 'inscripciones' => [$first->id, $first->id]])->assertSessionHasErrors('inscripciones.1');
        $this->post($url, ['evento_id' => $first->evento_id, 'inscripciones' => [$first->id, $other->id]])->assertSessionHasErrors('inscripciones');
        $response = $this->post($url, ['evento_id' => $first->evento_id, 'inscripciones' => [$second->id, $first->id]])->assertOk()
            ->assertViewIs('inscripciones.credenciales-lote')->assertSeeInOrder(['Organizador', 'Participante'])
            ->assertDontSee('Otro evento')->assertSee('Volver a inscripciones');
        $items = $response->viewData('credenciales');
        $this->assertCount(2, $items);
        $this->assertNotSame($items[0]['qr'], $items[1]['qr']);
        $this->get($items[0]['verificacionUrl'])->assertOk()->assertSee('Organizador');
        $this->get(route('inscripciones.credencial', $first))->assertOk()->assertViewIs('personas.vercred');
        $this->actingAs(new \App\Models\User(['id' => 5, 'role' => 'impresiones', 'active' => true]));
        $this->post($url, ['evento_id' => $first->evento_id, 'inscripciones' => [$first->id, $second->id]])->assertOk();
    }

    private function persona(string $documento = '1234567'): Persona
    {
        $grado = Grado::firstOrCreate(['nombre' => 'Dan']);
        return Persona::create(['primer_nombre' => 'Ana', 'primer_apellido' => 'Pérez', 'documento_identidad' => $documento, 'sexo' => 'Femenino', 'telefono' => '70012345', 'grado_id' => $grado->id]);
    }

    public function test_file_card_shows_country_and_flag_in_admin_and_public_verification(): void
    {
        $persona = $this->persona();
        $bolivia = \App\Models\Pais::firstOrCreate(['nombre' => 'Bolivia']);
        $persona->update(['pais_id' => $bolivia->id]);
        $inscripcion = $this->inscripcion($persona);
        $admin = $this->get(route('inscripciones.credencial', $inscripcion))->assertOk();
        $admin->assertSee('credential-file-country')->assertSee('Bandera de Bolivia');
        $url = $admin->viewData('verificacionUrl');
        auth()->logout();
        $this->get($url)->assertOk()->assertSee('credential-file-country')->assertSee('Bandera de Bolivia')
            ->assertSee('images/banderas/bo.svg', false);

        $otro = \App\Models\Pais::firstOrCreate(['nombre' => 'Uruguay']);
        $persona->update(['pais_id' => $otro->id]);
        $this->get($url)->assertOk()->assertSee('credential-file-country')->assertSee('Uruguay')->assertDontSee('Bandera de Uruguay');
        $persona->update(['pais_id' => null]);
        $this->get($url)->assertOk()->assertDontSee('credential-file-country');
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
            ->assertDontSee('data-action-style="credential"', false)
            ->assertDontSee('overview-banner')->assertSee('Persona actualizada.')->assertSee('id="app-notifications"', false);
        $inscripcion = $this->inscripcion($persona);
        $this->get('/inscripciones?evento_id='.$inscripcion->evento_id)->assertOk()->assertSee('data-action-style="credential"', false)
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
        $persona->update(['pais_id' => \App\Models\Pais::where('nombre', 'Bolivia')->value('id')]);
        $response = $this->get(route('personas.credencial', $persona));
        $response->assertOk()->assertViewIs('personas.vercred')->assertSee('Anverso')->assertSee('Reverso')->assertSee('Ficha')
            ->assertSee('Ana Pérez')->assertSee('1234567')->assertSee('Dan')->assertSee('Participante')
            ->assertSee('Seminario Internacional')->assertSee('02/12/2026')->assertSee('05/12/2026')
            ->assertSee('Coliseo La Pampita')->assertSee('Trinidad, Beni')->assertSee('data:image/svg+xml;base64,', false)->assertSee('Imprimir')
            ->assertSee('images/anverso.png', false)->assertSee('images/reverso.png', false)->assertSee('images/Ficha1.png', false)
            ->assertSee('credential-country')->assertSee('Bolivia')->assertSee('images/banderas/bo.svg', false);
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
