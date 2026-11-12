@extends('layouts.app')
@section('section', 'Inscripción / Certificado')
@section('title', 'Certificado')
@section('heading-icon')<img src="{{ asset('images/diploma.png') }}" width="25" height="25" alt="">@endsection
@section('actions')
    <button type="button" class="btn btn-create" id="print-certificate"><i class="fa-solid fa-print" aria-hidden="true"></i> Imprimir</button>
    <a href="{{ route('inscripciones.index') }}" class="btn btn-close-page"><i class="fa-solid fa-door-open" aria-hidden="true"></i> Cerrar</a>
@endsection
@section('content')
<div class="certificate-stage">
    <article class="certificate" aria-label="Certificado de participación de {{ $persona->nombre_completo }} en {{ $evento->nombre }}">
        <img class="certificate-template" src="{{ asset('images/Certificado.png') }}" alt="Certificado de participación Shorin Ryu Shorinkan - Karate y Kobudo">
        <div class="certificate-recipient">{{ $nombreCertificado }}</div>
    </article>
</div>
@endsection
@push('styles')
<link rel="preload" href="{{ asset('fonts/agnesa-kyoto.ttf') }}" as="font" type="font/ttf" crossorigin>
<link rel="stylesheet" href="{{ asset('css/certificados.css') }}?v={{ filemtime(public_path('css/certificados.css')) }}">
@endpush
@push('scripts')
<script src="{{ asset('js/certificados.js') }}?v={{ filemtime(public_path('js/certificados.js')) }}" defer></script>
@endpush
