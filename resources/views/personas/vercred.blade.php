@extends('layouts.app')
@section('section', 'Inscripción / Credencial')
@section('title', 'Credencial')
@section('heading-icon')<i class="fa-regular fa-id-badge" aria-hidden="true"></i>@endsection
@section('actions')
    @if($inscripcion)<button type="button" class="btn btn-create btn-project" id="print-credential" data-action-style="print" aria-label="Imprimir" title="Imprimir"><i class="fa-solid fa-print" aria-hidden="true"></i><span class="button-label">Imprimir</span></button>@endif
    <a href="{{ route('inscripciones.index') }}" class="btn btn-close-page btn-project" data-action-style="close" aria-label="Cerrar" title="Cerrar"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="button-label">Cerrar</span></a>
@endsection
@section('content')
@if(!$inscripcion)
    <div class="panel p-4"><h2>La persona no tiene inscripciones</h2><p class="mt-3">Para emitir la credencial de {{ $persona->nombre_completo }}, primero debe estar inscrita en un evento.</p><a class="btn btn-primary btn-project" href="{{ route('inscripciones.create') }}" data-action-style="save" aria-label="Registrar inscripción" title="Registrar inscripción"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span class="button-label">Registrar inscripción</span></a></div>
@else
    @php($evento = $inscripcion->evento)
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
        <section class="credential-side credential-file-section">
            <h2 class="credential-side-label">Ficha</h2>
            @include('personas.ficha', ['zoom' => true])
        </section>
    </div>
@endif
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/credenciales.css') }}?v={{ filemtime(public_path('css/credenciales.css')) }}">
@endpush
@push('scripts')
<script src="{{ asset('js/credenciales.js') }}?v={{ filemtime(public_path('js/credenciales.js')) }}" defer></script>
@endpush
