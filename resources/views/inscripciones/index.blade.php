@extends('layouts.app')
@php($eventoElegido = $eventos->firstWhere('id', request('evento_id')))
@section('section', 'Inscripción')
@section('title', 'Inscripciones')
@section('subtitle', 'Participantes, fotos e inscripciones en un solo lugar.')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/inscripciones-listado.css') }}?v={{ filemtime(public_path('css/inscripciones-listado.css')) }}">
@endpush
@section('actions')
    @if($eventoElegido)<button type="submit" form="form-credenciales-lote" class="btn btn-create btn-project" data-action-style="print" aria-label="Imprimir credenciales por lotes" title="Imprimir credenciales por lotes (mínimo dos)" id="imprimir-lote" disabled><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span class="button-label">Imprimir credenciales por lotes</span></button>@endif
    @if(auth()->user()->isSuperAdmin())
    {{-- Abre el listado completo de inscritos; botón verde a la izquierda de Nueva inscripción. --}}
    @if($eventoElegido)<a href="{{ route('inscripciones.imprimir', ['evento_id' => request('evento_id')]) }}" class="btn btn-create btn-project" data-action-style="print" aria-label="Imprimir" title="Imprimir"><i class="fa-solid fa-print" aria-hidden="true"></i><span class="button-label">Imprimir</span></a>@endif
<a href="{{ route('inscripciones.create', ['evento_id' => request('evento_id')]) }}" class="btn btn-primary btn-project" data-action-style="create" aria-label="Nueva inscripción" title="Nueva inscripción"><i class="fa-solid fa-plus" aria-hidden="true"></i><span class="button-label">Nueva inscripción</span></a>
    @endif
@endsection
@section('content')
<section class="panel registrations-index">
<form class="row g-3" method="GET" role="search" id="filtro-inscripciones">
    <div class="col-md-6">
        <label for="evento_id" class="form-label ms-3">Evento</label>
        <select id="evento_id" name="evento_id" class="form-select">
            <option value="">Seleccione un evento</option>
            @foreach($eventos as $evento)<option value="{{ $evento->id }}" @selected(request('evento_id') == $evento->id)>{{ $evento->nombre }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label for="q" class="form-label">Buscar participante</label>
        <div class="d-flex gap-2"><input id="q" name="q" class="form-control" placeholder="Nombre o documento…" value="{{ $q }}"><button class="btn btn-outline-secondary btn-project" data-action-style="filter" aria-label="Filtrar" title="Filtrar"><i class="fa-solid fa-filter" aria-hidden="true"></i><span class="button-label">Filtrar</span></button>
        @if($q || request('evento_id'))<a class="btn btn-light btn-project" href="{{ route('inscripciones.index') }}" data-action-style="reset" aria-label="Limpiar" title="Limpiar"><i class="fa-solid fa-broom" aria-hidden="true"></i><span class="button-label">Limpiar</span></a>@endif</div>
    </div>
</form>
@if($eventoElegido)
<div class="panel-toolbar">
    <div>
        <h2 class="d-flex flex-wrap align-items-baseline gap-2">Participantes inscritos @if($eventoElegido)<span class="text-secondary">· {{ $eventoElegido->nombre }}</span>@endif</h2>
    </div>
</div>
<form id="form-credenciales-lote" method="POST" action="{{ route('inscripciones.credenciales-lote') }}" data-evento="{{ $eventoElegido->id }}">
    @csrf
    <input type="hidden" name="evento_id" value="{{ $eventoElegido->id }}">
    <div id="seleccion-lote-campos"></div>
    <p id="seleccion-lote-estado" role="status">Seleccione al menos dos inscritos para imprimir sus credenciales.</p>
    <button type="button" id="limpiar-seleccion-lote" class="btn btn-sm btn-outline-secondary mb-3">Limpiar selección</button>
</form>
<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th><input type="checkbox" id="seleccionar-pagina" aria-label="Seleccionar inscritos de esta página"></th>
                <th>Participante</th>
                <th>Organización / Grado</th>
                <th>Teléfono</th>
                @if(auth()->user()->isSuperAdmin())
                <th>Costo / Pagado / Saldo</th>
                @endif
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
@forelse($inscripciones as $inscripcion)
<tr>
    <td><input type="checkbox" class="seleccionar-credencial" value="{{ $inscripcion->id }}" aria-label="Seleccionar a {{ $inscripcion->persona->nombre_completo }}"></td>
    <td>
        <div class="person-cell">@include('personas.photo', ['persona' => $inscripcion->persona])<div>
            <strong>{{ $inscripcion->persona->nombre_completo }}</strong>
            <small>#{{ str_pad($inscripcion->persona->id, 4, '0', STR_PAD_LEFT) }} · {{ $inscripcion->persona->sexo }} - {{ $inscripcion->persona->documento_identidad ?: '—' }}</small>
            <small class="text-secondary"><span>{{ $inscripcion->cargo }}</span> - <span class="d-inline-flex align-items-center gap-1"><span>{{ $inscripcion->persona->pais?->nombre ?? 'País sin registrar' }}</span>@if($inscripcion->persona->pais?->bandera_url)<img src="{{ $inscripcion->persona->pais->bandera_url }}" alt="" aria-hidden="true" width="21" height="14" style="object-fit:contain">@endif</span></small>
            @if($inscripcion->observaciones)<small class="d-block text-secondary">{{ $inscripcion->observaciones }}</small>@endif
        </div></div>
    </td>
    <td>
        <div class="mb-1">{{ $inscripcion->persona->organizacion ?: 'Sin organización registrada' }}</div>
        <x-grado-badge :nombre="$inscripcion->persona->grado->nombre"/>
    </td>
    <td>{{ $inscripcion->persona->telefono ?: '—' }}</td>
    @if(auth()->user()->isSuperAdmin())
    <td class="text-nowrap">
        <div>Costo: <strong>Bs {{ number_format($inscripcion->tarifa_vigente, 2, ',', '.') }}</strong></div>
        <div>Pagado: <strong>@include('inscripciones.importe-pagado')</strong></div>
        <div>Saldo: <strong>Bs {{ number_format($inscripcion->saldo_pendiente, 2, ',', '.') }}</strong></div>
    </td>
    @endif
    <td class="text-end text-nowrap">
        <div class="table-actions registration-actions">
            @if(auth()->user()->isSuperAdmin())
            <button type="button" class="btn action-button btn-project" data-action-style="view" data-bs-toggle="modal" data-bs-target="#ver-participante-{{ $inscripcion->id }}" title="Ver participante" aria-label="Ver todos los datos de {{ $inscripcion->persona->nombre_completo }}"><i class="fa-solid fa-eye" aria-hidden="true"></i><span class="button-label">Ver participante</span></button>
            <a class="btn action-button btn-success btn-project" href="{{ route('inscripciones.pagos.create', $inscripcion) }}" title="Agregar pago" aria-label="Agregar pago de {{ $inscripcion->persona->nombre_completo }} para {{ $inscripcion->evento->nombre }}" data-action-style="payment"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i><span class="button-label">Agregar pago</span></a>
            @endif
            <a class="btn action-button btn-warning btn-project" href="{{ route('inscripciones.credencial', $inscripcion) }}" title="Credencial" aria-label="Credencial de {{ $inscripcion->persona->nombre_completo }} para {{ $inscripcion->evento->nombre }}" data-action-style="credential"><i class="fa-solid fa-id-badge" aria-hidden="true"></i><span class="button-label">Credencial</span></a>
            <a class="btn action-button action-delete btn-project" href="{{ route('inscripciones.certificado', $inscripcion) }}" title="Certifica" aria-label="Certifica a {{ $inscripcion->persona->nombre_completo }}" data-action-style="certificate"><img src="{{ asset('images/diploma.png') }}" width="21" height="21" alt=""><span class="button-label">Certifica</span></a>
            @if(auth()->user()->isSuperAdmin())
            <a class="btn action-button action-edit btn-project" href="{{ route('inscripciones.edit', $inscripcion) }}" title="Editar" aria-label="Editar inscripción de {{ $inscripcion->persona->nombre_completo }}" data-action-style="edit"><i class="fa-solid fa-pen" aria-hidden="true"></i><span class="button-label">Editar</span></a>
            <form id="eliminar-inscripcion-{{ $inscripcion->id }}" method="POST" action="{{ route('inscripciones.destroy', $inscripcion) }}">
                @csrf @method('DELETE')
                <button type="button" class="btn action-button action-delete btn-project" data-bs-toggle="modal" data-bs-target="#confirmar-eliminar-inscripcion" data-delete-form="eliminar-inscripcion-{{ $inscripcion->id }}" data-participante="{{ $inscripcion->persona->nombre_completo }}" data-evento="{{ $inscripcion->evento->nombre }}" title="Eliminar" aria-label="Eliminar inscripción de {{ $inscripcion->persona->nombre_completo }}" data-action-style="delete"><i class="fa-solid fa-trash-can" aria-hidden="true"></i><span class="button-label">Eliminar</span></button>
            </form>
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="{{ auth()->user()->isSuperAdmin() ? 6 : 5 }}">
        <div class="empty-state">
            @include('partials.icon', ['name' => 'inscripciones'])
            <h3>{{ $q || request('evento_id') ? 'No hay inscripciones con estos filtros' : 'Haz parte del encuentro' }}</h3>
            @if(auth()->user()->isSuperAdmin())
            <p>Selecciona una persona, un evento y el cargo que desempeñará.</p>
            <a class="btn btn-primary btn-project" href="{{ route('inscripciones.create') }}" data-action-style="save" aria-label="Registrar inscripción" title="Registrar inscripción"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span class="button-label">Registrar inscripción</span></a>
            @endif
        </div>
    </td>
</tr>
@endforelse
</tbody></table></div><div class="panel-pagination">{{ $inscripciones->links() }}</div>
@else
<p class="p-4 mb-0 text-secondary" role="status">Seleccione un evento para mostrar sus participantes inscritos.</p>
@endif
</section>
@if(auth()->user()->isSuperAdmin())
@foreach($inscripciones as $inscripcion)
    @include('inscripciones.detalle', ['inscripcion' => $inscripcion])
@endforeach
<div class="modal fade" data-action-style="delete" id="confirmar-eliminar-inscripcion" tabindex="-1" aria-labelledby="eliminar-inscripcion-titulo" aria-describedby="eliminar-inscripcion-explicacion" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="eliminar-inscripcion-titulo"><i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Eliminar inscripción</h2>
                <button type="button" class="btn-close btn-project" style="filter:none" data-bs-dismiss="modal" aria-label="Cerrar" data-action-style="close" title="Cerrar"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="button-label">Cerrar</span></button>
            </div>
            <div class="modal-body" id="eliminar-inscripcion-explicacion">
                <p>¿Deseas eliminar la inscripción de <strong id="eliminar-inscripcion-participante"></strong> en <strong id="eliminar-inscripcion-evento"></strong>?</p>
                <p>Esta acción no se puede deshacer. Se quitará su inscripción en este evento; sus datos personales, foto e inscripciones en otros eventos se conservarán.</p>
                <p class="mb-0"><strong>Si tiene pagos registrados, no se permitirá eliminar la inscripción.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-project" data-bs-dismiss="modal" data-action-style="cancel" aria-label="Cancelar" title="Cancelar"><i class="fa-solid fa-ban" aria-hidden="true"></i><span class="button-label">Cancelar</span></button>
                <button type="submit" id="confirmar-eliminar-inscripcion-boton" class="btn btn-warning btn-project" disabled data-action-style="delete" aria-label="Sí, eliminar inscripción" title="Sí, eliminar inscripción"><i class="fa-solid fa-trash-can" aria-hidden="true"></i><span class="button-label">Sí, eliminar inscripción</span></button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/person-photo-preview.css') }}?v={{ filemtime(public_path('css/person-photo-preview.css')) }}">
@endpush
@push('scripts')
<script src="{{ asset('js/credenciales-lote-seleccion.js') }}?v={{ filemtime(public_path('js/credenciales-lote-seleccion.js')) }}" defer></script>
<script src="{{ asset('js/person-photo-preview.js') }}?v={{ filemtime(public_path('js/person-photo-preview.js')) }}" defer></script>
@if(auth()->user()->isSuperAdmin())
<script src="{{ asset('js/inscripciones-eliminar.js') }}?v={{ filemtime(public_path('js/inscripciones-eliminar.js')) }}" defer></script>
@endif
<script>
document.getElementById('evento_id').addEventListener('change', () => document.getElementById('filtro-inscripciones').requestSubmit());
</script>
@endpush
