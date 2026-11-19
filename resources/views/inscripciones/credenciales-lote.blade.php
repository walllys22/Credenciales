@extends('layouts.app')
@section('section', 'Inscripción / Credenciales por lotes')
@section('title', 'Credenciales por lotes')
@section('actions')
<form method="POST" action="{{ route('inscripciones.credenciales-lote') }}" class="d-inline">
    @csrf
    <input type="hidden" name="evento_id" value="{{ $credenciales->first()['inscripcion']->evento_id }}">
    <input type="hidden" name="pdf" value="1">
    @foreach($credenciales as $credencial)<input type="hidden" name="inscripciones[]" value="{{ $credencial['inscripcion']->id }}">@endforeach
    <button type="submit" class="btn btn-project" data-action-style="pdf" aria-label="Descargar PDF" title="Descargar PDF"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i><span class="button-label">PDF</span></button>
</form>
<button type="button" class="btn btn-create btn-project" id="print-credential" data-action-style="print" aria-label="Imprimir" title="Imprimir"><i class="fa-solid fa-print" aria-hidden="true"></i><span class="button-label">Imprimir</span></button>
<a href="{{ route('inscripciones.index', ['evento_id' => $credenciales->first()['inscripcion']->evento_id]) }}" class="btn btn-close-page btn-project" data-action-style="close" aria-label="Volver a inscripciones" title="Volver a inscripciones"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="button-label">Volver a inscripciones</span></a>
@endsection
@section('content')
<p class="batch-instructions">Revise las credenciales y pulse Imprimir. Cada hoja contiene hasta dos inscritos, con anverso y reverso.</p>
<div class="batch-preview">
@foreach($credenciales->chunk(2) as $pagina)
<div class="batch-page {{ $loop->last ? 'batch-last' : '' }}" aria-label="Página {{ $loop->iteration }} de {{ $loop->count }}">
@foreach($pagina as $credencial)
@php($inscripcion = $credencial['inscripcion'])
@php($persona = $inscripcion->persona)
@php($evento = $inscripcion->evento)
@php($qr = $credencial['qr'])
@php($verificacionUrl = $credencial['verificacionUrl'])
<div class="credential-sheet" aria-label="Credencial de {{ $persona->nombre_completo }}">
        <section class="credential-side">
            <h2 class="credential-side-label">Anverso</h2>
            <article class="credential-card credential-front" data-credential-zoom role="button" tabindex="0" aria-label="Ampliar anverso de la credencial" aria-expanded="false" aria-controls="credential-zoom">
                <img class="credential-template" src="{{ asset('images/anverso.png') }}?v={{ filemtime(public_path('images/anverso.png')) }}" alt="" aria-hidden="true">
                <div class="credential-card-content">
                    <img class="credential-photo" src="{{ $persona->foto_url }}" alt="Foto de {{ $persona->nombre_completo }}">
                    @if($persona->pais)
                    <div class="credential-country">
                        @if($persona->pais->bandera_url)<img src="{{ $persona->pais->bandera_url }}" alt="Bandera de {{ $persona->pais->nombre }}" width="20" height="14">@endif
                        <span>{{ $persona->pais->nombre }}</span>
                    </div>
                    @endif
                    <dl class="credential-data"><div><dt>Nombre:</dt><dd>{{ $persona->nombre_completo }}</dd></div><div><dt>Cargo:</dt><dd>{{ $inscripcion->cargo }}</dd></div><div><dt>Grado:</dt><dd>{{ $persona->grado->nombre }}</dd></div><div><dt>CI:</dt><dd>{{ $persona->documento_identidad }}</dd></div></dl>
                </div>
                <div class="credential-bottom"><img src="{{ asset('images/Logo.png') }}?v={{ filemtime(public_path('images/Logo.png')) }}" alt="Control de Inscripciones"><span>#{{ str_pad($inscripcion->id, 6, '0', STR_PAD_LEFT) }}</span></div>
            </article>
        </section>
        <section class="credential-side">
            <h2 class="credential-side-label">Reverso</h2>
            <article class="credential-card credential-back" data-credential-zoom role="button" tabindex="0" aria-label="Ampliar reverso de la credencial" aria-expanded="false" aria-controls="credential-zoom">
                <img class="credential-template" src="{{ asset('images/reverso.png') }}?v={{ filemtime(public_path('images/reverso.png')) }}" alt="" aria-hidden="true">
                <div class="credential-card-content">
                    <h3 class="credential-verification-title">VERIFICACIÓN DIGITAL</h3>
                    <a class="credential-qr" href="{{ $verificacionUrl }}" aria-label="Verificar inscripción de {{ $persona->nombre_completo }}"><img src="{{ $qr }}" alt="Código QR para verificar esta inscripción" width="130" height="130"></a>
                    <p class="credential-event-title">{{ $evento->nombre }}</p>
                    <p class="credential-dates">{{ $evento->fecha_inicio->format('d/m/Y') }} al {{ $evento->fecha_final->format('d/m/Y') }}<br>{{ $evento->lugar }}<br>{{ $evento->ciudad }}</p>
                </div>
                <div class="credential-bottom"><img src="{{ asset('images/Logo.png') }}?v={{ filemtime(public_path('images/Logo.png')) }}" alt="Control de Inscripciones"><span>#{{ str_pad($inscripcion->id, 6, '0', STR_PAD_LEFT) }}</span></div>
            </article>
        </section>
</div>
@endforeach
</div>
@endforeach
</div>
<nav class="batch-page-navigation" aria-label="Páginas de credenciales" hidden>
    <button type="button" class="btn btn-project" data-action-style="back" data-batch-previous title="Página anterior" aria-label="Página anterior"><i class="fa-solid fa-chevron-up" aria-hidden="true"></i><span class="button-label">Página anterior</span></button>
    <span data-batch-status role="status" aria-live="polite"></span>
    <button type="button" class="btn btn-project" data-action-style="manage" data-batch-next title="Página siguiente" aria-label="Página siguiente"><i class="fa-solid fa-chevron-down" aria-hidden="true"></i><span class="button-label">Página siguiente</span></button>
</nav>
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/credenciales.css') }}?v={{ filemtime(public_path('css/credenciales.css')) }}">
<style>
@page { size: 210mm 294mm; margin: 10mm; }
@media screen {
    .batch-preview { overflow-x:auto; padding:8px 8px 24px; }
    .batch-page { box-sizing:border-box; width:210mm; min-height:294mm; padding:10mm; margin:0 auto 16px; background:#fff; box-shadow:0 2px 10px #0003; scroll-margin-top:12px; }
    .batch-page .credential-sheet { display:flex; flex-wrap:nowrap; justify-content:flex-start; width:190mm; height:126mm; gap:4mm; padding:0; margin:0 0 4mm; }
    .batch-page .credential-side { width:93mm; flex:0 0 93mm; max-width:none; }
    .batch-page .credential-card { width:93mm; height:126mm; box-shadow:none; }
    .batch-page .credential-side-label { display:none; }
    .batch-page-navigation:not([hidden]) { display:flex; }
    .batch-page-navigation { position:fixed; right:20px; bottom:20px; z-index:1030; padding:8px; gap:10px; align-items:center; background:#fff; border:1px solid #dce4ef; border-radius:8px; box-shadow:0 3px 14px #0002; font:400 13px/1.3 Arial,sans-serif; }
}
@media print {
    .batch-instructions, .batch-page-navigation, .batch-page .credential-side-label { display:none !important; }
    .batch-page { break-after:page; page-break-after:always; }
    .batch-page.batch-last { break-after:auto; page-break-after:auto; }
    .batch-page .credential-sheet { width:190mm; height:126mm; gap:4mm !important; margin:0 0 4mm; }
    .batch-page .credential-side { width:93mm !important; flex:0 0 93mm !important; }
    .batch-page .credential-card { width:93mm !important; height:126mm !important; }
}
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/credenciales.js') }}?v={{ filemtime(public_path('js/credenciales.js')) }}" defer></script>
<script src="{{ asset('js/credenciales-lote-paginas.js') }}?v={{ filemtime(public_path('js/credenciales-lote-paginas.js')) }}" defer></script>
@endpush
