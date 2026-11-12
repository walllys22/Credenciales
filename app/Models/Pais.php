<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// La tabla de países solo tiene id y nombre, sin fechas automáticas.
class Pais extends Model
{
    protected $table = 'paises';
    public $timestamps = false;
    protected $fillable = ['nombre'];
}
