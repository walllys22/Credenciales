<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
class Inscripcion extends Model
{
    protected $table = 'inscripciones';
    protected $fillable = ['evento_id', 'persona_id', 'cargo', 'jefe_dojo', 'invitado_sin_costo', 'observaciones'];
    protected function casts(): array { return ['jefe_dojo' => 'boolean', 'invitado_sin_costo' => 'boolean']; }
    public function tarifa(): string
    {
        if ($this->invitado_sin_costo) {
            return '0.00';
        }
        if ($this->jefe_dojo && $this->persona->grado->esDan()) {
            return '100.00';
        }
        if (in_array($this->persona->grado->nombre, Grado::DETALLADOS, true) || $this->persona->grado_anterior) {
            return (string) BigDecimal::of($this->persona->grado->costoBaseUsd() ?? '0')->toScale(2, RoundingMode::HalfUp);
        }
        $tarifa = $this->evento->grados->firstWhere('id', $this->persona->grado_id)?->pivot;
        if ($tarifa && $tarifa->costo_usd === null && BigDecimal::of((string) $tarifa->costo)->isPositive()) {
            if (!$this->evento->tipo_cambio || $this->evento->tipo_cambio <= 0) throw new \RuntimeException('Falta el tipo de cambio para convertir la tarifa histórica en bolivianos.');
            return (string) BigDecimal::of((string) $tarifa->costo)->dividedBy((string) $this->evento->tipo_cambio, 2, RoundingMode::HalfUp);
        }
        return (string) BigDecimal::of((string) ($tarifa?->costo_usd ?? '0.00'))->toScale(2, RoundingMode::HalfUp);
    }
    public function totalesPagos(): array
    {
        $pagado = BigDecimal::zero(); $descuento = BigDecimal::zero();
        foreach ($this->pagos as $pago) {
            $pagado = $pagado->plus($pago->importeUsd($this->evento->tipo_cambio));
            $descuento = $descuento->plus($pago->descuentoUsd($this->evento->tipo_cambio));
        }
        return [$pagado, $descuento];
    }
    public function evento() { return $this->belongsTo(Evento::class); }
    public function persona() { return $this->belongsTo(Persona::class); }
    public function pagos() { return $this->hasMany(Pago::class); }
}
