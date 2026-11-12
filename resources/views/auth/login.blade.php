<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Ingresar · Credencial</title><link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}"></head>
<body class="bg-light"><main class="container py-5" style="max-width:460px"><div class="card shadow-sm p-4"><img src="{{ asset('images/Logo.png') }}" alt="Control de Inscripciones" class="img-fluid mb-3"><h1 class="h4">Iniciar sesión</h1>
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('login.store') }}">@csrf
<label for="username" class="form-label">Usuario</label><input id="username" name="username" class="form-control mb-3" value="{{ old('username') }}" autocomplete="username" required autofocus>
<label for="password" class="form-label">Contraseña</label><input id="password" type="password" name="password" class="form-control mb-3" autocomplete="current-password" required>
<button class="btn btn-primary w-100">Ingresar</button></form></div></main></body></html>
