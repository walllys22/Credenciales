            <tbody class="report-payment-totals">
                <tr><th colspan="5" scope="row">Pagado Bs.-</th><td colspan="2">{{ number_format($totalPagadoBs, 2, '.', ',') }}</td></tr>
                <tr><th colspan="5" scope="row">Pagado $us.-</th><td colspan="2">{{ number_format($totalPagadoUsd, 2, '.', ',') }}</td></tr>
                <tr class="report-debt-summary-row"><td colspan="7">
                    <table class="report-debt-summary" aria-label="Resumen de deuda">
                        <tbody>
                            <tr><th scope="row">Participantes con deuda</th><td>{{ $participantesConDeuda }}</td></tr>
                            <tr><th scope="row">Deuda pendiente Bs.-</th><td>{{ $deudaTotalBs !== null ? number_format($deudaTotalBs, 2, '.', ',') : 'Sin tipo de cambio' }}</td></tr>
                            <tr><th scope="row">Deuda pendiente $us.-</th><td>{{ $deudaTotalUsd !== null ? number_format($deudaTotalUsd, 2, '.', ',') : 'Sin tipo de cambio' }}</td></tr>
                            <tr><th scope="row">Tipo de cambio</th><td>@forelse($tiposCambioReporte as $eventoCambio)<div>@if($tiposCambioReporte->count() > 1){{ $eventoCambio->nombre }}: @endif{{ $eventoCambio->tipo_cambio ? 'Bs '.number_format($eventoCambio->tipo_cambio, 2, ',', '.').' por 1 $us' : 'Sin registrar' }}</div>@empty Sin registrar @endforelse</td></tr>
                        </tbody>
                    </table>
                </td></tr>
            </tbody>
