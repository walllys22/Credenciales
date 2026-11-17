@extends('layouts.app')
@section('title', 'Usuarios')
@section('section', 'Usuarios')
@section('actions')<a class="btn btn-create btn-project" href="{{ route('usuarios.create') }}" data-action-style="create" aria-label="Crear usuario" title="Crear usuario"><i class="fa-solid fa-plus" aria-hidden="true"></i><span class="button-label">Crear usuario</span></a>@endsection
@section('content')
<section class="panel"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
@foreach($usuarios as $usuario)<tr><td>{{ $usuario->name }}</td><td>{{ $usuario->username }}</td><td>{{ $usuario->email }}</td><td>{{ $usuario->role }}</td><td>{{ $usuario->active ? 'Activo' : 'Inactivo' }}</td><td><div class="table-actions"><a class="btn action-button action-edit btn-project" href="{{ route('usuarios.edit', $usuario) }}" aria-label="Editar {{ $usuario->name }}" data-action-style="edit" title="Editar {{ $usuario->name }}"><i class="fa-solid fa-pen" aria-hidden="true"></i><span class="button-label">Editar {{ $usuario->name }}</span></a>
@if($usuario->id !== auth()->id())<form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" data-confirm="¿Eliminar este usuario?">@csrf @method('DELETE')<button class="btn action-button action-delete btn-project" aria-label="Eliminar {{ $usuario->name }}" data-action-style="delete" title="Eliminar {{ $usuario->name }}"><i class="fa-solid fa-trash-can" aria-hidden="true"></i><span class="button-label">Eliminar {{ $usuario->name }}</span></button></form>@endif
</div></td></tr>@endforeach
</tbody></table></div>{{ $usuarios->links() }}</section>
@endsection
