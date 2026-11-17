<?php

namespace App\Support;

use App\Models\{Evento, Grado};
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

class CatalogoGrados
{
    public static function reemplazar(): void
    {
        DB::transaction(function () {
            // La denominación anterior se conserva sin inventar un número.
            $anteriores = Grado::whereNotIn('nombre', Grado::DETALLADOS)->get();
            foreach ($anteriores as $grado) {
                DB::table('personas')->where('grado_id', $grado->id)->update(['grado_anterior' => $grado->nombre, 'grado_id' => null]);
                DB::table('evento_grado')->where('grado_id', $grado->id)->delete();
                $grado->delete();
            }
            foreach (Grado::DETALLADOS as $nombre) Grado::firstOrCreate(['nombre' => $nombre]);
            foreach (Evento::all() as $evento) {
                if (!$evento->tipo_cambio) continue;
                foreach (Grado::whereIn('nombre', Grado::DETALLADOS)->get() as $grado) {
                    $usd = $grado->costoBaseUsd();
                    $bs = (string) BigDecimal::of($usd)->multipliedBy($evento->tipo_cambio)->toScale(2, RoundingMode::HalfUp);
                    $evento->grados()->syncWithoutDetaching([$grado->id => ['costo_usd' => $usd, 'costo' => $bs]]);
                }
            }
        });
    }
}
