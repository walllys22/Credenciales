<?php

namespace Tests\Feature;

use App\Models\{Evento, Grado};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthenticatedTestCase as TestCase;

class EventoCurrencyTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        return ['nombre' => 'Seminario', 'fecha_inicio' => '2026-12-02', 'fecha_final' => '2026-12-05', 'lugar' => 'Coliseo', 'ciudad' => 'Trinidad'];
    }

    public function test_dan_and_kyu_convert_usd_to_bs_and_ignore_tampered_bs_values(): void
    {
        $dan = Grado::create(['nombre' => 'Dan']); $kyu = Grado::create(['nombre' => 'Kyu']);
        $data = $this->data() + ['tipo_cambio' => '6.96', 'costos_usd' => [$dan->id => '100', $kyu->id => '50'], 'costos' => [$dan->id => '1'], 'costos_bs' => [$kyu->id => '1']];
        $this->post('/eventos', $data)->assertSessionHasNoErrors()->assertRedirect('/eventos');
        $evento = Evento::firstOrFail();
        $this->assertSame('6.96', $evento->tipo_cambio);
        $this->assertDatabaseHas('evento_grado', ['evento_id' => $evento->id, 'grado_id' => $dan->id, 'costo_usd' => 100, 'costo' => 696]);
        $this->assertDatabaseHas('evento_grado', ['evento_id' => $evento->id, 'grado_id' => $kyu->id, 'costo_usd' => 50, 'costo' => 348]);
        $data['tipo_cambio'] = '7.10';
        $this->put('/eventos/'.$evento->id, $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('evento_grado', ['grado_id' => $dan->id, 'costo' => 710]);
        $this->assertDatabaseHas('evento_grado', ['grado_id' => $kyu->id, 'costo' => 355]);
        $this->get('/eventos/'.$evento->id.'/edit')->assertOk()->assertSee('readonly')->assertSee('100.00')->assertSee('7.10');
    }

    public function test_rounding_free_access_and_empty_grades(): void
    {
        $dan = Grado::create(['nombre' => 'Dan']); $kyu = Grado::create(['nombre' => 'Kyu']); $other = Grado::create(['nombre' => 'Otro']);
        $this->post('/eventos', $this->data() + ['tipo_cambio' => '6.97', 'costos_usd' => [$dan->id => '0.50', $kyu->id => '0', $other->id => null]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('evento_grado', ['grado_id' => $dan->id, 'costo' => 3.49]);
        $this->assertDatabaseHas('evento_grado', ['grado_id' => $kyu->id, 'costo' => 0]);
        $this->assertDatabaseMissing('evento_grado', ['grado_id' => $other->id]);
    }

    public function test_invalid_rates_amounts_and_conversion_overflow_are_rejected(): void
    {
        $dan = Grado::create(['nombre' => 'Dan']);
        foreach ([null, '0', '-1', '1.123'] as $rate) {
            $this->post('/eventos', $this->data() + ['tipo_cambio' => $rate, 'costos_usd' => [$dan->id => '100']])->assertSessionHasErrors('tipo_cambio');
        }
        foreach (['-1', '0.001', '9999999999.99'] as $amount) {
            $this->post('/eventos', $this->data() + ['tipo_cambio' => '7', 'costos_usd' => [$dan->id => $amount]])->assertSessionHasErrors('costos_usd.'.$dan->id);
        }
        $this->assertDatabaseCount('eventos', 0);
    }

    public function test_legacy_bs_prices_are_preserved_until_explicit_conversion(): void
    {
        $dan = Grado::create(['nombre' => 'Dan']); $evento = Evento::create($this->data());
        $evento->grados()->attach($dan, ['costo' => '650.00']);
        $this->put('/eventos/'.$evento->id, $this->data() + ['tipo_cambio' => null, 'costos_usd' => [$dan->id => null]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('evento_grado', ['grado_id' => $dan->id, 'costo' => 650, 'costo_usd' => null]);
        $this->assertNull($evento->fresh()->tipo_cambio);
        $this->put('/eventos/'.$evento->id, $this->data() + ['tipo_cambio' => '7', 'costos_usd' => [$dan->id => '100']])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('evento_grado', ['grado_id' => $dan->id, 'costo' => 700, 'costo_usd' => 100]);
    }
}
