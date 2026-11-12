@extends('layouts.app')
@section('title', $usuario->exists ? 'Editar usuario' : 'Crear usuario')
@section('section', 'Usuarios')
@section('actions')<a href="{{ route('usuarios.index') }}" class="btn btn-close-page">Cerrar</a>@endsection
@section('content')
<form class="panel" method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}">@csrf @if($usuario->exists) @method('PUT') @endif
<div class="row g-3">
@foreach(['name' => 'Nombre', 'username' => 'Usuario', 'email' => 'Correo electrónico'] as $field => $label)<div class="col-md-6"><label for="{{ $field }}" class="form-label">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" class="form-control" value="{{ old($field, $usuario->$field) }}" required></div>@endforeach
<div class="col-md-6"><label for="role" class="form-label">Rol</label><select id="role" name="role" class="form-select">@foreach(['superadmin' => 'Superadmin — acceso completo', 'impresiones' => 'Impresiones — certificados y credenciales'] as $value => $label)<option value="{{ $value }}" @selected(old('role', $usuario->role) === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-6"><label for="active" class="form-label">Estado</label><select id="active" name="active" class="form-select"><option value="1" @selected(old('active', $usuario->active))>Activo</option><option value="0" @selected(!old('active', $usuario->active))>Inactivo</option></select></div>
<div class="col-md-6"><label for="password" class="form-label">{{ $usuario->exists ? 'Nueva contraseña (opcional)' : 'Contraseña' }}</label><input id="password" name="password" type="password" class="form-control" autocomplete="new-password" minlength="8" @required(!$usuario->exists)></div>
<div class="col-md-6"><label for="password_confirmation" class="form-label">Confirmar contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" @required(!$usuario->exists)></div>
</div><button class="btn btn-create mt-4">Guardar</button></form>
@endsection
