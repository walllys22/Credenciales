<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inscripciones', fn (Blueprint $table) => $table->boolean('jefe_dojo')->default(false));
    }

    public function down(): void
    {
        Schema::table('inscripciones', fn (Blueprint $table) => $table->dropColumn('jefe_dojo'));
    }
};
