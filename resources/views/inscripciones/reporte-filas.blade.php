                @forelse($inscripciones as $inscripcion)
                    <tr @class(['report-guest' => $inscripcion->invitado_sin_costo, 'report-paid' => !$inscripcion->invitado_sin_costo && $inscripcion->total_pagado > 0 && $inscripcion->saldo_pendiente == 0])>
                        <td class="report-number">{{ ($numeroInicial ?? 0) + $loop->iteration }}</td>
                        <td><div class="report-participant-name">{{ $inscripcion->persona->nombre_completo }}</div><small class="d-block report-participant-organization">{{ $inscripcion->persona->organizacion ?: 'Sin organización registrada' }}</small></td>
                        <td>{{ $inscripcion->persona->documento_identidad }}</td>
                        <td>{{ $inscripcion->persona->grado->nombre }}</td>
                        @if($inscripcion->invitado_sin_costo)
                        <td colspan="3" class="text-center">Sin costo</td>
                        @else
                        <td class="text-nowrap">$us {{ number_format($inscripcion->tarifa_vigente, 2, ',', '.') }}</td>
                        <td class="text-nowrap">
                            @if($inscripcion->pagado_bs > 0 || !($inscripcion->pagado_usd > 0))
                                <div>Bs {{ number_format($inscripcion->pagado_bs ?? 0, 2, ',', '.') }}</div>
                            @endif
                            @if($inscripcion->pagado_usd > 0)
                                <div>$us {{ number_format($inscripcion->pagado_usd, 2, ',', '.') }}</div>
                            @endif
                        </td>
                        <td class="text-nowrap">$us {{ number_format($inscripcion->saldo_pendiente, 2, ',', '.') }}@if($inscripcion->evento->tipo_cambio)<br>Bs {{ number_format($inscripcion->saldo_pendiente * $inscripcion->evento->tipo_cambio, 2, ',', '.') }}@endif</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7">No hay personas inscritas.</td></tr>
                @endforelse
