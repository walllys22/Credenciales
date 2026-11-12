<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grados', function (Blueprint $t) {
            $t->id(); $t->string('nombre')->unique(); $t->timestamps();
        });
        Schema::create('eventos', function (Blueprint $t) {
            $t->id(); $t->string('nombre'); $t->date('fecha_inicio'); $t->date('fecha_final');
            $t->string('lugar'); $t->string('ciudad'); $t->timestamps();
        });
        Schema::create('evento_grado', function (Blueprint $t) {
            $t->id();
            $t->foreignId('evento_id')->constrained('eventos')->cascadeOnDelete();
            $t->foreignId('grado_id')->constrained('grados')->restrictOnDelete();
            $t->decimal('costo', 12, 2); $t->unique(['evento_id', 'grado_id']);
        });
        Schema::create('personas', function (Blueprint $t) {
            $t->id(); $t->string('primer_nombre', 100); $t->string('segundo_nombre', 100)->nullable();
            $t->string('primer_apellido', 100); $t->string('segundo_apellido', 100)->nullable();
            $t->string('documento_identidad', 50)->unique(); $t->string('sexo', 20);
            $t->date('fecha_nacimiento'); $t->string('telefono', 30);
            $t->foreignId('grado_id')->constrained('grados')->restrictOnDelete(); $t->timestamps();
        });
        Schema::create('inscripciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('evento_id')->constrained('eventos')->restrictOnDelete();
            $t->foreignId('persona_id')->constrained('personas')->restrictOnDelete();
            $t->string('cargo', 120); $t->timestamps(); $t->unique(['evento_id', 'persona_id']);
        });
    }
    public function down(): void
    {
        foreach (['inscripciones', 'personas', 'evento_grado', 'eventos', 'grados'] as $table) Schema::dropIfExists($table);
    }
};
