<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personas', fn (Blueprint $table) => $table->string('organizacion', 255)->nullable());
        $this->asignarOrganizacionInicial();
    }

    public function asignarOrganizacionInicial(): void
    {
        // Organización de los participantes anteriores, confirmada por el usuario.
        DB::table('personas')->whereNull('organizacion')->update(['organizacion' => 'Shorin Ryu Shorinkan Bolivia']);
    }

    public function down(): void
    {
        Schema::table('personas', fn (Blueprint $table) => $table->dropColumn('organizacion'));
    }
};
