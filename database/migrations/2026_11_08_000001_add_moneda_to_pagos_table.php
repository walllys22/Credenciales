<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->string('moneda', 3)->default('BOB');
            $table->decimal('monto_original', 12, 2)->nullable();
            $table->decimal('tipo_cambio', 12, 6)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pagos', fn (Blueprint $table) => $table->dropColumn(['moneda', 'monto_original', 'tipo_cambio']));
    }
};
