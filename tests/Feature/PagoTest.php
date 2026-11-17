<?php

namespace Tests\Feature;

use App\Models\{Evento, Grado, Inscripcion, Persona};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthenticatedTestCase as TestCase;

class PagoTest extends TestCase
{
    use RefreshDatabase;

    private function inscripcion(): Inscripcion
    {
        $grado = Grado::firstOrCreate(['nombre' => 'Dan']);
        $persona = Persona::create([
            'primer_nombre' => 'Ana', 'primer_apellido' => 'Pérez',
            'documento_identidad' => (string) (Persona::count() + 1000),
            'sexo' => 'Femenino', 'fecha_nacimiento' => '1995-05-20',
            'telefono' => '70012345', 'grado_id' => $grado->id,
        ]);
        $evento = Evento::create([
            'nombre' => 'Seminario', 'fecha_inicio' => '2026-12-10',
            'fecha_final' => '2026-12-12', 'lugar' => 'Coliseo', 'ciudad' => 'La Paz',
        ]);
        $evento->grados()->attach($grado, ['costo' => '590.50']);

        return Inscripcion::create(['persona_id' => $persona->id, 'evento_id' => $evento->id, 'cargo' => 'Participante']);
    }

    public function test_button_opens_payment_form_and_multiple_payments_are_saved_for_the_correct_registration(): void
    {
        $inscripcion = $this->inscripcion();
        $otra = $this->inscripcion();
        $url = route('inscripciones.pagos.create', $inscripcion);
        $this->get('/inscripciones?evento_id='.$inscripcion->evento_id)->assertOk()->assertSee($url, false)->assertSee('Agregar pago');
        $this->get($url)->assertOk()->assertSee('Ana Pérez')->assertSee('590,50')->assertSee('todavía no tiene pagos');
        foreach (['100.25', '50.10'] as $monto) {
            $this->post($url, [
                'monto' => $monto, 'fecha' => now()->toDateString(), 'observacion' => 'Abono en efectivo',
                'inscripcion_id' => $otra->id,
            ])->assertRedirect($url)->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('pagos', 2);
        $this->assertEquals('150.35', $inscripcion->pagos()->sum('monto'));
        $this->assertSame(0, $otra->pagos()->count());
        $this->get($url)->assertOk()->assertSee('150,35')->assertSee('100,25')->assertSee('50,10')->assertSee('Abono en efectivo');
    }

    public function test_invalid_payments_are_rejected(): void
    {
        $url = route('inscripciones.pagos.store', $this->inscripcion());
        foreach (['0', '-10', '1.123', '10000000000', 'texto'] as $monto) {
            $this->post($url, ['monto' => $monto, 'fecha' => now()->toDateString()])->assertSessionHasErrors('monto');
        }
        $this->post($url, [])->assertSessionHasErrors(['monto', 'fecha']);
        $this->post($url, ['monto' => '10', 'fecha' => now()->addDay()->toDateString()])->assertSessionHasErrors('fecha');
        $this->post($url, ['monto' => '10', 'fecha' => 'no es fecha'])->assertSessionHasErrors('fecha');
        $this->post($url, ['monto' => '10', 'fecha' => now()->toDateString(), 'observacion' => str_repeat('a', 501)])->assertSessionHasErrors('observacion');
        $this->assertDatabaseCount('pagos', 0);
        $this->get('/inscripciones/999/pagos')->assertNotFound();
        $this->post('/inscripciones/999/pagos', ['monto' => '10', 'fecha' => now()->toDateString()])->assertNotFound();
    }

    public function test_dollar_payments_use_event_exchange_rate_and_keep_original_amount(): void
    {
        $inscripcion = $this->inscripcion();
        $inscripcion->evento->update(['tipo_cambio' => '6.96']);
        $url = route('inscripciones.pagos.store', $inscripcion);
        $this->post($url, ['monto' => '10.25', 'moneda' => 'USD', 'fecha' => now()->toDateString()])
            ->assertRedirect($url)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pagos', [
            'inscripcion_id' => $inscripcion->id, 'moneda' => 'USD',
            'monto_original' => '10.25', 'monto' => '71.34', 'tipo_cambio' => '6.96',
        ]);
        $inscripcion->evento->update(['tipo_cambio' => '7']);
        $this->get($url)->assertOk()->assertSee('71,34')->assertSee('10,25')->assertSee('6,96');
        $this->post($url, ['monto' => '9999999999.99', 'moneda' => 'USD', 'fecha' => now()->toDateString()])->assertSessionHasErrors('monto');
        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_dollar_payments_require_exchange_rate_and_valid_currency(): void
    {
        $url = route('inscripciones.pagos.store', $this->inscripcion());
        foreach (['USD', 'EUR', ''] as $moneda) {
            $this->post($url, ['monto' => '10', 'moneda' => $moneda, 'fecha' => now()->toDateString()])->assertSessionHasErrors('moneda');
        }
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_registration_with_payments_cannot_be_deleted_or_assigned_to_another_person_or_event(): void
    {
        $inscripcion = $this->inscripcion();
        $otra = $this->inscripcion();
        $inscripcion->pagos()->create(['monto' => '100', 'fecha' => now()->toDateString()]);
        $this->delete(route('inscripciones.destroy', $inscripcion))->assertSessionHas('error');
        $this->put(route('inscripciones.update', $inscripcion), [
            'persona_id' => $otra->persona_id, 'evento_id' => $inscripcion->evento_id, 'cargo' => 'Participante',
        ])->assertSessionHasErrors('persona_id');
        $this->put(route('inscripciones.update', $inscripcion), [
            'persona_id' => $inscripcion->persona_id, 'evento_id' => $otra->evento_id, 'cargo' => 'Participante',
        ])->assertSessionHasErrors('persona_id');
        $this->put(route('inscripciones.update', $inscripcion), [
            'persona_id' => $inscripcion->persona_id, 'evento_id' => $inscripcion->evento_id, 'cargo' => 'Organizador',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('inscripciones', ['id' => $inscripcion->id, 'persona_id' => $inscripcion->persona_id, 'evento_id' => $inscripcion->evento_id, 'cargo' => 'Organizador']);
        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_pending_balance_supports_mixed_currencies_and_chronological_history_across_pages(): void
    {
        $inscripcion = $this->inscripcion();
        $inscripcion->evento->update(['tipo_cambio' => '6.96']);
        $url = route('inscripciones.pagos.create', $inscripcion);
        $this->get($url)->assertViewHas('saldoPendiente', '590.50');
        // Insert the older payment last to verify ordering by date as well as ID.
        $this->post($url, ['monto' => '10', 'moneda' => 'USD', 'fecha' => '2026-01-02'])->assertSessionHasNoErrors();
        $this->post($url, ['monto' => '100', 'moneda' => 'BOB', 'fecha' => '2026-01-01'])->assertSessionHasNoErrors();
        $this->get($url)->assertOk()->assertViewHas('saldoPendiente', '420.90')
            ->assertViewHas('pagos', fn ($pagos) => $pagos[0]->saldo_pendiente === '420.90' && $pagos[1]->saldo_pendiente === '490.50')
            ->assertSee('USD 10,00')->assertSee('Bs 420,90');
        $otra = $this->inscripcion();
        $otra->pagos()->create(['monto' => '200', 'fecha' => '2026-01-01']);
        for ($i = 0; $i < 10; $i++) {
            $inscripcion->pagos()->create(['monto' => '1', 'fecha' => '2026-01-03']);
        }
        $this->get($url.'?page=2')->assertOk()->assertViewHas('saldoPendiente', '410.90')
            ->assertViewHas('pagos', fn ($pagos) => $pagos[0]->saldo_pendiente === '420.90' && $pagos[1]->saldo_pendiente === '490.50');
    }

    public function test_overpayment_shows_zero_pending_and_credit(): void
    {
        $inscripcion = $this->inscripcion();
        $inscripcion->pagos()->create(['monto' => '600', 'fecha' => '2026-01-01']);
        $this->get(route('inscripciones.pagos.create', $inscripcion))->assertOk()
            ->assertViewHas('saldoPendiente', '0.00')->assertViewHas('saldoAFavor', '9.50')
            ->assertViewHas('pagos', fn ($pagos) => $pagos[0]->saldo_pendiente === '0.00');
    }

    public function test_delete_payment_recalculates_total_and_balance_and_cannot_delete_another_registrations_payment(): void
    {
        $inscripcion = $this->inscripcion();
        $otra = $this->inscripcion();
        $pago = $inscripcion->pagos()->create(['monto' => '100', 'fecha' => '2026-01-01']);
        $restante = $inscripcion->pagos()->create(['monto' => '50', 'fecha' => '2026-01-02']);
        $url = route('inscripciones.pagos.create', $inscripcion);
        $this->get($url)->assertOk()->assertDontSee(route('inscripciones.pagos.destroy', [$inscripcion, $pago]), false)
            ->assertSee(route('inscripciones.pagos.destroy', [$inscripcion, $restante]), false)->assertSee('Eliminar pago');
        $this->delete(route('inscripciones.pagos.destroy', [$otra, $pago]))->assertNotFound();
        $this->assertDatabaseCount('pagos', 2);
        $this->delete(route('inscripciones.pagos.destroy', [$inscripcion, $pago]))->assertRedirect($url)->assertSessionHas('error');
        $this->assertDatabaseCount('pagos', 2);
        $this->delete(route('inscripciones.pagos.destroy', [$inscripcion, $restante]))->assertRedirect($url)->assertSessionHas('success');
        $this->assertDatabaseMissing('pagos', ['id' => $restante->id]);
        $this->get($url)->assertOk()->assertViewHas('totalPagado', '100.00')->assertViewHas('saldoPendiente', '490.50')
            ->assertSee(route('inscripciones.pagos.destroy', [$inscripcion, $pago]), false)
            ->assertViewHas('pagos', fn ($pagos) => $pagos->count() === 1 && $pagos[0]->id === $pago->id && $pagos[0]->saldo_pendiente === '490.50');
        $this->delete(route('inscripciones.pagos.destroy', [$inscripcion, $pago]))->assertRedirect($url);
        $this->get($url)->assertViewHas('saldoPendiente', '590.50')->assertViewHas('totalPagado', '0.00');
    }

    public function test_only_latest_payment_by_date_and_id_can_be_deleted_even_on_other_pages(): void
    {
        $inscripcion = $this->inscripcion();
        $primero = $inscripcion->pagos()->create(['monto' => '10', 'fecha' => '2026-02-11']);
        $ultimo = $inscripcion->pagos()->create(['monto' => '10', 'fecha' => '2026-02-11']);
        for ($i = 0; $i < 10; $i++) {
            $inscripcion->pagos()->create(['monto' => '1', 'fecha' => '2026-01-15']);
        }
        $url = route('inscripciones.pagos.create', $inscripcion);
        $this->get($url)->assertOk()->assertViewHas('pagoEliminableId', $ultimo->id)
            ->assertSee(route('inscripciones.pagos.destroy', [$inscripcion, $ultimo]), false)
            ->assertDontSee(route('inscripciones.pagos.destroy', [$inscripcion, $primero]), false);
        $this->get($url.'?page=2')->assertOk()->assertDontSee('title="Eliminar pago"', false);
        $this->delete(route('inscripciones.pagos.destroy', [$inscripcion, $primero]))->assertSessionHas('error');
        $this->delete(route('inscripciones.pagos.destroy', [$inscripcion, $ultimo]))->assertSessionHas('success');
        $this->get($url)->assertViewHas('pagoEliminableId', $primero->id);
    }

    public function test_registration_list_shows_pending_balance_per_registration_with_mixed_currency_payments(): void
    {
        $inscripcion = $this->inscripcion();
        $sinPagos = $this->inscripcion();
        $sinPagos->update(['evento_id' => $inscripcion->evento_id]);
        $inscripcion->evento->update(['tipo_cambio' => '6.96']);
        $url = route('inscripciones.pagos.store', $inscripcion);
        $this->post($url, ['monto' => '100', 'moneda' => 'BOB', 'fecha' => '2026-01-01'])->assertSessionHasNoErrors();
        $this->post($url, ['monto' => '10', 'moneda' => 'USD', 'fecha' => '2026-01-02'])->assertSessionHasNoErrors();
        $this->get('/inscripciones?evento_id='.$inscripcion->evento_id)->assertOk()->assertSeeInOrder(['<th>Costo / Pagado / Saldo</th>', 'Acciones', 'Costo:', 'Pagado:', 'Saldo:'], false)
            ->assertSee('Bs 420,90')->assertViewHas('inscripciones', fn ($items) =>
                $items->firstWhere('id', $inscripcion->id)->saldo_pendiente === '420.90'
                && $items->firstWhere('id', $sinPagos->id)->saldo_pendiente === '590.50');
        $pago = $inscripcion->pagos()->orderByDesc('fecha')->firstOrFail();
        $this->delete(route('inscripciones.pagos.destroy', [$inscripcion, $pago]))->assertSessionHas('success');
        $this->get('/inscripciones?evento_id='.$inscripcion->evento_id)->assertOk()->assertSee('Bs 490,50');
        $inscripcion->pagos()->create(['monto' => '600', 'fecha' => '2026-01-03']);
        $this->get('/inscripciones?evento_id='.$inscripcion->evento_id)->assertOk()->assertViewHas('inscripciones', fn ($items) =>
            $items->firstWhere('id', $inscripcion->id)->saldo_pendiente === '0.00');
    }

    public function test_total_payment_discount_settles_balance_and_deleting_it_restores_pending_amount(): void
    {
        $inscripcion = $this->inscripcion();
        $url = route('inscripciones.pagos.store', $inscripcion);
        $this->post($url, ['monto' => '100', 'fecha' => '2026-01-01'])->assertSessionHasNoErrors();
        $this->post($url, [
            'monto' => '400', 'moneda' => 'BOB', 'fecha' => '2026-01-02', 'pago_total' => '1',
            'descuento' => '5000',
        ])->assertSessionHasNoErrors()->assertRedirect($url);
        $pago = $inscripcion->pagos()->orderByDesc('id')->firstOrFail();
        $this->assertTrue($pago->pago_total);
        $this->assertSame('400.00', $pago->monto);
        $this->assertSame('90.50', $pago->descuento);
        $this->get($url)->assertOk()->assertViewHas('totalPagado', '500.00')->assertViewHas('totalDescuento', '90.50')
            ->assertViewHas('saldoPendiente', '0.00')->assertViewHas('saldoAFavor', '0.00')
            ->assertViewHas('pagos', fn ($pagos) => $pagos[0]->saldo_pendiente === '0.00' && $pagos[1]->saldo_pendiente === '490.50');
        $this->get('/inscripciones?evento_id='.$inscripcion->evento_id)->assertViewHas('inscripciones', fn ($items) => $items->firstWhere('id', $inscripcion->id)->saldo_pendiente === '0.00');
        $this->delete(route('inscripciones.pagos.destroy', [$inscripcion, $pago]))->assertSessionHas('success');
        $this->get($url)->assertViewHas('saldoPendiente', '490.50')->assertViewHas('totalDescuento', '0.00');
        $this->get('/inscripciones?evento_id='.$inscripcion->evento_id)->assertViewHas('inscripciones', fn ($items) => $items->firstWhere('id', $inscripcion->id)->saldo_pendiente === '490.50');
    }

    public function test_total_payment_discount_uses_converted_dollar_amount(): void
    {
        $inscripcion = $this->inscripcion();
        $inscripcion->evento->update(['tipo_cambio' => '6.96']);
        $url = route('inscripciones.pagos.store', $inscripcion);
        $this->post($url, ['monto' => '70', 'moneda' => 'USD', 'fecha' => '2026-01-01', 'pago_total' => '1'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pagos', ['inscripcion_id' => $inscripcion->id, 'monto' => '487.20', 'monto_original' => '70.00', 'descuento' => '103.30', 'pago_total' => true]);
        $this->get($url)->assertOk()->assertViewHas('saldoPendiente', '0.00')->assertViewHas('totalPagado', '487.20');
        $this->post($url, ['monto' => '10', 'fecha' => '2026-01-02', 'pago_total' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('0.00', $inscripcion->pagos()->orderByDesc('id')->firstOrFail()->descuento);
        $this->get($url)->assertViewHas('saldoPendiente', '0.00')->assertViewHas('saldoAFavor', '10.00');
    }
}
