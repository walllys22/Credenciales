<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $fillable = ['monto', 'fecha', 'observacion', 'moneda', 'monto_original', 'tipo_cambio', 'pago_total', 'descuento'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'fecha' => 'date', 'monto_original' => 'decimal:2', 'tipo_cambio' => 'decimal:6', 'pago_total' => 'boolean', 'descuento' => 'decimal:2'];
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class);
    }
}
