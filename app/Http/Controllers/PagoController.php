<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\Pago;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PagoController extends Controller
{
    public function create(Inscripcion $inscripcion)
    {
        $inscripcion->load(['persona.grado', 'evento.grados']);
        $tarifa = BigDecimal::of($inscripcion->tarifa())->toScale(2);
        $totalPagado = BigDecimal::of((string) $inscripcion->pagos()->sum('monto'))->toScale(2, RoundingMode::HalfUp);
        $totalDescuento = BigDecimal::of((string) $inscripcion->pagos()->sum('descuento'))->toScale(2, RoundingMode::HalfUp);
        $saldo = $tarifa->minus($totalPagado)->minus($totalDescuento);
        $pagos = $inscripcion->pagos()->select('pagos.*')
            ->selectSub(DB::table('pagos as anteriores')->selectRaw('SUM(anteriores.monto + anteriores.descuento)')
                ->whereColumn('anteriores.inscripcion_id', 'pagos.inscripcion_id')
                ->where(fn ($query) => $query->whereColumn('anteriores.fecha', '<', 'pagos.fecha')
                    ->orWhere(fn ($query) => $query->whereColumn('anteriores.fecha', 'pagos.fecha')->whereColumn('anteriores.id', '<=', 'pagos.id'))), 'acumulado')
            ->orderByDesc('fecha')->orderByDesc('id')->paginate(10);
        $pagos->getCollection()->each(function ($pago) use ($tarifa) {
            $restante = $tarifa->minus(BigDecimal::of((string) $pago->acumulado)->toScale(2, RoundingMode::HalfUp));
            $pago->saldo_pendiente = $restante->isNegative() ? '0.00' : (string) $restante;
        });

        return view('inscripciones.pagos', [
            'inscripcion' => $inscripcion,
            'pagos' => $pagos,
            'pagoEliminableId' => $inscripcion->pagos()->orderByDesc('fecha')->orderByDesc('id')->value('id'),
            'tarifa' => (string) $tarifa,
            'totalPagado' => (string) $totalPagado,
            'totalDescuento' => (string) $totalDescuento,
            'saldoPendiente' => $saldo->isNegative() ? '0.00' : (string) $saldo,
            'saldoAFavor' => $saldo->isNegative() ? (string) $saldo->negated() : '0.00',
        ]);
    }

    public function destroy(Inscripcion $inscripcion, Pago $pago)
    {
        abort_unless($pago->inscripcion_id === $inscripcion->id, 404);
        $ultimoPagoId = $inscripcion->pagos()->orderByDesc('fecha')->orderByDesc('id')->value('id');
        if ($pago->id !== $ultimoPagoId) {
            return to_route('inscripciones.pagos.create', $inscripcion)->with('error', 'Solo se puede eliminar el pago más reciente.');
        }
        $pago->delete();

        return to_route('inscripciones.pagos.create', $inscripcion)->with('success', 'Pago eliminado. El saldo pendiente se ha actualizado.');
    }

    public function store(Request $request, Inscripcion $inscripcion)
    {
        $data = $request->validate([
            'monto' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'observacion' => ['nullable', 'string', 'max:500'],
            'moneda' => ['sometimes', 'required', 'in:BOB,USD'],
            'pago_total' => ['sometimes', 'boolean'],
        ]);

        $data['moneda'] = $data['moneda'] ?? 'BOB';
        $data['monto_original'] = $data['monto'];
        if ($data['moneda'] === 'USD') {
            $tipoCambio = $inscripcion->evento->tipo_cambio;
            if (!$tipoCambio || $tipoCambio <= 0) {
                throw ValidationException::withMessages(['moneda' => 'Configura el tipo de cambio del evento para registrar pagos en dólares.']);
            }
            $montoBs = BigDecimal::of((string) $data['monto'])->multipliedBy((string) $tipoCambio)->toScale(2, RoundingMode::HalfUp);
            if ($montoBs->isGreaterThan('9999999999.99') || $montoBs->isLessThan('0.01')) {
                throw ValidationException::withMessages(['monto' => 'El monto convertido a bolivianos debe estar entre 0,01 y 9.999.999.999,99.']);
            }
            $data['tipo_cambio'] = $tipoCambio;
            $data['monto'] = (string) $montoBs;
        }

        $data['pago_total'] = $request->boolean('pago_total');
        DB::transaction(function () use ($inscripcion, $data) {
            $inscripcion = Inscripcion::whereKey($inscripcion->id)->lockForUpdate()->firstOrFail();
            if ($inscripcion->invitado_sin_costo) {
                throw ValidationException::withMessages(['monto' => 'Este participante es invitado sin costo. No corresponde registrar cobros.']);
            }
            $data['descuento'] = '0.00';
            if ($data['pago_total']) {
                $inscripcion->load(['persona', 'evento.grados']);
                $tarifa = $inscripcion->tarifa();
                $pagado = BigDecimal::of((string) $inscripcion->pagos()->sum('monto'))->toScale(2, RoundingMode::HalfUp);
                $descontado = BigDecimal::of((string) $inscripcion->pagos()->sum('descuento'))->toScale(2, RoundingMode::HalfUp);
                $diferencia = BigDecimal::of((string) $tarifa)->minus($pagado)->minus($descontado)->minus($data['monto'])->toScale(2);
                $data['descuento'] = $diferencia->isNegative() ? '0.00' : (string) $diferencia;
            }
            $inscripcion->pagos()->create($data);
        });

        return to_route('inscripciones.pagos.create', $inscripcion)->with('success', 'Pago registrado correctamente.');
    }
}
