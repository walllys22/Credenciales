@extends('layouts.app')
@section('section', 'Inscripción')
@section('title', 'Inscripciones')
@section('subtitle', 'Conecta a cada participante con su evento y su función en el encuentro.')
@section('actions')
    @if(auth()->user()->isSuperAdmin())
    {{-- Abre el listado completo de inscritos; botón verde a la izquierda de Nueva inscripción. --}}
    <a href="{{ route('inscripciones.imprimir') }}" class="btn btn-create"><img src="{{ asset('images/Impresora.png') }}" width="22" height="22" alt=""> Imprimir</a>
<a href="{{ route('inscripciones.create') }}" class="btn btn-primary">＋ Nueva inscripción</a>
    @endif
@endsection
@section('content')
<section class="panel">
<div class="panel-toolbar">
    <div>
        <h2>Registro de inscripciones</h2>
        <p>{{ $inscripciones->total() }} resultados @if(auth()->user()->isSuperAdmin()) · Tarifas vigentes por grado @endif</p>
    </div>
    <form class="search-form flex-wrap" method="GET" role="search">
        <label class="visually-hidden" for="evento_id">Filtrar por evento</label>
        <select id="evento_id" name="evento_id" class="form-select">
            <option value="">Todos los eventos</option>
            @foreach($eventos as $evento)
            <option value="{{ $evento->id }}" @selected(request('evento_id') == $evento->id)>{{ $evento->nombre }}</option>
            @endforeach
        </select>
        <label class="visually-hidden" for="q">Buscar participante</label>
        <input id="q" name="q" class="form-control" placeholder="Nombre o documento…" value="{{ $q }}">
        <button class="btn btn-outline-secondary">Filtrar</button>
        @if($q || request('evento_id'))
        <a class="btn btn-light" href="{{ route('inscripciones.index') }}">Limpiar</a>
        @endif
    </form>
</div>
<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Participante</th>
                <th>Evento</th>
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
        <strong>{{ $inscripcion->persona->nombre_completo }}</strong>
        <small class="d-block text-secondary">{{ $inscripcion->persona->documento_identidad }} · {{ $inscripcion->persona->grado->nombre }}</small>
    </td>
    <td>
        {{ $inscripcion->evento->nombre }}
        <span class="d-block">{{ $inscripcion->cargo }}</span>
    </td>
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
    <td colspan="{{ auth()->user()->isSuperAdmin() ? 6 : 3 }}">
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
