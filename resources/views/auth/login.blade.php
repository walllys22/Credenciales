<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar · Credencial</title>
    <link rel="icon" href="{{ asset('images/icono.png') }}?v={{ hash_file('sha256', public_path('images/icono.png')) }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ filemtime(public_path('css/login.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}?v={{ filemtime(public_path('css/botones.css')) }}">
</head>
<body>
<main class="login-layout">
    <section class="login-art" aria-label="Sistema de Control de Inscripciones">
        <img src="{{ asset('images/Login.png') }}?v={{ filemtime(public_path('images/Login.png')) }}" alt="Sistema de Control de Inscripciones: credenciales con QR, impresión automática y certificados">
    </section>
    <section class="login-panel" aria-labelledby="login-title">
        <div class="login-card">
            <header class="login-brand">
                <img src="{{ asset('images/icono.png') }}?v={{ hash_file('sha256', public_path('images/icono.png')) }}" alt="Icono de Control de Inscripciones" width="80" height="54">
                <div><h1 id="login-title">Credencial</h1><p>Ingresa a tu panel administrativo</p></div>
            </header>
            @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="login-field">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input id="email" type="email" name="email" class="form-control" value="{{ old('email') }}" autocomplete="username" required autofocus>
                </div>
                <div class="login-field">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="position-relative">
                        <input id="password" type="password" name="password" class="form-control pe-5" autocomplete="current-password" required>
                        <button id="toggle-password" type="button" class="btn position-absolute top-0 end-0 h-100 px-3 border-0" aria-label="Mostrar contraseña" aria-controls="password" aria-pressed="false" title="Mostrar contraseña">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path id="password-eye-slash" d="m3 3 18 18" style="display:none"/></svg>
                        </button>
                    </div>
                </div>
                <div class="form-check login-remember"><input id="remember" name="remember" type="checkbox" class="form-check-input" value="1" @checked(old('remember'))><label for="remember" class="form-check-label">Mantener sesión iniciada</label></div>
                <button class="btn login-submit w-100 btn-project" type="submit" data-action-style="login" aria-label="Ingresar" title="Ingresar"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i><span class="button-label">Ingresar</span></button>
            </form>
        </div>
    </section>
</main>
<script src="{{ asset('js/password-toggle.js') }}?v={{ filemtime(public_path('js/password-toggle.js')) }}" defer></script>
</body>
</html>
