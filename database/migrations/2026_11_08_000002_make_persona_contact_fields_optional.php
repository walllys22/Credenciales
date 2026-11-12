<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->date('fecha_nacimiento')->nullable()->change();
            $table->string('telefono', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Los registros sin estos datos deben completarse antes de revertir.
        Schema::table('personas', function (Blueprint $table) {
            $table->date('fecha_nacimiento')->nullable(false)->change();
            $table->string('telefono', 30)->nullable(false)->change();
        });
    }
};
