<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Evento extends Model
{
    protected $fillable = ['nombre', 'fecha_inicio', 'fecha_final', 'lugar', 'ciudad', 'imagen', 'tipo_cambio'];
    protected function casts(): array { return ['fecha_inicio' => 'date', 'fecha_final' => 'date', 'tipo_cambio' => 'decimal:2']; }
    public function grados() { return $this->belongsToMany(Grado::class)->withPivot('costo', 'costo_usd'); }
    public function inscripciones() { return $this->hasMany(Inscripcion::class); }
    public function getCostosAgrupadosAttribute(): \Illuminate\Support\Collection
    {
        $costos = $this->grados->map(fn ($grado) => [
            'nombre' => $grado->categoria() ?? $grado->nombre,
            'usd' => $grado->pivot->costo_usd,
            'bs' => $grado->pivot->costo,
        ])->unique(fn ($costo) => json_encode($costo))
            ->sortBy(fn ($costo) => match ($costo['nombre']) { 'Kyu' => 0, 'Dan' => 1, default => 2 })->values();
        $costos->push([
            'nombre' => 'Jefes de Dojo',
            'usd' => '100.00',
            'bs' => $this->tipo_cambio ? (string) \Brick\Math\BigDecimal::of('100')->multipliedBy($this->tipo_cambio)->toScale(2, \Brick\Math\RoundingMode::HalfUp) : null,
        ]);
        return $costos;
    }
    public function getImagenUrlAttribute(): string
    {
        return $this->imagen ? route('eventos.imagen', $this) : asset('images/evento-default.svg');
    }
}
