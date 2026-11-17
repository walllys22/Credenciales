<?php

namespace Tests\Feature;

use App\Models\{Grado, Persona};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\AuthenticatedTestCase as TestCase;

class PersonaPhotoTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        $grado = Grado::firstOrCreate(['nombre' => 'Dan']);
        return ['primer_nombre' => 'Ana', 'primer_apellido' => 'Pérez', 'documento_identidad' => '1234567', 'sexo' => 'Femenino', 'telefono' => '70012345', 'grado_id' => $grado->id];
    }

    public function test_phone_is_optional_when_creating_and_editing(): void
    {
        Storage::fake('local');
        $data = $this->data();
        unset($data['telefono']);
        $this->post('/personas', $data + ['foto' => UploadedFile::fake()->image('foto.jpg')])
            ->assertRedirect('/personas')->assertSessionHasNoErrors();
        $persona = Persona::firstOrFail();
        $this->assertNull($persona->telefono);
        $this->get(route('personas.edit', $persona))->assertOk();
        $this->put(route('personas.update', $persona), $data + ['telefono' => '70012345'])
            ->assertSessionHasNoErrors();
        $this->put(route('personas.update', $persona), $data + ['telefono' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($persona->fresh()->telefono);
        $this->put(route('personas.update', $persona), $data + ['telefono' => str_repeat('1', 31)])
            ->assertSessionHasErrors('telefono');
    }

    public function test_photo_is_required_for_new_person_and_allows_exactly_five_mb(): void
    {
        Storage::fake('local');
        $data = $this->data();
        $this->post('/personas', $data)->assertSessionHasErrors('foto');
        $this->assertDatabaseCount('personas', 0);
        $this->post('/personas', $data + ['foto' => UploadedFile::fake()->image('foto.jpg')->size(5120)])->assertSessionHasNoErrors()->assertRedirect('/personas');
        $persona = Persona::firstOrFail();
        Storage::disk('local')->assertExists($persona->foto);
        $this->get(route('personas.foto', $persona))->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/personas')->assertOk()->assertSee(route('personas.foto', $persona), false)->assertSee('images/Logo.png', false);
        $this->get('/personas/create')->assertOk()->assertSee('multipart/form-data')->assertSee('Peso máximo: 5 MB');
    }

    public function test_multiple_people_can_have_no_document_and_supplied_documents_remain_unique(): void
    {
        Storage::fake('local');
        $data = $this->data();
        unset($data['documento_identidad']);
        $this->post('/personas', $data + ['foto' => UploadedFile::fake()->image('uno.jpg')])
            ->assertRedirect('/personas')->assertSessionHasNoErrors();
        $this->post('/personas', $data + ['documento_identidad' => '   ', 'foto' => UploadedFile::fake()->image('dos.jpg')])
            ->assertRedirect('/personas')->assertSessionHasNoErrors();
        $this->assertSame(2, Persona::whereNull('documento_identidad')->count());
        [$primera, $segunda] = Persona::orderBy('id')->get()->all();
        $this->put(route('personas.update', $primera), $data + ['documento_identidad' => '1234567'])->assertSessionHasNoErrors();
        $this->put(route('personas.update', $primera), $data + ['documento_identidad' => '1234567'])->assertSessionHasNoErrors();
        $this->put(route('personas.update', $segunda), $data + ['documento_identidad' => '1234567'])->assertSessionHasErrors('documento_identidad');
        $this->put(route('personas.update', $primera), $data + ['documento_identidad' => ''])->assertSessionHasNoErrors();
        $this->assertNull($primera->fresh()->documento_identidad);
        $this->get(route('personas.edit', $primera))->assertOk();
        $this->get('/personas')->assertOk();
    }

    public function test_oversized_and_non_image_files_are_rejected(): void
    {
        Storage::fake('local');
        $data = $this->data();
        foreach ([UploadedFile::fake()->image('grande.jpg')->size(5121), UploadedFile::fake()->create('texto.txt', 1, 'text/plain'), UploadedFile::fake()->create('vector.svg', 1, 'image/svg+xml')] as $file) {
            $this->post('/personas', $data + ['foto' => $file])->assertSessionHasErrors('foto');
        }
        $this->assertDatabaseCount('personas', 0);
        $this->assertEmpty(Storage::disk('local')->allFiles('personas'));
    }

    public function test_photo_is_preserved_replaced_and_removed_when_person_is_deleted(): void
    {
        Storage::fake('local');
        $data = $this->data();
        $this->post('/personas', $data + ['foto' => UploadedFile::fake()->image('original.png')])->assertSessionHasNoErrors();
        $persona = Persona::firstOrFail(); $original = $persona->foto;
        $this->get("/personas/{$persona->id}/edit")->assertOk()->assertSee(route('personas.foto', $persona), false);
        $this->put("/personas/{$persona->id}", $data)->assertSessionHasNoErrors();
        $this->assertSame($original, $persona->fresh()->foto);
        $this->put("/personas/{$persona->id}", $data + ['foto' => UploadedFile::fake()->image('grande.jpg')->size(5121)])->assertSessionHasErrors('foto');
        $this->assertSame($original, $persona->fresh()->foto);
        Storage::disk('local')->assertExists($original);
        $this->put("/personas/{$persona->id}", $data + ['foto' => UploadedFile::fake()->image('nueva.jpg')])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($original);
        $replacement = $persona->fresh()->foto;
        Storage::disk('local')->assertExists($replacement);
        $this->delete("/personas/{$persona->id}")->assertRedirect('/personas');
        Storage::disk('local')->assertMissing($replacement);
    }

    public function test_existing_person_without_photo_can_be_edited_and_has_a_fallback(): void
    {
        Storage::fake('local');
        $data = $this->data(); $persona = Persona::create($data);
        $this->put("/personas/{$persona->id}", $data)->assertSessionHasNoErrors();
        $this->get(route('personas.foto', $persona))->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $persona->update(['foto' => 'personas/ausente.jpg']);
        $this->get(route('personas.foto', $persona))->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
    }
}
