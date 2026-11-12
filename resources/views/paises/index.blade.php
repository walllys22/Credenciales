@extends('layouts.app')
{{-- Catálogo de países con dos columnas y orden alfabético. --}}
@section('section', 'País')
@section('title', 'Países')
@section('heading-icon')<i class="fa-solid fa-earth-americas" aria-hidden="true"></i>@endsection
@section('actions')
    <a href="{{ route('paises.create') }}" class="btn btn-primary">＋ Nuevo país</a>
@endsection
@section('content')
<section class="panel">
    <div class="panel-toolbar">
        <div><h2>Catálogo de países</h2><p>{{ $paises->total() }} países · Orden alfabético</p></div>
        <form method="GET" class="search-form" role="search">
            <label for="q" class="visually-hidden">Buscar país</label>
            <input id="q" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por nombre…">
            <button class="btn btn-outline-secondary">Buscar</button>
            @if($q !== '')<a href="{{ route('paises.index') }}" class="btn btn-light">Limpiar</a>@endif
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
                                <a href="{{ route('paises.edit', $pais) }}" class="btn btn-sm btn-edit" aria-label="Editar país {{ $pais->nombre }}">Editar</a>
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
