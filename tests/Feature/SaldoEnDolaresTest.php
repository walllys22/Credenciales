<?php

namespace Tests\Feature;

use App\Models\{Evento, Grado, Inscripcion, Persona};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthenticatedTestCase;

class SaldoEnDolaresTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    private function crearInscripcion(bool $jefe = false): Inscripcion
    {
        $grado = Grado::firstOrCreate(['nombre' => '2do Dan']);
        $persona = Persona::create(['primer_nombre' => 'Walter', 'primer_apellido' => 'Prueba', 'sexo' => 'Masculino', 'grado_id' => $grado->id]);
        $evento = Evento::create(['nombre' => 'Seminario', 'fecha_inicio' => '2026-12-10', 'fecha_final' => '2026-12-12', 'lugar' => 'Coliseo', 'ciudad' => 'La Paz', 'tipo_cambio' => '12']);
        $evento->grados()->attach($grado, ['costo' => '600', 'costo_usd' => '50']);
        return Inscripcion::create(['persona_id' => $persona->id, 'evento_id' => $evento->id, 'cargo' => 'Participante', 'jefe_dojo' => $jefe]);
    }

    public function test_pago_historico_en_dolares_cancela_tarifa_de_jefe(): void
    {
        $inscripcion = $this->crearInscripcion(true);
        $inscripcion->pagos()->create(['moneda' => 'USD', 'monto_original' => '100', 'monto' => '1170', 'tipo_cambio' => '11.70', 'fecha' => '2026-01-01']);
        $this->get(route('inscripciones.pagos.create', $inscripcion))->assertOk()
            ->assertViewHas('saldoPendiente', '0.00')->assertSee('USD 100,00')->assertDontSee('Equivale a Bs');
        $this->get(route('inscripciones.index', ['evento_id' => $inscripcion->evento_id]))->assertOk()->assertSee('$us 100,00')->assertSee('Bs 0,00');
    }

    public function test_pagos_en_bolivianos_y_saldo_en_ambas_monedas(): void
    {
        $inscripcion = $this->crearInscripcion();
        foreach (['200', '300'] as $monto) $inscripcion->pagos()->create(['moneda' => 'BOB', 'monto' => $monto, 'fecha' => '2026-01-01']);
        $this->get(route('inscripciones.pagos.create', $inscripcion))->assertOk()->assertSee('$us 8,33 / Bs 100,00');
        $this->get(route('inscripciones.index', ['evento_id' => $inscripcion->evento_id]))->assertOk()->assertSee('$us 8,33 / Bs 100,00');
        $this->get(route('inscripciones.imprimir', ['evento_id' => $inscripcion->evento_id]))->assertOk()->assertSee('100.00')->assertSee('8.33');
    }

    public function test_nuevos_pagos_mixtos_usan_tipo_del_evento_y_descuento(): void
    {
        $inscripcion = $this->crearInscripcion();
        $url = route('inscripciones.pagos.store', $inscripcion);
        $this->post($url, ['moneda' => 'BOB', 'monto' => '120', 'fecha' => '2026-01-01'])->assertSessionHasNoErrors();
        $this->post($url, ['moneda' => 'USD', 'monto' => '40', 'fecha' => '2026-01-02'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pagos', ['inscripcion_id' => $inscripcion->id, 'moneda' => 'USD', 'monto' => '40', 'monto_original' => '40']);
        $inscripcion->evento->update(['tipo_cambio' => '15']);
        $this->get($url)->assertOk()->assertSee('$us 2,00 / Bs 30,00')->assertSee('TC 15,00');
        $this->get(route('inscripciones.index', ['evento_id' => $inscripcion->evento_id]))->assertOk()->assertSee('$us 2,00 / Bs 30,00');
        $inscripcion2 = $this->crearInscripcion();
        $url2 = route('inscripciones.pagos.store', $inscripcion2);
        $this->post($url2, ['moneda' => 'BOB', 'monto' => '100', 'fecha' => '2026-01-01', 'pago_total' => 1])->assertSessionHasNoErrors();
        $this->get($url2)->assertOk()->assertViewHas('saldoPendiente', fn ($saldo) => (float) $saldo === 0.0);
        $this->delete(route('inscripciones.pagos.destroy', [$inscripcion2, $inscripcion2->pagos()->first()]))->assertRedirect();
        $this->get($url2)->assertOk()->assertViewHas('saldoPendiente', '50.00');
    }

    public function test_pago_y_descuento_historico_utilizan_el_tipo_vigente_del_evento(): void
    {
        $inscripcion = $this->crearInscripcion();
        $inscripcion->pagos()->create(['moneda' => 'BOB', 'monto' => '240', 'descuento' => '120', 'tipo_cambio' => '10', 'fecha' => '2026-01-01']);
        $this->get(route('inscripciones.pagos.create', $inscripcion))->assertOk()
            ->assertSee('$us 20,00 / Bs 240,00')->assertSee('Equivale a $us 20,00')->assertSee('TC 12,00');
        $inscripcion->evento->update(['tipo_cambio' => '15']);
        $this->get(route('inscripciones.pagos.create', $inscripcion))->assertOk()
            ->assertSee('$us 26,00 / Bs 390,00')->assertSee('Equivale a $us 16,00')->assertSee('TC 15,00');
    }

    public function test_pago_en_dolares_no_necesita_tipo_de_cambio(): void
    {
        $inscripcion = $this->crearInscripcion(true);
        $inscripcion->evento->update(['tipo_cambio' => null]);
        $url = route('inscripciones.pagos.store', $inscripcion);
        $this->post($url, ['moneda' => 'USD', 'monto' => '100', 'fecha' => '2026-01-01'])->assertSessionHasNoErrors();
        $this->get($url)->assertOk()->assertViewHas('saldoPendiente', '0.00');
        $this->post($url, ['moneda' => 'BOB', 'monto' => '100', 'fecha' => '2026-01-01'])->assertSessionHasErrors('moneda');
    }
}
