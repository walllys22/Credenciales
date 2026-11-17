<?php

namespace Tests\Feature;

use App\Models\Pais;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\AuthenticatedTestCase as TestCase;

// Verifica el catálogo inicial, su estructura de dos campos y el mantenimiento de países.
class PaisTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_catalog_has_two_fields_and_alphabetical_initial_data(): void
    {
        $this->assertSame(['id', 'nombre'], Schema::getColumnListing('paises'));
        $this->assertSame(['Argentina', 'Bolivia', 'Chile', 'Estados Unidos'], Pais::orderBy('nombre')->pluck('nombre')->all());
        $this->get(route('paises.index'))->assertOk()
            ->assertSeeInOrder(['Argentina', 'Bolivia', 'Chile', 'Estados Unidos']);
        $this->get(route('dashboard'))->assertOk()
            ->assertSeeInOrder([route('paises.index'), route('grados.index'), route('inscripciones.index')], false)
            ->assertDontSee('href="'.route('personas.index').'"', false);
    }

    public function test_country_can_be_created_edited_searched_and_deleted(): void
    {
        $this->get(route('paises.create'))->assertOk();
        $this->post(route('paises.store'), ['nombre' => 'Perú'])->assertRedirect(route('paises.index'));
        $pais = Pais::where('nombre', 'Perú')->firstOrFail();
        $this->get(route('paises.edit', $pais))->assertOk();
        $this->put(route('paises.update', $pais), ['nombre' => 'Uruguay'])->assertRedirect(route('paises.index'));
        $this->get(route('paises.index', ['q' => 'Uruguay']))->assertOk()->assertSee('Uruguay')->assertDontSee('Argentina');
        $this->post(route('paises.store'), ['nombre' => 'Bolivia'])->assertSessionHasErrors('nombre');
        $this->post(route('paises.store'), ['nombre' => '   '])->assertSessionHasErrors('nombre');
        $this->delete(route('paises.destroy', $pais))->assertRedirect(route('paises.index'));
        $this->assertDatabaseMissing('paises', ['id' => $pais->id]);
    }
}
