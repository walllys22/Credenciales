<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['superadmin', 'password1234', 'superadmin'], ['impresiones', 'imprime1234', 'impresiones']] as [$username, $password, $role]) {
            User::firstOrCreate(['username' => $username], [
                'name' => $username, 'email' => $username.'@credencial.local',
                'password' => $password, 'role' => $role, 'active' => true,
            ]);
        }
    }
}
