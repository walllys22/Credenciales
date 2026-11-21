<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('pagos', fn (Blueprint $table) => $table->decimal('descuento_usd', 18, 8)->nullable()); }
    public function down(): void { Schema::table('pagos', fn (Blueprint $table) => $table->dropColumn('descuento_usd')); }
};
