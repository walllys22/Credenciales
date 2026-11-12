<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo con únicamente ID y nombre, incluidos los cuatro países solicitados.
        Schema::create('paises', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
        });
        DB::table('paises')->insert([
            ['nombre' => 'Argentina'], ['nombre' => 'Bolivia'],
            ['nombre' => 'Chile'], ['nombre' => 'Estados Unidos'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('paises');
    }
};
