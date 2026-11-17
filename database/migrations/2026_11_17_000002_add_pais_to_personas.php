<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personas', fn (Blueprint $table) => $table->foreignId('pais_id')->nullable()->constrained('paises')->restrictOnDelete());
        $this->asignarBolivia();
    }

    public function asignarBolivia(): void
    {
        // El usuario confirmó que todos los participantes actuales son de Bolivia.
        $boliviaId = DB::table('paises')->where('nombre', 'Bolivia')->value('id');
        if (!$boliviaId) $boliviaId = DB::table('paises')->insertGetId(['nombre' => 'Bolivia']);
        DB::table('personas')->whereNull('pais_id')->update(['pais_id' => $boliviaId]);
    }

    public function down(): void
    {
        Schema::table('personas', fn (Blueprint $table) => $table->dropConstrainedForeignId('pais_id'));
    }
};
