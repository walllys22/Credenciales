<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', fn (Blueprint $table) => $table->decimal('tipo_cambio', 12, 6)->nullable());
        Schema::table('evento_grado', fn (Blueprint $table) => $table->decimal('costo_usd', 12, 2)->nullable());
    }

    public function down(): void
    {
        Schema::table('evento_grado', fn (Blueprint $table) => $table->dropColumn('costo_usd'));
        Schema::table('eventos', fn (Blueprint $table) => $table->dropColumn('tipo_cambio'));
    }
};
