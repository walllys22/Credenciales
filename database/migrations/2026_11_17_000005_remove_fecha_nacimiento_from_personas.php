<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personas', fn (Blueprint $table) => $table->dropColumn('fecha_nacimiento'));
    }

    public function down(): void
    {
        // La reversión repone la columna vacía, sin recuperar valores eliminados.
        Schema::table('personas', fn (Blueprint $table) => $table->date('fecha_nacimiento')->nullable());
    }
};
