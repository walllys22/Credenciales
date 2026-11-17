<?php

namespace Tests\Feature;

use App\Models\{Evento, Grado, Inscripcion, Persona};
use App\Support\CatalogoGrados;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthenticatedTestCase as TestCase;

class CatalogoGradosTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_replacement_keeps_people_payments_and_unnumbered_grades(): void
    {
        $dan = Grado::create(['nombre' => 'Dan']);
        $kyu = Grado::create(['nombre' => 'Kyu']);
        Grado::create(['nombre' => 'Jefe Dojo']);
        $evento = Evento::create(['nombre' => 'Seminario', 'fecha_inicio' => '2026-12-02', 'fecha_final' => '2026-12-05', 'lugar' => 'Dojo', 'ciudad' => 'Trinidad', 'tipo_cambio' => '11.70']);
        foreach ([$dan, $kyu] as $grado) {
            $evento->grados()->attach($grado, ['costo' => '100']);
            $persona = Persona::create(['primer_nombre' => $grado->nombre, 'primer_apellido' => 'Prueba', 'sexo' => 'Masculino', 'grado_id' => $grado->id, 'foto' => 'personas/original.jpg']);
            $inscripcion = Inscripcion::create(['evento_id' => $evento->id, 'persona_id' => $persona->id, 'cargo' => 'Participante']);
            $inscripcion->pagos()->create(['monto' => '20', 'fecha' => '2026-01-01']);
        }
        $originales = Persona::all()->map(fn ($p) => $p->getAttributes())->all();
        CatalogoGrados::reemplazar();
        $this->assertSame(Grado::DETALLADOS, Grado::orderBy('id')->pluck('nombre')->all());
        $this->assertDatabaseCount('personas', 2);
        $this->assertDatabaseCount('inscripciones', 2);
        $this->assertDatabaseCount('pagos', 2);
        foreach (Persona::with('grado')->get() as $i => $persona) {
            $this->assertNull($persona->grado_id);
            $this->assertSame($originales[$i]['primer_nombre'], $persona->grado->nombre);
            foreach ($originales[$i] as $key => $value) {
                if (!in_array($key, ['grado_id', 'grado_anterior'])) $this->assertEquals($value, $persona->$key);
            }
            $this->assertSame($persona->grado->esDan() ? '585.00' : '351.00', $persona->inscripciones->first()->tarifa());
        }
        $this->get('/grados')->assertOk()->assertSee('10mo Kyu')->assertSee('10mo Dan')->assertDontSee('Jefe Dojo');
        CatalogoGrados::reemplazar();
        $this->assertDatabaseCount('grados', 18);
        $this->assertDatabaseCount('evento_grado', 18);
    }

    public function test_numbered_grades_charge_by_category_and_only_dan_leaders_charge_100(): void
    {
        CatalogoGrados::reemplazar();
        $evento = Evento::create(['nombre' => 'Seminario', 'fecha_inicio' => '2026-12-02', 'fecha_final' => '2026-12-05', 'lugar' => 'Dojo', 'ciudad' => 'Trinidad', 'tipo_cambio' => '11.70']);
        foreach (Grado::all() as $grado) {
            $persona = Persona::create(['primer_nombre' => 'Ana', 'primer_apellido' => 'Prueba', 'sexo' => 'Femenino', 'grado_id' => $grado->id]);
            $inscripcion = new Inscripcion(['jefe_dojo' => false]);
            $inscripcion->setRelation('persona', $persona)->setRelation('evento', $evento);
            $this->assertSame($grado->esDan() ? '585.00' : '351.00', $inscripcion->tarifa());
            $inscripcion->jefe_dojo = true;
            $this->assertSame($grado->esDan() ? '1170.00' : '351.00', $inscripcion->tarifa());
            $inscripcion->invitado_sin_costo = true;
            $this->assertSame('0.00', $inscripcion->tarifa());
        }
    }

    public function test_legacy_participant_can_choose_numbered_grade_within_the_same_category(): void
    {
        $general = Grado::create(['nombre' => 'Dan']);
        $persona = Persona::create(['primer_nombre' => 'Ana', 'primer_apellido' => 'Prueba', 'sexo' => 'Femenino', 'grado_id' => $general->id]);
        $evento = Evento::create(['nombre' => 'Seminario', 'fecha_inicio' => '2026-12-02', 'fecha_final' => '2026-12-05', 'lugar' => 'Dojo', 'ciudad' => 'Trinidad', 'tipo_cambio' => '11.70']);
        $inscripcion = Inscripcion::create(['evento_id' => $evento->id, 'persona_id' => $persona->id, 'cargo' => 'Participante']);
        CatalogoGrados::reemplazar();
        $data = ['registro_integrado' => 1, 'persona_id' => $persona->id, 'primer_nombre' => 'Ana', 'primer_apellido' => 'Prueba', 'sexo' => 'Femenino', 'grado_id' => Grado::where('nombre', '3er Dan')->value('id'), 'evento_id' => $evento->id, 'cargo' => 'Participante'];
        $this->put(route('inscripciones.update', $inscripcion), $data)->assertRedirect('/inscripciones')->assertSessionHasNoErrors();
        $this->assertSame('3er Dan', $persona->fresh()->grado->nombre);
        $this->assertSame('585.00', $inscripcion->fresh()->tarifa());
        $data['grado_id'] = Grado::where('nombre', '1er Kyu')->value('id');
        $this->put(route('inscripciones.update', $inscripcion), $data)->assertSessionHasErrors('grado_id');
    }
}
