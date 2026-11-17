@extends('layouts.app')
{{-- Listado completo sin paginación para revisar e imprimir todas las personas inscritas. --}}
@section('section', 'Inscripción / Imprimir')
@section('title', 'Personas inscritas')
@section('actions')
    <a href="{{ route('inscripciones.pdf', request()->only('evento_id')) }}" class="btn btn-project" data-action-style="pdf" aria-label="Descargar PDF" title="Descargar PDF"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i><span class="button-label">PDF</span></a>
    <button type="button" class="btn btn-create btn-project" id="print-registrations" data-pdf-url="{{ route('inscripciones.pdf', array_merge(request()->only('evento_id'), ['vista' => 1])) }}" data-action-style="print" aria-label="Imprimir" title="Imprimir"><i class="fa-solid fa-print" aria-hidden="true"></i><span class="button-label">Imprimir</span></button>
    <a href="{{ route('inscripciones.index') }}" class="btn btn-close-page btn-project" data-action-style="close" aria-label="Cerrar" title="Cerrar"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="button-label">Cerrar</span></a>
@endsection
@section('content')
<section class="panel registrations-report" id="report-source">
    <div class="report-page-header"><img class="report-letterhead" src="{{ asset('images/Cabecera.png') }}?v={{ hash_file('sha256', public_path('images/Cabecera.png')) }}" alt="" aria-hidden="true"><h2>Listado de Inscritos</h2></div>
    <div class="table-responsive">
        <table class="table align-middle">
            <colgroup><col class="report-col-number"><col class="report-col-name"><col class="report-col-document"><col class="report-col-grade"><col class="report-col-money"><col class="report-col-money"><col class="report-col-money"></colgroup>
            <thead>
                <tr><th class="report-number">N.º</th><th>Nombre completo</th><th>Documento</th><th>Grado</th><th>Costo</th><th>Pagado</th><th>Saldo</th></tr>
            </thead>
            <tbody class="report-participants">
                @include('inscripciones.reporte-filas')
            </tbody>
            @include('inscripciones.reporte-totales')
            <tfoot class="report-print-footer"><tr><td colspan="7"></td></tr></tfoot>
        </table>
    </div>
    <div class="report-letterhead-footer"><div>SEMINARIO INTERNACIONAL DE KARATE <span>&amp;</span> KOBUDO</div><div class="report-footer-country">BOLIVIA 2026</div></div>
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
    // Imprime el mismo PDF de descarga, con cabecera y pie incorporados en cada hoja.
    document.getElementById('print-registrations').addEventListener('click', async () => {
        const button = document.getElementById('print-registrations');
        button.disabled = true;
        try {
            const response = await fetch(button.dataset.pdfUrl);
            if (!response.ok || !response.headers.get('Content-Type')?.includes('application/pdf')) throw new Error('PDF');
            const url = URL.createObjectURL(await response.blob());
            const previousFrame = document.getElementById('report-print-frame');
            if (previousFrame) {
                URL.revokeObjectURL(previousFrame.src);
                previousFrame.remove();
            }
            const frame = document.createElement('iframe');
            frame.id = 'report-print-frame';
            frame.title = 'Impresión del reporte';
            frame.style.cssText = 'position:fixed;left:-10000px;top:0;width:1px;height:1px;border:0';
            frame.onload = () => {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            };
            frame.src = url;
            document.body.append(frame);
        } catch (error) {
            window.showAppMessage('No se pudo preparar la impresión. Vuelve a intentarlo o descarga el PDF.');
        } finally {
            button.disabled = false;
        }
    });
</script>
@endpush
