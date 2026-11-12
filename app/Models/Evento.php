<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Evento extends Model
{
    protected $fillable = ['nombre', 'fecha_inicio', 'fecha_final', 'lugar', 'ciudad', 'imagen', 'tipo_cambio'];
    protected function casts(): array { return ['fecha_inicio' => 'date', 'fecha_final' => 'date', 'tipo_cambio' => 'decimal:2']; }
    public function grados() { return $this->belongsToMany(Grado::class)->withPivot('costo', 'costo_usd'); }
    public function inscripciones() { return $this->hasMany(Inscripcion::class); }
    public function getImagenUrlAttribute(): string
    {
        return $this->imagen ? route('eventos.imagen', $this) : asset('images/evento-default.svg');
    }
}
