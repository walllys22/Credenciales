@extends('layouts.app')
{{-- Formulario del catálogo: el ID se asigna automáticamente. --}}
@section('section', 'País')
@section('title', $pais->exists ? 'Editar país' : 'Nuevo país')
@section('heading-icon')<i class="fa-solid fa-earth-americas" aria-hidden="true"></i>@endsection
@section('actions')<a href="{{ route('paises.index') }}" class="btn btn-outline-secondary btn-project" data-action-style="back" aria-label="Volver a países" title="Volver a países"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i><span class="button-label">Volver a países</span></a>@endsection
@section('content')
<form method="POST" action="{{ $pais->exists ? route('paises.update', $pais) : route('paises.store') }}" class="panel form-panel">
    @csrf
    @if($pais->exists) @method('PUT') @endif
    <div class="form-section-heading"><span class="step-number">01</span><div><h2>Datos del país</h2><p>El nombre es obligatorio. El ID se asigna automáticamente.</p></div></div>
    @if($pais->exists)<div class="mb-3"><label for="pais-id" class="form-label">ID</label><input id="pais-id" class="form-control" value="{{ $pais->id }}" readonly></div>@endif
    <x-field name="nombre" label="Nombre" :value="$pais->nombre" :required="true" maxlength="255" />
    <div class="form-actions">
        <a href="{{ route('paises.index') }}" class="btn btn-outline-secondary btn-project" data-action-style="cancel" aria-label="Cancelar" title="Cancelar"><i class="fa-solid fa-ban" aria-hidden="true"></i><span class="button-label">Cancelar</span></a>
        <button class="btn btn-primary btn-project" data-action-style="save" aria-label="{{ $pais->exists ? 'Guardar cambios' : 'Crear país' }}" title="{{ $pais->exists ? 'Guardar cambios' : 'Crear país' }}"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span class="button-label">{{ $pais->exists ? 'Guardar cambios' : 'Crear país' }}</span></button>
    </div>
</form>
@endsection
