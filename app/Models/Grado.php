<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Grado extends Model
{
    public const DETALLADOS = ['10mo Kyu', '9no Kyu', '7mo Kyu', '6to Kyu', '5to Kyu', '4to Kyu', '3er Kyu', '2do Kyu', '1er Kyu', '1er Dan', '2do Dan', '3er Dan', '4to Dan', '5to Dan', '7mo Dan', '8vo Dan', '9no Dan', '10mo Dan'];
    public function esDan(): bool { return preg_match('/\bDAN$/u', mb_strtoupper(trim($this->nombre))) === 1; }
    public function categoria(): ?string
    {
        if ($this->esDan()) return 'Dan';
        return preg_match('/\bKYU$/u', mb_strtoupper(trim($this->nombre))) === 1 ? 'Kyu' : null;
    }
    public function costoBaseUsd(): ?string
    {
        return match ($this->categoria()) { 'Dan' => '50.00', 'Kyu' => '30.00', default => null };
    }
    protected $fillable = ['nombre'];
    public function personas() { return $this->hasMany(Persona::class); }
    public function eventos() { return $this->belongsToMany(Evento::class)->withPivot('costo', 'costo_usd'); }
}
