<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->boolean('pago_total')->default(false);
            $table->decimal('descuento', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('pagos', fn (Blueprint $table) => $table->dropColumn(['pago_total', 'descuento']));
    }
};
