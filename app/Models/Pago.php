<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $fillable = ['monto', 'fecha', 'observacion', 'moneda', 'monto_original', 'tipo_cambio', 'pago_total', 'descuento', 'descuento_usd'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'fecha' => 'date', 'monto_original' => 'decimal:2', 'tipo_cambio' => 'decimal:6', 'pago_total' => 'boolean', 'descuento' => 'decimal:2'];
    }

    public function importeUsd($tipoEvento): \Brick\Math\BigDecimal
    {
        if ($this->moneda === 'USD') return \Brick\Math\BigDecimal::of($this->monto_original ?? $this->monto);
        return $this->convertirBs($this->monto, $tipoEvento);
    }

    public function descuentoUsd($tipoEvento): \Brick\Math\BigDecimal
    {
        if ($this->descuento_usd !== null) return \Brick\Math\BigDecimal::of($this->descuento_usd);
        return $this->convertirBs($this->descuento ?? '0', $tipoEvento);
    }

    private function convertirBs($monto, $tipoEvento): \Brick\Math\BigDecimal
    {
        $importe = \Brick\Math\BigDecimal::of($monto);
        if ($importe->isZero()) return $importe->toScale(2);
        $tipo = $this->tipo_cambio ?: $tipoEvento;
        if (!$tipo || $tipo <= 0) throw new \RuntimeException('Falta el tipo de cambio para convertir un importe en bolivianos.');
        return $importe->dividedBy((string) $tipo, 8, \Brick\Math\RoundingMode::HalfUp);
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class);
    }
}
