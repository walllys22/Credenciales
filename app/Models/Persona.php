<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Persona extends Model
{
    protected $fillable = ['primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'documento_identidad', 'sexo', 'telefono', 'grado_id', 'foto', 'pais_id', 'organizacion'];
    public function grado() { return $this->belongsTo(Grado::class)->withDefault(function ($grado, $persona) { $grado->nombre = $persona->grado_anterior ?? 'Sin grado'; }); }
    public function pais() { return $this->belongsTo(Pais::class); }
    public function inscripciones() { return $this->hasMany(Inscripcion::class); }
    public function getFotoUrlAttribute(): string
    {
        return $this->foto ? route('personas.foto', $this) : asset('images/persona-default.svg');
    }
    public function getNombreCompletoAttribute(): string
    {
        return implode(' ', array_filter([$this->primer_nombre, $this->segundo_nombre, $this->primer_apellido, $this->segundo_apellido]));
    }
}
