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
        $inscripcion->load(['persona.grado', 'evento.grados', 'pagos']);
        $tarifa = BigDecimal::of($inscripcion->tarifa())->toScale(2);
        [$totalPagado, $totalDescuento] = $inscripcion->totalesPagos();
        $saldo = $tarifa->minus($totalPagado)->minus($totalDescuento);
        $acumulado = BigDecimal::zero(); $saldos = [];
        foreach ($inscripcion->pagos->sortBy([['fecha', 'asc'], ['id', 'asc']]) as $pago) {
            $acumulado = $acumulado->plus($pago->importeUsd($inscripcion->evento->tipo_cambio))->plus($pago->descuentoUsd($inscripcion->evento->tipo_cambio));
            $restante = $tarifa->minus($acumulado);
            $saldos[$pago->id] = $restante->isNegative() ? '0.00' : (string) $restante;
        }
        $pagos = $inscripcion->pagos()->orderByDesc('fecha')->orderByDesc('id')->paginate(10);
        $pagos->getCollection()->each(function ($pago) use ($saldos, $inscripcion) {
            $pago->saldo_pendiente = $saldos[$pago->id];
            $pago->importe_usd = (string) $pago->importeUsd($inscripcion->evento->tipo_cambio);
            $pago->descuento_calculado_usd = (string) $pago->descuentoUsd($inscripcion->evento->tipo_cambio);
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
        if ($data['moneda'] === 'BOB') {
            $tipoCambio = $inscripcion->evento->tipo_cambio;
            if (!$tipoCambio || $tipoCambio <= 0) {
                throw ValidationException::withMessages(['moneda' => 'Configura el tipo de cambio del evento para registrar pagos en bolivianos.']);
            }
            if (BigDecimal::of((string) $data['monto'])->dividedBy((string) $tipoCambio, 2, RoundingMode::HalfUp)->isLessThan('0.01')) {
                throw ValidationException::withMessages(['monto' => 'El monto convertido debe ser al menos USD 0,01.']);
            }
            $data['tipo_cambio'] = $tipoCambio;
        }

        $data['pago_total'] = $request->boolean('pago_total');
        DB::transaction(function () use ($inscripcion, $data) {
            $inscripcion = Inscripcion::whereKey($inscripcion->id)->lockForUpdate()->firstOrFail();
            if ($inscripcion->invitado_sin_costo) {
                throw ValidationException::withMessages(['monto' => 'Este participante es invitado sin costo. No corresponde registrar cobros.']);
            }
            $data['descuento'] = '0.00';
            $data['descuento_usd'] = '0.00';
            if ($data['pago_total']) {
                $inscripcion->load(['persona.grado', 'evento.grados', 'pagos']);
                $tarifa = $inscripcion->tarifa();
                [$pagado, $descontado] = $inscripcion->totalesPagos();
                $nuevo = new Pago($data);
                $diferencia = BigDecimal::of((string) $tarifa)->minus($pagado)->minus($descontado)->minus($nuevo->importeUsd($inscripcion->evento->tipo_cambio));
                $data['descuento_usd'] = $diferencia->isNegative() ? '0.00' : (string) $diferencia;
            }
            $inscripcion->pagos()->create($data);
        });

        return to_route('inscripciones.pagos.create', $inscripcion)->with('success', 'Pago registrado correctamente.');
    }
}
