@extends('layouts.app')
{{-- Listado completo sin paginación para revisar e imprimir todas las personas inscritas. --}}
@section('section', 'Inscripción / Imprimir')
@section('title', 'Personas inscritas')
@section('actions')
    <button type="button" class="btn btn-create" id="print-registrations"><img src="{{ asset('images/Impresora.png') }}" width="22" height="22" alt=""> Imprimir</button>
    <a href="{{ route('inscripciones.index') }}" class="btn btn-close-page">Cerrar</a>
@endsection
@section('content')
<section class="panel registrations-report">
    <img class="report-letterhead" src="{{ asset('images/Membrete.png') }}" alt="" aria-hidden="true">
    <div class="table-responsive">
        <table class="table align-middle">
            <colgroup><col class="report-col-number"><col class="report-col-name"><col class="report-col-document"><col class="report-col-grade"><col class="report-col-role"><col class="report-col-money"><col class="report-col-money"><col class="report-col-money"></colgroup>
            <thead>
                <tr class="report-print-heading"><th colspan="8"><h2>Listado de Inscritos</h2></th></tr>
                <tr><th>N.º</th><th>Nombre completo</th><th>Documento</th><th>Grado</th><th>Cargo</th><th>Tarifa</th><th>Pagado</th><th>Saldo</th></tr>
            </thead>
            <tbody>
                @forelse($inscripciones as $inscripcion)
                    <tr @class(['report-paid' => $inscripcion->total_pagado > 0 && $inscripcion->saldo_pendiente == 0])>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $inscripcion->persona->nombre_completo }}</td>
                        <td>{{ $inscripcion->persona->documento_identidad }}</td>
                        <td>{{ $inscripcion->persona->grado->nombre }}</td>
                        <td>{{ $inscripcion->cargo }}@if($inscripcion->observaciones)<small class="d-block">{{ $inscripcion->observaciones }}</small>@endif</td>
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
                    </tr>
                @empty
                    <tr><td colspan="8">No hay personas inscritas.</td></tr>
                @endforelse
            </tbody>
            <tbody class="report-payment-totals">
                <tr><th colspan="6" scope="row">Pagado Bs.-</th><td colspan="2">{{ number_format($totalPagadoBs, 2, '.', ',') }}</td></tr>
                <tr><th colspan="6" scope="row">Pagado $us.-</th><td colspan="2">{{ number_format($totalPagadoUsd, 2, '.', ',') }}</td></tr>
            </tbody>
            <tfoot class="report-print-footer"><tr><td colspan="8"></td></tr></tfoot>
        </table>
    </div>
</section>
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/inscripciones-imprimir.css') }}?v={{ filemtime(public_path('css/inscripciones-imprimir.css')) }}">
@endpush
@push('scripts')
<script>
    // Abre la impresión después de revisar el listado completo.
    document.getElementById('print-registrations').addEventListener('click', async () => {
        const button = document.getElementById('print-registrations');
        const letterhead = document.querySelector('.report-letterhead');
        button.disabled = true;
        try {
            await letterhead.decode();
            window.print();
        } catch (error) {
            window.showAppMessage('No se pudo cargar el membrete. Recarga la página antes de imprimir.');
        } finally {
            button.disabled = false;
        }
    });
</script>
@endpush
