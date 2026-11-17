<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->boolean('invitado_sin_costo')->default(false);
            $table->string('observaciones', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', fn (Blueprint $table) => $table->dropColumn(['invitado_sin_costo', 'observaciones']));
    }
};
