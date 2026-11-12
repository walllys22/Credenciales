<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Grado extends Model
{
    protected $fillable = ['nombre'];
    public function personas() { return $this->hasMany(Persona::class); }
    public function eventos() { return $this->belongsToMany(Evento::class)->withPivot('costo', 'costo_usd'); }
}
