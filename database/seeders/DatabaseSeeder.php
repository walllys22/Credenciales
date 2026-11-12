<?php

namespace Database\Seeders;

use App\Models\Grado;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UsuariosSeeder::class);
        Grado::firstOrCreate(['nombre' => 'Sin grado']);
    }
}
