<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->string('grado_anterior')->nullable();
            $table->unsignedBigInteger('grado_id')->nullable()->change();
        });
        // En una instalación vacía el catálogo se carga mediante DatabaseSeeder.
        if (\App\Models\Grado::exists()) \App\Support\CatalogoGrados::reemplazar();
    }

    public function down(): void
    {
        // Reconstruye las referencias generales antes de restaurar la restricción.
        foreach (\Illuminate\Support\Facades\DB::table('personas')->whereNull('grado_id')->get() as $persona) {
            $grado = \App\Models\Grado::firstOrCreate(['nombre' => $persona->grado_anterior ?? 'Sin grado']);
            \Illuminate\Support\Facades\DB::table('personas')->where('id', $persona->id)->update(['grado_id' => $grado->id]);
        }
        Schema::table('personas', function (Blueprint $table) {
            $table->unsignedBigInteger('grado_id')->nullable(false)->change();
            $table->dropColumn('grado_anterior');
        });
    }
};
