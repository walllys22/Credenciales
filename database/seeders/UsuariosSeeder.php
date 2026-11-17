<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            ['username' => 'superadmin', 'email' => 'superadmin@credencial.com', 'password' => 'password1234', 'role' => 'superadmin'],
            ['username' => 'impresiones', 'email' => 'impresiones@credencial.com', 'password' => 'imprime1234', 'role' => 'impresiones'],
        ];

        foreach ($usuarios as $datos) {
            ['username' => $username, 'email' => $email, 'password' => $password, 'role' => $role] = $datos;
            $user = User::firstOrCreate(['username' => $username], [
                'name' => $username, 'email' => $email,
                'password' => $password, 'role' => $role, 'active' => true,
            ]);
            if ($user->email === $username.'@credencial.local') {
                $user->update(['email' => $email]);
            }
        }
    }
}
