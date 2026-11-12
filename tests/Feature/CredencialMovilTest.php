<?php

namespace Tests\Feature;

use App\Models\{Evento, Grado, Inscripcion, Persona};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Storage, URL};
use Tests\AuthenticatedTestCase as TestCase;

class CredencialMovilTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_uses_public_origin_and_mobile_page_shows_only_the_same_file_card(): void
    {
        Storage::fake('local');
        config(['credenciales.public_url' => 'https://credencial.example.test']);
        $grado = Grado::create(['nombre' => 'Dan']);
        $foto = UploadedFile::fake()->image('foto.jpg')->store('personas', 'local');
        $imagen = UploadedFile::fake()->image('evento.png')->store('eventos', 'local');
        $persona = Persona::create(['primer_nombre' => 'Luis', 'primer_apellido' => 'Zabala', 'documento_identidad' => '5588395', 'sexo' => 'Masculino', 'fecha_nacimiento' => '1990-01-01', 'telefono' => '70012345', 'grado_id' => $grado->id, 'foto' => $foto]);
        $evento = Evento::create(['nombre' => 'Seminario Internacional', 'fecha_inicio' => '2026-12-02', 'fecha_final' => '2026-12-05', 'lugar' => 'Coliseo', 'ciudad' => 'Trinidad', 'imagen' => $imagen]);
        $inscripcion = Inscripcion::create(['persona_id' => $persona->id, 'evento_id' => $evento->id, 'cargo' => 'Organizador']);
        $admin = $this->get(route('inscripciones.credencial', $inscripcion))->assertOk();
        $url = $admin->viewData('verificacionUrl');
        $this->assertStringStartsWith('https://credencial.example.test/credenciales/', $url);
        $mobile = $this->get($url)->assertOk()->assertViewIs('personas.verificarcred')
            ->assertSee('mobile-ficha')->assertSee('credential-file')->assertSee('Luis Zabala')->assertSee('5588395')
            ->assertSee('Organizador')->assertSee('70012345')->assertSee('02/12/2026')->assertSee('05/12/2026')
            ->assertSee('images/icono.png', false)->assertSee('images/Ficha1.png', false)->assertDontSee('images/Logo.png', false)
            ->assertDontSee('sidebar')->assertDontSee('Anverso')->assertDontSee('Reverso')->assertDontSee('Imprimir');
        $this->get($mobile->viewData('fotoUrl'))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get($mobile->viewData('eventoImagenUrl'))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get(route('credenciales.foto', $inscripcion))->assertForbidden();
        $this->get(route('credenciales.imagen', $inscripcion))->assertForbidden();
        $inscripcion->delete();
        $this->get($url)->assertNotFound();
        $this->get($mobile->viewData('fotoUrl'))->assertNotFound();
    }
}
