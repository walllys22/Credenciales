@extends('layouts.app')
@section('section', 'Evento')
@section('title', 'Eventos')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/eventos-modal.css') }}?v={{ filemtime(public_path('css/eventos-modal.css')) }}">
@endpush
@section('actions')
    <a href="{{ route('eventos.create') }}" class="btn btn-create btn-project" data-action-style="create" aria-label="Crear" title="Crear"><i class="fa-solid fa-plus" aria-hidden="true"></i><span class="button-label">Crear</span></a>
    <a href="{{ route('dashboard') }}" class="btn btn-close-page btn-project" data-action-style="close" aria-label="Cerrar" title="Cerrar"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="button-label">Cerrar</span></a>
@endsection
@section('content')
<section class="panel">
    <form method="GET" class="event-filters" role="search">
        <div><label for="per_page" class="form-label">Registros</label><select id="per_page" name="per_page" class="form-select">@foreach([10, 25, 50, 100] as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>@endforeach</select></div>
        <div><label for="estado" class="form-label">Estado</label><select id="estado" name="estado" class="form-select"><option value="">Todos</option><option value="proximo" @selected($estado === 'proximo')>Próximos</option><option value="en_curso" @selected($estado === 'en_curso')>En curso</option><option value="finalizado" @selected($estado === 'finalizado')>Finalizados</option></select></div>
        <div class="filter-search"><label for="q" class="form-label">Buscar</label><input id="q" name="q" value="{{ $q }}" class="form-control" placeholder="Evento, ciudad o lugar…"></div>
        <div class="filter-submit"><button class="btn btn-primary btn-project" data-action-style="search" aria-label="Buscar" title="Buscar"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span class="button-label">Buscar</span></button>@if($q !== '' || $estado !== '' || $perPage !== 10)<a class="btn btn-outline-secondary btn-project" href="{{ route('eventos.index') }}" data-action-style="reset" aria-label="Limpiar" title="Limpiar"><i class="fa-solid fa-broom" aria-hidden="true"></i><span class="button-label">Limpiar</span></a>@endif</div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle event-table">
            <thead><tr><th scope="col">Evento</th><th scope="col">Ciudad</th><th scope="col">Fechas</th><th scope="col">Costo por categoría</th><th scope="col" class="text-center">Acciones</th></tr></thead>
            <tbody>
            @forelse($eventos as $evento)
                <tr>
                    <td><div class="event-name"><img class="event-thumbnail" src="{{ $evento->imagen_url }}" alt="Imagen de {{ $evento->nombre }}" width="58" height="74" loading="lazy"><strong>{{ $evento->nombre }}</strong></div></td>
                    <td>{{ $evento->ciudad }}<small class="d-block">{{ $evento->lugar }}</small></td>
                    <td class="dates-cell">{{ $evento->fecha_inicio->format('d/m/Y') }}<br>al {{ $evento->fecha_final->format('d/m/Y') }}</td>
                    <td class="tariff-cell">
                        @foreach($evento->costos_agrupados as $costo)
                            <div><small>{{ $costo['nombre'] }}:</small> {{ $costo['usd'] !== null ? '$us '.number_format((float) $costo['usd'], 2, ',', '.') : 'Sin registrar en $us' }}</div>
                        @endforeach
                        <small class="d-block mt-1">Tipo de cambio: {{ $evento->tipo_cambio !== null ? 'Bs '.number_format((float) $evento->tipo_cambio, 2, ',', '.').' por 1 $us' : 'Sin registrar' }}</small>
                        <small class="d-block mt-1">{{ $evento->inscripciones_count }} inscripciones</small>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button type="button" class="btn action-button action-view btn-project" data-bs-toggle="modal" data-bs-target="#evento-{{ $evento->id }}" title="Ver evento" aria-label="Ver evento {{ $evento->nombre }}" data-action-style="view"><i class="fa-solid fa-eye" aria-hidden="true"></i><span class="button-label">Ver evento</span></button>
                            <a class="btn action-button action-edit btn-project" href="{{ route('eventos.edit', $evento) }}" title="Editar evento" aria-label="Editar evento {{ $evento->nombre }}" data-action-style="edit"><i class="fa-solid fa-pen" aria-hidden="true"></i><span class="button-label">Editar evento</span></a>
                            <form action="{{ route('eventos.destroy', $evento) }}" method="POST" data-confirm="¿Eliminar el evento {{ $evento->nombre }}? Esta acción no se puede deshacer.">@csrf @method('DELETE')<button class="btn action-button action-delete btn-project" title="Eliminar evento" aria-label="Eliminar evento {{ $evento->nombre }}" data-action-style="delete"><i class="fa-solid fa-trash-can" aria-hidden="true"></i><span class="button-label">Eliminar evento</span></button></form>
                            <a class="btn action-button action-inscriptions btn-project" href="{{ route('inscripciones.index', ['evento_id' => $evento->id]) }}" title="Ver inscripciones" aria-label="Ver inscripciones de {{ $evento->nombre }}" data-action-style="registrations"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i><span class="button-label">Ver inscripciones</span></a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-5 text-secondary">{{ $q !== '' || $estado !== '' ? 'No hay eventos que coincidan con los filtros.' : 'No hay eventos registrados.' }} <a href="{{ route('eventos.create') }}">Crear evento</a></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-pagination"><p class="mb-0">{{ $eventos->total() }} evento(s) registrados.</p>@if($eventos->hasPages())<div class="mt-3">{{ $eventos->links() }}</div>@endif</div>
</section>
@foreach($eventos as $evento)
    <div class="modal fade event-info-modal" data-action-style="view" id="evento-{{ $evento->id }}" tabindex="-1" aria-labelledby="evento-title-{{ $evento->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h2 class="modal-title">Datos del evento</h2></div>
            <div class="modal-body">
                <div class="event-compact-header">
                    <img class="event-detail-image" src="{{ $evento->imagen_url }}" alt="Imagen de {{ $evento->nombre }}" loading="lazy">
                    <div class="event-compact-heading">
                        <h2 class="event-detail-title" id="evento-title-{{ $evento->id }}">{{ $evento->nombre }}</h2>
                        <p class="text-secondary">{{ $evento->inscripciones_count }} inscripciones</p>
                        <dl class="event-compact-dates">
                            <div><dt>ID</dt><dd>{{ $evento->id }}</dd></div>
                            <div><dt>Inicio</dt><dd>{{ $evento->fecha_inicio->format('d/m/Y') }}</dd></div>
                            <div><dt>Final</dt><dd>{{ $evento->fecha_final->format('d/m/Y') }}</dd></div>
                        </dl>
                    </div>
                </div>
                <dl class="event-compact-location">
                    <div><dt>Lugar</dt><dd>{{ $evento->lugar }}</dd></div>
                    <div><dt>Ciudad</dt><dd>{{ $evento->ciudad }}</dd></div>
                    <div class="event-compact-exchange"><dt>Tipo de cambio</dt><dd>{{ $evento->tipo_cambio !== null ? 'Bs.- '.number_format((float) $evento->tipo_cambio, 2, ',', '.').' por 1 $us' : 'Sin registrar' }}</dd></div>
                </dl>
                <h3 class="h6 event-compact-cost-title">Costo por categoría</h3>
                <div class="overflow-auto">
                    <table class="table table-bordered align-middle">
                        <thead><tr><th scope="col">Categoría</th><th scope="col" class="text-end">Costo $us</th><th scope="col" class="text-end">Costo Bs.-</th></tr></thead>
                        <tbody>
                            @forelse($evento->costos_agrupados as $costo)
                                <tr><td>{{ $costo['nombre'] }}</td><td class="text-end">{{ $costo['usd'] !== null ? '$us '.number_format((float) $costo['usd'], 2, ',', '.') : 'Sin registrar' }}</td><td class="text-end"><strong>{{ $costo['bs'] !== null ? 'Bs.- '.number_format((float) $costo['bs'], 2, ',', '.') : 'Sin registrar' }}</strong></td></tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-secondary">No hay tarifas configuradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-project" data-bs-dismiss="modal" data-action-style="close" aria-label="Cerrar" title="Cerrar"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="button-label">Cerrar</span></button><a href="{{ route('eventos.edit', $evento) }}" class="btn btn-primary btn-project" data-action-style="edit" aria-label="Editar evento" title="Editar evento"><i class="fa-solid fa-pen" aria-hidden="true"></i><span class="button-label">Editar evento</span></a></div>
        </div></div>
    </div>
@endforeach
@endsection
