@extends('layouts.app')
@section('section', 'Grado')
@section('title', 'Grados')
@section('subtitle', 'Administra los grados disponibles para las personas y las tarifas de tus eventos.')
@section('actions')
    <a href="{{ route('grados.create') }}" class="btn btn-primary btn-project" data-action-style="create" aria-label="Nuevo grado" title="Nuevo grado"><i class="fa-solid fa-plus" aria-hidden="true"></i><span class="button-label">Nuevo grado</span></a>
@endsection

@section('content')
<section class="panel">
    <div class="panel-toolbar">
        <div>
            <h2>Catálogo de grados</h2>
            <p>{{ $grados->total() }} {{ $grados->total() === 1 ? 'grado' : 'grados' }} {{ $q !== '' ? 'encontrados' : 'registrados' }}</p>
        </div>
        <form method="GET" class="search-form" role="search">
            <label for="q" class="visually-hidden">Buscar grado por nombre</label>
            <input id="q" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por nombre…">
            <button class="btn btn-outline-secondary btn-project" data-action-style="search" aria-label="Buscar" title="Buscar"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span class="button-label">Buscar</span></button>
            @if($q !== '')
                <a href="{{ route('grados.index') }}" class="btn btn-light btn-project" data-action-style="reset" aria-label="Limpiar" title="Limpiar"><i class="fa-solid fa-broom" aria-hidden="true"></i><span class="button-label">Limpiar</span></a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr><th scope="col">ID</th><th scope="col">Nombre</th><th scope="col" class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
                @forelse($grados as $grado)
                    <tr>
                        <td>{{ $grado->id }}</td>
                        <td><strong>{{ $grado->nombre }}</strong></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('grados.edit', $grado) }}" class="btn btn-sm btn-edit btn-project" aria-label="Editar grado {{ $grado->nombre }}" data-action-style="edit" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i><span class="button-label">Editar</span></a>
                            @include('partials.delete', ['action' => route('grados.destroy', $grado), 'label' => 'grado '.$grado->nombre])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">
                        <div class="empty-state">
                            @include('partials.icon', ['name' => 'grados'])
                            <h3>{{ $q !== '' ? 'No encontramos grados' : 'Agrega tu primer grado' }}</h3>
                            <p>{{ $q !== '' ? 'Prueba con otro nombre o limpia la búsqueda.' : 'Los grados estarán disponibles al registrar personas y configurar eventos.' }}</p>
                            <a href="{{ route('grados.create') }}" class="btn btn-primary btn-project" data-action-style="create" aria-label="Crear grado" title="Crear grado"><i class="fa-solid fa-plus" aria-hidden="true"></i><span class="button-label">Crear grado</span></a>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-pagination">{{ $grados->links() }}</div>
</section>
@endsection
