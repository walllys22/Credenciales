<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// La tabla de países solo tiene id y nombre, sin fechas automáticas.
class Pais extends Model
{
    protected $table = 'paises';
    public $timestamps = false;
    protected $fillable = ['nombre'];
    public function personas() { return $this->hasMany(Persona::class); }
    public function getBanderaUrlAttribute(): ?string
    {
        $codigo = match (\Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($this->nombre))) {
            'bolivia' => 'bo', 'argentina' => 'ar', 'chile' => 'cl', 'estados unidos' => 'us',
            default => null,
        };
        return $codigo ? asset('images/banderas/'.$codigo.'.svg') : null;
    }
}
