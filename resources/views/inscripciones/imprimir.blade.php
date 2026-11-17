@extends('layouts.app')
{{-- Listado completo sin paginación para revisar e imprimir todas las personas inscritas. --}}
@section('section', 'Inscripción / Imprimir')
@section('title', 'Personas inscritas')
@section('actions')
    <button type="button" class="btn btn-create btn-project" id="print-registrations" data-action-style="print" aria-label="Imprimir" title="Imprimir"><i class="fa-solid fa-print" aria-hidden="true"></i><span class="button-label">Imprimir</span></button>
    <a href="{{ route('inscripciones.index') }}" class="btn btn-close-page btn-project" data-action-style="close" aria-label="Cerrar" title="Cerrar"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="button-label">Cerrar</span></a>
@endsection
@section('content')
<section class="panel registrations-report" id="report-source">
    <div class="report-letterhead-footer"><div>SEMINARIO INTERNACIONAL DE KARATE <span>&amp;</span> KOBUDO</div><div class="report-footer-country">BOLIVIA 2026</div></div>
    <div class="table-responsive">
        <table class="table align-middle">
            <colgroup><col class="report-col-number"><col class="report-col-name"><col class="report-col-document"><col class="report-col-grade"><col class="report-col-money"><col class="report-col-money"><col class="report-col-money"></colgroup>
            <thead>
                <tr class="report-print-heading"><th colspan="7"><img class="report-letterhead" src="{{ asset('images/Cabecera.png') }}?v={{ hash_file('sha256', public_path('images/Cabecera.png')) }}" alt="" aria-hidden="true"><h2>Listado de Inscritos</h2></th></tr>
                <tr><th class="report-number">N.º</th><th>Nombre completo</th><th>Documento</th><th>Grado</th><th>Costo</th><th>Pagado</th><th>Saldo</th></tr>
            </thead>
            <tbody class="report-participants">
                @forelse($inscripciones as $inscripcion)
                    <tr @class(['report-guest' => $inscripcion->invitado_sin_costo, 'report-paid' => !$inscripcion->invitado_sin_costo && $inscripcion->total_pagado > 0 && $inscripcion->saldo_pendiente == 0])>
                        <td class="report-number">{{ $loop->iteration }}</td>
                        <td><div class="report-participant-name">{{ $inscripcion->persona->nombre_completo }}</div><small class="d-block report-participant-organization">{{ $inscripcion->persona->organizacion ?: 'Sin organización registrada' }}</small></td>
                        <td>{{ $inscripcion->persona->documento_identidad }}</td>
                        <td>{{ $inscripcion->persona->grado->nombre }}</td>
                        @if($inscripcion->invitado_sin_costo)
                        <td colspan="3" class="text-center">Sin costo</td>
                        @else
                        <td class="text-nowrap">Bs {{ number_format($inscripcion->tarifa_vigente, 2, ',', '.') }}</td>
                        <td class="text-nowrap">
                            @if($inscripcion->pagado_bs > 0 || !($inscripcion->pagado_usd > 0))
                                <div>Bs {{ number_format($inscripcion->pagado_bs ?? 0, 2, ',', '.') }}</div>
                            @endif
                            @if($inscripcion->pagado_usd > 0)
                                <div>$us {{ number_format($inscripcion->pagado_usd, 2, ',', '.') }}</div>
                            @endif
                        </td>
                        <td class="text-nowrap">Bs {{ number_format($inscripcion->saldo_pendiente, 2, ',', '.') }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7">No hay personas inscritas.</td></tr>
                @endforelse
            </tbody>
            <tbody class="report-payment-totals">
                <tr><th colspan="5" scope="row">Pagado Bs.-</th><td colspan="2">{{ number_format($totalPagadoBs, 2, '.', ',') }}</td></tr>
                <tr><th colspan="5" scope="row">Pagado $us.-</th><td colspan="2">{{ number_format($totalPagadoUsd, 2, '.', ',') }}</td></tr>
                <tr class="report-debt-summary-row"><td colspan="7">
                    <table class="report-debt-summary" aria-label="Resumen de deuda">
                        <tbody>
                            <tr><th scope="row">Participantes con deuda</th><td>{{ $participantesConDeuda }}</td></tr>
                            <tr><th scope="row">Deuda pendiente Bs.-</th><td>{{ number_format($deudaTotalBs, 2, '.', ',') }}</td></tr>
                            <tr><th scope="row">Deuda pendiente $us.-</th><td>{{ $deudaTotalUsd !== null ? number_format($deudaTotalUsd, 2, '.', ',') : 'Sin tipo de cambio' }}</td></tr>
                            <tr><th scope="row">Tipo de cambio</th><td>@forelse($tiposCambioReporte as $eventoCambio)<div>@if($tiposCambioReporte->count() > 1){{ $eventoCambio->nombre }}: @endif{{ $eventoCambio->tipo_cambio ? 'Bs '.number_format($eventoCambio->tipo_cambio, 2, ',', '.').' por 1 $us' : 'Sin registrar' }}</div>@empty Sin registrar @endforelse</td></tr>
                        </tbody>
                    </table>
                </td></tr>
            </tbody>
            <tfoot class="report-print-footer"><tr><td colspan="7"></td></tr></tfoot>
        </table>
    </div>
</section>
<div id="report-preview-pages" class="report-preview-pages" hidden></div>
<nav id="report-page-navigation" class="report-page-navigation" aria-label="Páginas del reporte" hidden>
    <button type="button" id="report-previous-page" class="btn btn-project" data-action-style="back" title="Página anterior" aria-label="Página anterior"><i class="fa-solid fa-chevron-up" aria-hidden="true"></i><span class="button-label">Página anterior</span></button>
    <span id="report-page-status" role="status" aria-live="polite"></span>
    <button type="button" id="report-next-page" class="btn btn-project" data-action-style="manage" title="Página siguiente" aria-label="Página siguiente"><i class="fa-solid fa-chevron-down" aria-hidden="true"></i><span class="button-label">Página siguiente</span></button>
</nav>
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/inscripciones-imprimir.css') }}?v={{ filemtime(public_path('css/inscripciones-imprimir.css')) }}">
@endpush
@push('scripts')
<script src="{{ asset('js/reporte-paginas.js') }}?v={{ filemtime(public_path('js/reporte-paginas.js')) }}" defer></script>
<script>
    // Abre la impresión después de revisar el listado completo.
    document.getElementById('print-registrations').addEventListener('click', async () => {
        const button = document.getElementById('print-registrations');
        const letterhead = document.querySelector('#report-source .report-letterhead');
        button.disabled = true;
        try {
            await letterhead.decode();
            await document.fonts?.ready;
            window.prepararHojasReporte?.();
            window.print();
        } catch (error) {
            window.showAppMessage('No se pudo cargar la cabecera. Recarga la página antes de imprimir.');
        } finally {
            button.disabled = false;
        }
    });
</script>
@endpush
