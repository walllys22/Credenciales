@extends('layouts.app')
{{-- Catálogo de países con dos columnas y orden alfabético. --}}
@section('section', 'País')
@section('title', 'Países')
@section('heading-icon')<i class="fa-solid fa-earth-americas" aria-hidden="true"></i>@endsection
@section('actions')
    <a href="{{ route('paises.create') }}" class="btn btn-primary btn-project" data-action-style="create" aria-label="Nuevo país" title="Nuevo país"><i class="fa-solid fa-plus" aria-hidden="true"></i><span class="button-label">Nuevo país</span></a>
@endsection
@section('content')
<section class="panel">
    <div class="panel-toolbar">
        <div><h2>Catálogo de países</h2><p>{{ $paises->total() }} países · Orden alfabético</p></div>
        <form method="GET" class="search-form" role="search">
            <label for="q" class="visually-hidden">Buscar país</label>
            <input id="q" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por nombre…">
            <button class="btn btn-outline-secondary btn-project" data-action-style="search" aria-label="Buscar" title="Buscar"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span class="button-label">Buscar</span></button>
            @if($q !== '')<a href="{{ route('paises.index') }}" class="btn btn-light btn-project" data-action-style="reset" aria-label="Limpiar" title="Limpiar"><i class="fa-solid fa-broom" aria-hidden="true"></i><span class="button-label">Limpiar</span></a>@endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th scope="col">ID</th><th scope="col">Nombre</th></tr></thead>
            <tbody>
                @forelse($paises as $pais)
                    <tr>
                        <td>{{ $pais->id }}</td>
                        <td><div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                            <strong>{{ $pais->nombre }}</strong>
                            <div class="table-actions">
                                <a href="{{ route('paises.edit', $pais) }}" class="btn btn-sm btn-edit btn-project" aria-label="Editar país {{ $pais->nombre }}" data-action-style="edit" title="Editar"><i class="fa-solid fa-pen" aria-hidden="true"></i><span class="button-label">Editar</span></a>
                                @include('partials.delete', ['action' => route('paises.destroy', $pais), 'label' => 'país '.$pais->nombre])
                            </div>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="2">No hay países registrados con estos criterios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-pagination">{{ $paises->links() }}</div>
</section>
@endsection
