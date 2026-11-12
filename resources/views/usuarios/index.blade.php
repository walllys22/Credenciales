@extends('layouts.app')
@section('title', 'Usuarios')
@section('section', 'Usuarios')
@section('actions')<a class="btn btn-create" href="{{ route('usuarios.create') }}">+ Crear usuario</a>@endsection
@section('content')
<section class="panel"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
@foreach($usuarios as $usuario)<tr><td>{{ $usuario->name }}</td><td>{{ $usuario->username }}</td><td>{{ $usuario->email }}</td><td>{{ $usuario->role }}</td><td>{{ $usuario->active ? 'Activo' : 'Inactivo' }}</td><td><div class="table-actions"><a class="btn action-button action-edit" href="{{ route('usuarios.edit', $usuario) }}" aria-label="Editar {{ $usuario->name }}"><i class="fa-solid fa-pen"></i></a>
@if($usuario->id !== auth()->id())<form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" data-confirm="¿Eliminar este usuario?">@csrf @method('DELETE')<button class="btn action-button action-delete" aria-label="Eliminar {{ $usuario->name }}"><i class="fa-solid fa-trash-can"></i></button></form>@endif
</div></td></tr>@endforeach
</tbody></table></div>{{ $usuarios->links() }}</section>
@endsection
