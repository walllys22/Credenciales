<?php

namespace Tests\Feature;

use App\Models\{Evento, Grado, Inscripcion, Persona};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthenticatedTestCase as TestCase;

class CertificadoTest extends TestCase
{
    use RefreshDatabase;

    private function inscripcion(string $gradoNombre, string $documento = '1234567'): Inscripcion
    {
        $grado = Grado::firstOrCreate(['nombre' => $gradoNombre]);
        $persona = Persona::create(['primer_nombre' => 'Walter', 'primer_apellido' => 'Landivar', 'segundo_apellido' => 'Limpias', 'documento_identidad' => $documento, 'sexo' => 'Masculino', 'fecha_nacimiento' => '1980-01-01', 'telefono' => '72841511', 'grado_id' => $grado->id]);
        $evento = Evento::create(['nombre' => 'Seminario Internacional Shorin Ryu Shorinkan - Karate & Kobudo', 'fecha_inicio' => '2026-12-02', 'fecha_final' => '2026-12-05', 'lugar' => 'Coliseo La Pampita', 'ciudad' => 'Trinidad, Beni']);
        return Inscripcion::create(['persona_id' => $persona->id, 'evento_id' => $evento->id, 'cargo' => 'Participante']);
    }

    public function test_dan_certificate_uses_sensei_and_data_from_selected_registration(): void
    {
        $inscripcion = $this->inscripcion('Dan');
        $this->get(route('inscripciones.certificado', $inscripcion))->assertOk()->assertViewIs('inscripciones.vercert')
            ->assertViewHas('nombreCertificado', 'Sensei Walter Landivar Limpias')
            ->assertSee('Sensei Walter Landivar Limpias')->assertSee($inscripcion->evento->nombre)
            ->assertSee('images/Certificado.png', false)
            ->assertSee('fonts/agnesa-kyoto.ttf', false)->assertSee('print-certificate');
        $this->get('/inscripciones/999999/certificado')->assertNotFound();
    }

    public function test_kyu_certificate_does_not_add_sensei(): void
    {
        $inscripcion = $this->inscripcion('Kyu');
        $this->get(route('inscripciones.certificado', $inscripcion))->assertOk()
            ->assertViewHas('nombreCertificado', 'Walter Landivar Limpias')->assertDontSee('Sensei Walter');
    }

    public function test_uppercase_dan_and_two_registrations_keep_their_own_names(): void
    {
        $primera = $this->inscripcion('DAN');
        $segunda = $this->inscripcion('DAN', '7654321');
        $segunda->persona->update(['primer_nombre' => 'Luis', 'primer_apellido' => 'Zabala', 'segundo_apellido' => null]);
        $this->get(route('inscripciones.certificado', $segunda))->assertOk()->assertSee('Sensei Luis Zabala')->assertDontSee('Sensei Walter');
        $this->get(route('inscripciones.certificado', $primera))->assertOk()->assertSee('Sensei Walter Landivar Limpias');
        $this->get('/inscripciones')->assertOk()->assertSee('registration-actions')->assertSee('images/diploma.png', false)
            ->assertSee(route('inscripciones.certificado', $segunda), false)->assertSee('title="Certifica"', false);
    }
}
