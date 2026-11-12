<?php

namespace Tests\Feature;

use App\Models\Grado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthenticatedTestCase as TestCase;

class GradoTest extends TestCase
{
    use RefreshDatabase;

    public function test_grado_can_be_created_edited_searched_and_deleted(): void
    {
        $this->get('/grados/create')->assertOk()->assertSee('Nuevo grado');
        $this->post('/grados', ['nombre' => 'Inicial'])->assertRedirect('/grados')->assertSessionHasNoErrors();
        $grado = Grado::firstOrFail();
        $this->get("/grados/{$grado->id}/edit")->assertOk()->assertSee('Inicial');
        $this->put("/grados/{$grado->id}", ['nombre' => 'Avanzado'])->assertRedirect('/grados')->assertSessionHasNoErrors();
        $this->get('/grados?q=Avanz')->assertOk()->assertSee('Avanzado');
        $this->get('/grados?q=Inexistente')->assertOk()->assertDontSee('Avanzado')->assertSee('No encontramos grados');
        $this->delete("/grados/{$grado->id}")->assertRedirect('/grados');
        $this->assertDatabaseMissing('grados', ['id' => $grado->id]);
    }

    public function test_name_is_required_and_unique_but_can_be_retained_when_editing(): void
    {
        $grado = Grado::create(['nombre' => 'Inicial']);
        $otro = Grado::create(['nombre' => 'Avanzado']);
        $this->post('/grados', ['nombre' => '   '])->assertSessionHasErrors('nombre');
        $this->post('/grados', ['nombre' => 'Inicial'])->assertSessionHasErrors('nombre');
        $this->put("/grados/{$otro->id}", ['nombre' => 'Inicial'])->assertSessionHasErrors('nombre');
        $this->put("/grados/{$grado->id}", ['nombre' => 'Inicial'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('grados', 2);
    }
}
