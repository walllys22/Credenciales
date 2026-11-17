@extends('layouts.app')
@section('title', $persona->exists ? 'Editar persona' : 'Nueva persona')
@section('subtitle', 'Completa la información personal y asigna un grado al participante.')
@section('actions')<a href="{{ route('personas.index') }}" class="btn btn-outline-secondary btn-project" data-action-style="back" aria-label="Volver al directorio" title="Volver al directorio"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i><span class="button-label">Volver al directorio</span></a>@endsection
@section('content')
<form method="POST" enctype="multipart/form-data" action="{{ $persona->exists ? route('personas.update', $persona) : route('personas.store') }}" class="panel form-panel">
@csrf @if($persona->exists) @method('PUT') @endif
@include('personas.datos')
<div class="form-actions"><a class="btn btn-outline-secondary btn-project" href="{{ route('personas.index') }}" data-action-style="cancel" aria-label="Cancelar" title="Cancelar"><i class="fa-solid fa-ban" aria-hidden="true"></i><span class="button-label">Cancelar</span></a><button class="btn btn-primary btn-project" @disabled($grados->isEmpty()) data-action-style="save" aria-label="{{ $persona->exists ? 'Guardar cambios' : 'Registrar persona' }}" title="{{ $persona->exists ? 'Guardar cambios' : 'Registrar persona' }}"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span class="button-label">{{ $persona->exists ? 'Guardar cambios' : 'Registrar persona' }}</span></button></div>
</form>
@endsection
@push('scripts')
<script src="{{ asset('js/personas.js') }}?v={{ filemtime(public_path('js/personas.js')) }}" defer></script>
@endpush
