@extends('layouts.app')
@section('section', 'Inscripción')
@section('title', 'Inscripciones')
@section('subtitle', 'Participantes, fotos e inscripciones en un solo lugar.')
@section('actions')
    @if(auth()->user()->isSuperAdmin())
    {{-- Abre el listado completo de inscritos; botón verde a la izquierda de Nueva inscripción. --}}
    <a href="{{ route('inscripciones.imprimir', ['evento_id' => request('evento_id')]) }}" class="btn btn-create"><img src="{{ asset('images/Impresora.png') }}" width="22" height="22" alt=""> Imprimir</a>
<a href="{{ route('inscripciones.create', ['evento_id' => request('evento_id')]) }}" class="btn btn-primary">＋ Nueva inscripción</a>
    @endif
@endsection
@section('content')
<section class="panel">
<form class="row g-3 mb-4" method="GET" role="search" id="filtro-inscripciones">
    <div class="col-md-6">
        <label for="evento_id" class="form-label">Evento</label>
        <select id="evento_id" name="evento_id" class="form-select">
            <option value="">Todos los eventos</option>
            @foreach($eventos as $evento)<option value="{{ $evento->id }}" @selected(request('evento_id') == $evento->id)>{{ $evento->nombre }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label for="q" class="form-label">Buscar participante</label>
        <div class="d-flex gap-2"><input id="q" name="q" class="form-control" placeholder="Nombre o documento…" value="{{ $q }}"><button class="btn btn-outline-secondary">Filtrar</button>
        @if($q || request('evento_id'))<a class="btn btn-light" href="{{ route('inscripciones.index') }}">Limpiar</a>@endif</div>
    </div>
</form>
<div class="panel-toolbar">
    <div>
        @php($eventoElegido = $eventos->firstWhere('id', request('evento_id')))
        <h2 class="d-flex flex-wrap align-items-baseline gap-2">Participantes inscritos @if($eventoElegido)<span class="text-secondary">· {{ $eventoElegido->nombre }}</span>@endif</h2>
        <p>{{ $inscripciones->total() }} resultados @if(auth()->user()->isSuperAdmin()) · Tarifas vigentes por grado @endif</p>
    </div>
</div>
<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Participante</th>
                <th>Documento / Grado</th>
                <th>Teléfono</th>
                @if(auth()->user()->isSuperAdmin())
                <th>Tarifa vigente</th>
                <th>Pagado</th>
                <th>Saldo</th>
                @endif
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
@forelse($inscripciones as $inscripcion)
<tr>
    <td>
        <div class="person-cell">@include('personas.photo', ['persona' => $inscripcion->persona])<div>
            <strong>{{ $inscripcion->persona->nombre_completo }}</strong>
            <small>#{{ str_pad($inscripcion->persona->id, 4, '0', STR_PAD_LEFT) }} · {{ $inscripcion->persona->sexo }}</small>
            <small class="d-block text-secondary">{{ $inscripcion->cargo }}</small>
            @if($inscripcion->observaciones)<small class="d-block text-secondary">{{ $inscripcion->observaciones }}</small>@endif
        </div></div>
    </td>
    <td>{{ $inscripcion->persona->documento_identidad ?: '—' }}<span class="d-block"><span class="grade-badge">{{ $inscripcion->persona->grado->nombre }}</span></span></td>
    <td>{{ $inscripcion->persona->telefono ?: '—' }}</td>
    @if(auth()->user()->isSuperAdmin())
    <td class="text-nowrap">
        Bs {{ number_format($inscripcion->tarifa_vigente, 2, ',', '.') }}
    </td>
    <td class="text-nowrap">
        Bs {{ number_format($inscripcion->total_pagado, 2, ',', '.') }}
    </td>
    <td class="text-nowrap">
        Bs {{ number_format($inscripcion->saldo_pendiente, 2, ',', '.') }}
    </td>
    @endif
    <td class="text-end text-nowrap">
        <div class="table-actions registration-actions">
            @if(auth()->user()->isSuperAdmin())
            <a class="btn action-button btn-success" href="{{ route('inscripciones.pagos.create', $inscripcion) }}" title="Agregar pago" aria-label="Agregar pago de {{ $inscripcion->persona->nombre_completo }} para {{ $inscripcion->evento->nombre }}"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i></a>
            @endif
            <a class="btn action-button btn-warning" href="{{ route('inscripciones.credencial', $inscripcion) }}" title="Credencial" aria-label="Credencial de {{ $inscripcion->persona->nombre_completo }} para {{ $inscripcion->evento->nombre }}"><i class="fa-regular fa-id-badge" aria-hidden="true"></i></a>
            <a class="btn action-button action-delete" href="{{ route('inscripciones.certificado', $inscripcion) }}" title="Certifica" aria-label="Certifica a {{ $inscripcion->persona->nombre_completo }}"><img src="{{ asset('images/diploma.png') }}" width="21" height="21" alt=""></a>
            @if(auth()->user()->isSuperAdmin())
            <a class="btn action-button action-edit" href="{{ route('inscripciones.edit', $inscripcion) }}" title="Editar" aria-label="Editar inscripción de {{ $inscripcion->persona->nombre_completo }}"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
            <form method="POST" action="{{ route('inscripciones.destroy', $inscripcion) }}" data-confirm="¿Eliminar esta inscripción? Esta acción no se puede deshacer.">
                @csrf @method('DELETE')
                <button class="btn action-button action-delete" title="Eliminar" aria-label="Eliminar inscripción de {{ $inscripcion->persona->nombre_completo }}"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
            </form>
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="{{ auth()->user()->isSuperAdmin() ? 7 : 4 }}">
        <div class="empty-state">
            @include('partials.icon', ['name' => 'inscripciones'])
            <h3>{{ $q || request('evento_id') ? 'No hay inscripciones con estos filtros' : 'Haz parte del encuentro' }}</h3>
            @if(auth()->user()->isSuperAdmin())
            <p>Selecciona una persona, un evento y el cargo que desempeñará.</p>
            <a class="btn btn-primary" href="{{ route('inscripciones.create') }}">Registrar inscripción</a>
            @endif
        </div>
    </td>
</tr>
@endforelse
</tbody></table></div><div class="panel-pagination">{{ $inscripciones->links() }}</div>
</section>
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/person-photo-preview.css') }}?v={{ filemtime(public_path('css/person-photo-preview.css')) }}">
@endpush
@push('scripts')
<script src="{{ asset('js/person-photo-preview.js') }}?v={{ filemtime(public_path('js/person-photo-preview.js')) }}" defer></script>
<script>
document.getElementById('evento_id').addEventListener('change', () => document.getElementById('filtro-inscripciones').requestSubmit());
</script>
@endpush
