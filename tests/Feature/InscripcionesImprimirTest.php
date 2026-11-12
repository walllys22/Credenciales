<?php

namespace Tests\Feature;

use App\Models\{Evento, Grado, Inscripcion, Persona};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthenticatedTestCase as TestCase;

// El informe debe incluir incluso los inscritos que no aparecen en la primera página del registro.
class InscripcionesImprimirTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_lists_every_registration_even_with_filters_and_without_pagination(): void
    {
        $grado = Grado::create(['nombre' => 'Dan']);
        $evento = Evento::create(['nombre' => 'Evento de prueba', 'fecha_inicio' => '2026-12-02', 'fecha_final' => '2026-12-05', 'lugar' => 'Coliseo', 'ciudad' => 'Trinidad']);
        for ($i = 1; $i <= 14; $i++) {
            $persona = Persona::create(['primer_nombre' => 'Participante '.str_pad($i, 2, '0', STR_PAD_LEFT), 'primer_apellido' => 'Prueba', 'documento_identidad' => '9000'.$i, 'sexo' => 'Masculino', 'fecha_nacimiento' => '1990-01-01', 'telefono' => '70012345', 'grado_id' => $grado->id]);
            Inscripcion::create(['persona_id' => $persona->id, 'evento_id' => $evento->id, 'cargo' => 'Participante']);
        }
        $this->get(route('inscripciones.index'))->assertOk()
            ->assertSeeInOrder([route('inscripciones.imprimir'), route('inscripciones.create')], false)
            ->assertSee('images/Impresora.png', false);
        $response = $this->get(route('inscripciones.imprimir', ['q' => 'inexistente', 'evento_id' => 99999]))->assertOk();
        $response->assertViewHas('inscripciones', fn ($rows) => $rows->count() === 14)
            ->assertSeeInOrder(['Participante 01 Prueba', 'Participante 14 Prueba'])
            ->assertDontSee('<th>Evento</th>', false)->assertDontSee('Evento de prueba')->assertSee('Listado de Inscritos')
            ->assertSeeInOrder(['<th>Tarifa</th>', '<th>Pagado</th>', '<th>Saldo</th>'], false);
    }

    public function test_report_amounts_include_mixed_payments_and_discount_without_inflating_paid_total(): void
    {
        $grado = Grado::create(['nombre' => 'Dan']);
        $evento = Evento::create(['nombre' => 'Seminario', 'fecha_inicio' => '2026-12-02', 'fecha_final' => '2026-12-05', 'lugar' => 'Coliseo', 'ciudad' => 'Trinidad']);
        $evento->grados()->attach($grado, ['costo' => '590.50']);
        $persona = Persona::create(['primer_nombre' => 'Ana', 'primer_apellido' => 'Prueba', 'sexo' => 'Femenino', 'grado_id' => $grado->id]);
        $inscripcion = Inscripcion::create(['persona_id' => $persona->id, 'evento_id' => $evento->id, 'cargo' => 'Participante']);
        $inscripcion->pagos()->create(['monto' => '69.60', 'moneda' => 'USD', 'monto_original' => '10', 'tipo_cambio' => '6.96', 'fecha' => '2026-01-02']);
        $url = route('inscripciones.imprimir');
        $this->get($url)->assertOk()->assertSee('$us 10,00')->assertDontSee('Bs 69,60')->assertDontSee('Bs 0,00');
        $inscripcion->pagos()->create(['monto' => '100', 'fecha' => '2026-01-01']);
        $this->get($url)->assertOk()->assertSee('Bs 590,50')->assertSee('Bs 100,00')->assertSee('$us 10,00')->assertSee('Bs 420,90')
            ->assertDontSee('Bs 169,60')
            ->assertViewHas('inscripciones', fn ($rows) => $rows[0]->saldo_pendiente === '420.90');
        $inscripcion->pagos()->create(['monto' => '330.40', 'fecha' => '2026-01-03', 'pago_total' => true, 'descuento' => '90.50']);
        $this->get($url)->assertOk()->assertSee('Bs 430,40')->assertSee('$us 10,00')->assertSee('Bs 0,00')
            ->assertViewHas('totalPagadoBs', '430.40')->assertViewHas('totalPagadoUsd', '10.00')
            ->assertSeeInOrder(['Pagado Bs.-', '430.40', 'Pagado $us.-', '10.00'])
            ->assertViewHas('inscripciones', fn ($rows) => $rows[0]->total_pagado === '500.00' && $rows[0]->saldo_pendiente === '0.00');
    }

    public function test_empty_report_can_be_opened(): void
    {
        $this->get(route('inscripciones.imprimir'))->assertOk()->assertSee('No hay personas inscritas.')
            ->assertViewHas('totalPagadoBs', '0.00')->assertViewHas('totalPagadoUsd', '0.00')
            ->assertSeeInOrder(['Pagado Bs.-', '0.00', 'Pagado $us.-', '0.00']);
    }
}
