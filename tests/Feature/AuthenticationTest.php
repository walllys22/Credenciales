<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Grado;
use App\Models\Inscripcion;
use App\Models\Persona;
use App\Models\User;
use Database\Seeders\UsuariosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function users(): void
    {
        $this->seed(UsuariosSeeder::class);
    }

    private function registration(): Inscripcion
    {
        $grado = Grado::create(['nombre' => 'Dan']);
        $persona = Persona::create(['primer_nombre' => 'Ana', 'primer_apellido' => 'Perez', 'documento_identidad' => '123', 'sexo' => 'Femenino', 'fecha_nacimiento' => '1995-01-01', 'telefono' => '70012345', 'grado_id' => $grado->id]);
        $evento = Evento::create(['nombre' => 'Seminario', 'fecha_inicio' => '2026-12-02', 'fecha_final' => '2026-12-05', 'lugar' => 'Coliseo', 'ciudad' => 'Trinidad']);

        return Inscripcion::create(['persona_id' => $persona->id, 'evento_id' => $evento->id, 'cargo' => 'Participante']);
    }

    public function test_guests_are_redirected_and_signed_qr_remains_public(): void
    {
        foreach (['/', '/dashboard', '/personas', '/usuarios', '/inscripciones', '/inscripciones/1/certificado'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->get('/login')->assertOk();
        $inscripcion = $this->registration();
        $this->get(URL::signedRoute('credenciales.verificar', $inscripcion))->assertOk();
        $this->get('/credenciales/'.$inscripcion->id.'/verificar')->assertForbidden();
    }

    public function test_seed_is_idempotent_hashes_passwords_and_preserves_changed_password(): void
    {
        $this->users();
        $this->users();
        $this->assertDatabaseCount('users', 2);
        $admin = User::where('username', 'superadmin')->firstOrFail();
        $this->assertTrue(Hash::check('password1234', $admin->password));
        $this->assertTrue(Hash::check('imprime1234', User::where('username', 'impresiones')->firstOrFail()->password));
        $admin->update(['password' => 'changed12345']);
        $this->users();
        $this->assertTrue(Hash::check('changed12345', $admin->fresh()->password));
    }

    public function test_both_logins_and_logout(): void
    {
        $this->users();
        foreach ([['superadmin@credencial.com', 'password1234', '/dashboard'], ['impresiones@credencial.com', 'imprime1234', '/inscripciones']] as [$email, $password, $destination]) {
            $this->post('/login', compact('email', 'password'))->assertRedirect($destination);
            $this->assertAuthenticated();
            $this->get('/login')->assertRedirect($destination);
            $this->post('/logout')->assertRedirect('/login');
            $this->assertGuest();
        }
    }

    public function test_existing_seed_accounts_keep_their_password_when_email_is_updated(): void
    {
        $this->users();
        $admin = User::where('username', 'superadmin')->firstOrFail();
        $admin->update(['email' => 'superadmin@credencial.local', 'password' => 'changed12345']);
        $hash = $admin->password;
        $this->users();
        $this->assertSame('superadmin@credencial.com', $admin->fresh()->email);
        $this->assertSame($hash, $admin->fresh()->password);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_login_design_and_remember_session(): void
    {
        $this->users();
        $this->get('/login')->assertOk()->assertSee('images/Login.png', false)->assertSee('images/Logo.png', false)->assertSee('name="email"', false)->assertSee('id="toggle-password"', false);
        $this->post('/login', ['email' => 'superadmin@credencial.com', 'password' => 'password1234', 'remember' => '1'])->assertRedirect('/dashboard')->assertCookie(\Illuminate\Support\Facades\Auth::guard()->getRecallerName());
        $this->get('/dashboard')->assertOk()->assertSee('id="account-menu"', false)->assertSee('data-bs-toggle="dropdown"', false)->assertSee('Cerrar sesión')->assertSee('images/Logo.png', false);
    }

    public function test_wrong_password_inactive_account_and_rate_limit(): void
    {
        $this->users();
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', ['email' => 'superadmin@credencial.com', 'password' => 'wrong']);
        }
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        User::where('username', 'impresiones')->update(['active' => false]);
        $this->post('/login', ['email' => 'impresiones@credencial.com', 'password' => 'imprime1234'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_operator_only_sees_two_print_actions_and_cannot_access_management(): void
    {
        $this->users();
        $inscripcion = $this->registration();
        $operator = User::where('username', 'impresiones')->firstOrFail();
        $this->actingAs($operator);
        $this->get('/')->assertRedirect('/inscripciones');
        $this->get('/inscripciones')->assertOk()->assertSee(route('inscripciones.credencial', $inscripcion), false)->assertSee(route('inscripciones.certificado', $inscripcion), false)
            ->assertDontSee(route('inscripciones.edit', $inscripcion), false)->assertDontSee(route('inscripciones.pagos.create', $inscripcion), false)->assertDontSee('Nueva inscripción')->assertDontSee('Tarifa vigente')->assertDontSee('Limpiar caché');
        $this->get(route('inscripciones.credencial', $inscripcion))->assertOk();
        $this->get(route('inscripciones.certificado', $inscripcion))->assertOk();
        foreach (['/dashboard', '/personas', '/eventos', '/grados', '/paises', '/usuarios', '/inscripciones/create', '/inscripciones/imprimir', '/inscripciones/'.$inscripcion->id.'/pagos', '/inscripciones/'.$inscripcion->id.'/edit'] as $url) {
            $this->get($url)->assertForbidden();
        }
        foreach (['/usuarios', '/personas', '/inscripciones', '/cache/clear'] as $url) {
            $this->post($url, [])->assertForbidden();
        }
        $this->delete('/inscripciones/'.$inscripcion->id)->assertForbidden();
        $this->put('/usuarios/'.$operator->id, ['role' => 'superadmin'])->assertForbidden();
        $operator->update(['active' => false]);
        $this->get('/inscripciones')->assertForbidden();
    }

    public function test_admin_can_manage_users_but_cannot_remove_own_access(): void
    {
        $this->users();
        $admin = User::where('username', 'superadmin')->firstOrFail();
        $this->actingAs($admin);
        foreach (['/dashboard', '/usuarios', '/usuarios/create', '/usuarios/'.$admin->id.'/edit', '/inscripciones'] as $url) {
            $this->get($url)->assertOk();
        }
        $data = ['name' => 'Operador', 'username' => 'operador', 'email' => 'operador@example.com', 'role' => 'impresiones', 'active' => 1, 'password' => 'another1234', 'password_confirmation' => 'another1234'];
        $this->post('/usuarios', $data)->assertRedirect('/usuarios');
        $operator = User::where('username', 'operador')->firstOrFail();
        $this->assertTrue(Hash::check('another1234', $operator->password));
        $data['active'] = 0;
        unset($data['password'], $data['password_confirmation']);
        $this->put('/usuarios/'.$operator->id, $data)->assertRedirect('/usuarios');
        $this->assertFalse($operator->fresh()->active);
        $data = ['name' => $admin->name, 'username' => $admin->username, 'email' => $admin->email, 'role' => 'impresiones', 'active' => 1];
        $this->put('/usuarios/'.$admin->id, $data)->assertSessionHasErrors('role');
        $this->delete('/usuarios/'.$admin->id)->assertSessionHasErrors('usuario');
        $this->delete('/usuarios/'.$operator->id)->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $operator->id]);
    }
}
