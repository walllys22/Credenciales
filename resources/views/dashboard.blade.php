@extends('layouts.app')
@section('section', 'Menú principal')
@section('title', 'Dashboard')
@section('content')
<div class="row g-4 mb-4">
    @foreach([['label' => 'Personas', 'total' => $personas, 'route' => 'personas.index', 'icon' => 'personas'], ['label' => 'Grados', 'total' => $grados, 'route' => 'grados.index', 'icon' => 'grados'], ['label' => 'Eventos', 'total' => $eventos, 'route' => 'eventos.index', 'icon' => 'eventos'], ['label' => 'Inscripciones', 'total' => $inscripciones, 'route' => 'inscripciones.index', 'icon' => 'inscripciones']] as $item)
        <div class="col-sm-6 col-xl-3"><a class="dashboard-stat panel" href="{{ route($item['route']) }}"><span class="stat-icon">@include('partials.icon', ['name' => $item['icon']])</span><span><strong>{{ $item['total'] }}</strong><span>{{ $item['label'] }}</span></span></a></div>
    @endforeach
</div>
<section class="panel">
    <div class="panel-toolbar"><h2>Eventos próximos y en curso</h2><a class="btn btn-primary" href="{{ route('eventos.index') }}">Ver eventos</a></div>
    <div class="table-responsive"><table class="table align-middle dashboard-table"><thead><tr><th>Evento</th><th>Ciudad</th><th>Fechas</th><th>Inscripciones</th></tr></thead><tbody>
    @forelse($proximos as $evento)
        <tr><td><div class="event-name"><img class="event-thumbnail" src="{{ $evento->imagen_url }}" alt="Imagen de {{ $evento->nombre }}" loading="lazy"><a href="{{ route('eventos.edit', $evento) }}">{{ $evento->nombre }}</a></div></td><td>{{ $evento->ciudad }}</td><td>{{ $evento->fecha_inicio->format('d/m/Y') }} al {{ $evento->fecha_final->format('d/m/Y') }}</td><td><a href="{{ route('inscripciones.index', ['evento_id' => $evento->id]) }}">{{ $evento->inscripciones_count }} inscritos</a></td></tr>
    @empty
        <tr><td colspan="4" class="text-center py-5 text-secondary">No hay eventos próximos. <a href="{{ route('eventos.create') }}">Crear un evento</a></td></tr>
    @endforelse
    </tbody></table></div>
</section>
@endsection
