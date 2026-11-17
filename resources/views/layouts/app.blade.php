<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Gestión de eventos') · Credencial</title>
    <link rel="icon" href="{{ asset('images/icono.png') }}?v={{ hash_file('sha256', public_path('images/icono.png')) }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/notificaciones.css') }}?v={{ filemtime(public_path('css/notificaciones.css')) }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/grados.css') }}?v={{ filemtime(public_path('css/grados.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/botones.css') }}?v={{ filemtime(public_path('css/botones.css')) }}">
</head>
<body>
<div id="app-notifications" class="app-notifications" aria-live="polite" aria-atomic="false">
    @foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $key => $type)
        @if(session($key))<div class="alert alert-{{ $type }}" role="{{ $type === 'danger' ? 'alert' : 'status' }}">{{ session($key) }}</div>@endif
    @endforeach
    @if($errors->any())
        <div class="alert alert-danger" role="alert"><strong>Revisa los siguientes datos:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
</div>
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Menú principal">
        <a href="{{ route(auth()->user()->isSuperAdmin() ? 'dashboard' : 'inscripciones.index') }}" class="brand brand-image" aria-label="Sistema de Control de Inscripciones - Menú principal"><img class="brand-expanded" src="{{ asset('images/Logo.png') }}?v={{ filemtime(public_path('images/Logo.png')) }}" alt="Sistema de Control de Inscripciones" width="210" height="140"><img class="brand-collapsed" src="{{ asset('images/icono.png') }}?v={{ hash_file('sha256', public_path('images/icono.png')) }}" alt="Sistema de Control de Inscripciones" width="56" height="56"></a>
        @if(auth()->user()->isSuperAdmin())
        <a class="nav-link home-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-icon">@include('partials.icon', ['name' => 'home'])</span> Menú principal</a>
        <div class="nav-caption">Administración</div>
        <nav class="nav flex-column gap-2">
            {{-- Catálogo de países ubicado debajo de Persona. --}}
            <a class="nav-link {{ request()->routeIs('paises.*') ? 'active' : '' }}" href="{{ route('paises.index') }}"><span class="nav-icon"><i class="fa-solid fa-earth-americas" aria-hidden="true"></i></span> País <span class="nav-arrow">›</span></a>
            <a class="nav-link {{ request()->routeIs('grados.*') ? 'active' : '' }}" href="{{ route('grados.index') }}"><span class="nav-icon">@include('partials.icon', ['name' => 'grados'])</span> Grado <span class="nav-arrow">›</span></a>
            <a class="nav-link {{ request()->routeIs('eventos.*') ? 'active' : '' }}" href="{{ route('eventos.index') }}"><span class="nav-icon">@include('partials.icon', ['name' => 'eventos'])</span> Evento <span class="nav-arrow">›</span></a>
            <a class="nav-link {{ request()->routeIs('inscripciones.*') ? 'active' : '' }}" href="{{ route('inscripciones.index') }}"><span class="nav-icon">@include('partials.icon', ['name' => 'inscripciones'])</span> Inscripción <span class="nav-arrow">›</span></a>
            <form action="{{ route('cache.clear') }}" method="POST" id="clear-cache-form">
                @csrf
                <button type="submit" class="nav-link cache-button"><span class="nav-icon"><i class="fa-solid fa-broom" aria-hidden="true"></i></span> Limpiar caché</button>
            </form>
            <a class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}"><span class="nav-icon"><i class="fa-solid fa-users"></i></span> Usuarios</a>
        </nav>
        @else
        <nav class="nav flex-column"><a class="nav-link active" href="{{ route('inscripciones.index') }}"><span class="nav-icon"><i class="fa-solid fa-print"></i></span> Impresiones</a></nav>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button class="nav-link cache-button" type="submit"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</button></form>
        <button type="button" id="sidebar-collapse" class="sidebar-collapse" aria-label="Contraer menú" aria-expanded="true" aria-controls="sidebar">«</button>
    </aside>
    <div class="main-shell">
        <header class="topbar">
            <button type="button" class="btn btn-light d-lg-none btn-project" id="menu-toggle" aria-controls="sidebar" aria-expanded="false" aria-label="Abrir menú" data-action-style="menu" title="☰"><i class="fa-solid fa-bars" aria-hidden="true"></i><span class="button-label">☰</span></button>
            <span class="breadcrumb-label">Administración <span>/</span> <strong>@yield('section', 'Persona')</strong></span>
            <div class="topbar-right">
                <span class="today">{{ now()->translatedFormat('d M Y') }}</span>
                <div class="dropdown">
                    <button class="account-badge dropdown-toggle border-0" type="button" id="account-menu" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Abrir menú de usuario">
                        <span class="profile-avatar" aria-hidden="true">
                            @if(auth()->user()->isSuperAdmin())
                                <img src="{{ asset('images/icono.png') }}?v={{ hash_file('sha256', public_path('images/icono.png')) }}" alt="" style="width:100%;height:100%;object-fit:contain;padding:3px">
                            @elseif(auth()->user()->role === 'impresiones')
                                <img src="{{ asset('images/Impresiones.png') }}?v={{ hash_file('sha256', public_path('images/Impresiones.png')) }}" alt="" style="width:100%;height:100%;object-fit:contain;padding:3px">
                            @else
                                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
                            @endif
                        </span>
                        <span class="text-start">{{ auth()->user()->name }}<small>{{ auth()->user()->role }}</small></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="account-menu">
                        <li><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item"><i class="fa-solid fa-right-from-bracket me-2" aria-hidden="true"></i>Cerrar sesión</button></form></li>
                    </ul>
                </div>
            </div>
        </header>
        <main id="main-content">
            <div class="page-heading"><div><h1><span class="heading-icon">@hasSection('heading-icon') @yield('heading-icon') @else @include('partials.icon', ['name' => request()->routeIs('eventos.*') ? 'eventos' : (request()->routeIs('grados.*') ? 'grados' : (request()->routeIs('inscripciones.*') ? 'inscripciones' : (request()->routeIs('dashboard') ? 'home' : 'personas')))]) @endif</span>@yield('title')</h1></div><div class="page-actions">@yield('actions')</div></div>
            @yield('content')
        </main>
        <footer class="content-footer">
            <span>© {{ date('Y') }} Credencial. Todos los derechos reservados.</span>
            <span>Walter Landivar Limpias  <i class="fa-solid fa-mobile-screen"></i> 72841511 <i class="fa-brands fa-whatsapp"></i></span>
            <span>Gestión de eventos</span>
        </footer>
    </div>
</div>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/modal-colores.js') }}?v={{ filemtime(public_path('js/modal-colores.js')) }}" defer></script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
