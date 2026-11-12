@extends('layouts.app')
{{-- Formulario del catálogo: el ID se asigna automáticamente. --}}
@section('section', 'País')
@section('title', $pais->exists ? 'Editar país' : 'Nuevo país')
@section('heading-icon')<i class="fa-solid fa-earth-americas" aria-hidden="true"></i>@endsection
@section('actions')<a href="{{ route('paises.index') }}" class="btn btn-outline-secondary">Volver a países</a>@endsection
@section('content')
<form method="POST" action="{{ $pais->exists ? route('paises.update', $pais) : route('paises.store') }}" class="panel form-panel">
    @csrf
    @if($pais->exists) @method('PUT') @endif
    <div class="form-section-heading"><span class="step-number">01</span><div><h2>Datos del país</h2><p>El nombre es obligatorio. El ID se asigna automáticamente.</p></div></div>
    @if($pais->exists)<div class="mb-3"><label for="pais-id" class="form-label">ID</label><input id="pais-id" class="form-control" value="{{ $pais->id }}" readonly></div>@endif
    <x-field name="nombre" label="Nombre" :value="$pais->nombre" :required="true" maxlength="255" />
    <div class="form-actions">
        <a href="{{ route('paises.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button class="btn btn-primary">{{ $pais->exists ? 'Guardar cambios' : 'Crear país' }}</button>
    </div>
</form>
@endsection
