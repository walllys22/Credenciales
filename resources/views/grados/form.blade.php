@extends('layouts.app')
@section('section', 'Grado')
@section('title', $grado->exists ? 'Editar grado' : 'Nuevo grado')
@section('subtitle', 'Define el nombre del grado que utilizará tu organización.')
@section('actions')
    <a href="{{ route('grados.index') }}" class="btn btn-outline-secondary btn-project" data-action-style="back" aria-label="Volver a grados" title="Volver a grados"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i><span class="button-label">Volver a grados</span></a>
@endsection

@section('content')
<form method="POST" action="{{ $grado->exists ? route('grados.update', $grado) : route('grados.store') }}" class="panel form-panel">
    @csrf
    @if($grado->exists) @method('PUT') @endif
    <div class="form-section-heading">
        <span class="step-number">01</span>
        <div><h2>Datos del grado</h2><p>El nombre es obligatorio. El ID se asigna automáticamente.</p></div>
    </div>
    @if($grado->exists)
        <div class="mb-3">
            <label for="grado-id" class="form-label">ID</label>
            <input id="grado-id" class="form-control" value="{{ $grado->id }}" readonly>
        </div>
    @endif
    <x-field name="nombre" label="Nombre" :value="$grado->nombre" :required="true" maxlength="255" />
    <div class="form-actions">
        <a href="{{ route('grados.index') }}" class="btn btn-outline-secondary btn-project" data-action-style="cancel" aria-label="Cancelar" title="Cancelar"><i class="fa-solid fa-ban" aria-hidden="true"></i><span class="button-label">Cancelar</span></a>
        <button class="btn btn-primary btn-project" data-action-style="save" aria-label="{{ $grado->exists ? 'Guardar cambios' : 'Crear grado' }}" title="{{ $grado->exists ? 'Guardar cambios' : 'Crear grado' }}"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span class="button-label">{{ $grado->exists ? 'Guardar cambios' : 'Crear grado' }}</span></button>
    </div>
</form>
@endsection
