@extends('layouts.app')
@section('title', 'Personas')
@section('subtitle', 'Cada persona cuenta. Gestiona aquí a quienes forman parte de tus eventos.')
@section('actions')
<a href="{{ route('grados.index') }}" class="btn btn-outline-secondary btn-project" data-action-style="manage" aria-label="Gestionar grados" title="Gestionar grados"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span class="button-label">Gestionar grados</span></a>
<a href="{{ route('personas.create') }}" class="btn btn-primary btn-project" data-action-style="create" aria-label="Nueva persona" title="Nueva persona"><i class="fa-solid fa-plus" aria-hidden="true"></i><span class="button-label">Nueva persona</span></a>
@endsection
@section('content')
<section class="panel">
    <div class="panel-toolbar"><div><h2>Directorio de personas</h2><p>Información de contacto y grado de cada participante.</p></div><form class="search-form" method="GET" role="search"><label class="visually-hidden" for="q">Buscar persona</label><input class="form-control" id="q" name="q" value="{{ $q }}" placeholder="Nombre o documento…"><button class="btn btn-outline-secondary btn-project" data-action-style="search" aria-label="Buscar" title="Buscar"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span class="button-label">Buscar</span></button>@if($q !== '')<a class="btn btn-light btn-project" href="{{ route('personas.index') }}" data-action-style="reset" aria-label="Limpiar" title="Limpiar"><i class="fa-solid fa-broom" aria-hidden="true"></i><span class="button-label">Limpiar</span></a>@endif</form></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Persona</th><th>Documento de identidad</th><th>Grado</th><th>Teléfono</th><th class="text-end">Acciones</th></tr></thead><tbody>
    @forelse($personas as $persona)
    <tr><td><div class="person-cell">@include('personas.photo', ['persona' => $persona])<div><strong>{{ $persona->nombre_completo }}</strong><small>#{{ str_pad($persona->id, 4, '0', STR_PAD_LEFT) }} · {{ $persona->sexo }}</small></div></div></td><td>{{ $persona->documento_identidad }}</td><td><x-grado-badge :nombre="$persona->grado->nombre"/></td><td>{{ $persona->telefono }}</td><td class="text-end text-nowrap"><a class="btn btn-sm btn-edit btn-project" href="{{ route('personas.edit', $persona) }}" data-action-style="edit" aria-label="Editar" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i><span class="button-label">Editar</span></a>@include('partials.delete', ['action' => route('personas.destroy', $persona), 'label' => $persona->nombre_completo])</td></tr>
    @empty
    <tr><td colspan="5"><div class="empty-state">@include('partials.icon', ['name' => 'personas'])<h3>{{ $q ? 'No encontramos coincidencias' : 'Tu próximo encuentro empieza aquí' }}</h3><p>{{ $q ? 'Prueba con otro nombre o documento de identidad.' : 'Agrega la primera persona para comenzar a organizar tus eventos.' }}</p><a class="btn btn-primary btn-project" href="{{ route('personas.create') }}" data-action-style="save" aria-label="Registrar persona" title="Registrar persona"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span class="button-label">Registrar persona</span></a></div></td></tr>
    @endforelse
    </tbody></table></div>
    <div class="panel-pagination">{{ $personas->links() }}</div>
</section>
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/person-photo-preview.css') }}?v={{ filemtime(public_path('css/person-photo-preview.css')) }}">
@endpush
@push('scripts')
<script src="{{ asset('js/person-photo-preview.js') }}?v={{ filemtime(public_path('js/person-photo-preview.js')) }}" defer></script>
@endpush
