<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Ficha · {{ $persona->nombre_completo }}</title>
    <link rel="icon" href="{{ asset('images/icono.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/credenciales.css') }}?v={{ filemtime(public_path('css/credenciales.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/credencial-movil.css') }}?v={{ filemtime(public_path('css/credencial-movil.css')) }}">
</head>
<body>
    <main class="mobile-ficha" aria-label="Ficha de {{ $persona->nombre_completo }}">
        @include('personas.ficha')
        {{-- Botón centrado debajo de la ficha, disponible solo en la vista móvil de verificación. --}}
        <div class="mobile-ficha-actions">
            <button type="button" class="mobile-ficha-close" id="close-mobile-ficha">Cerrar</button>
        </div>
    </main>
    <script>
        // Cierra la pestaña cuando el navegador lo permite; de lo contrario sale de la ficha.
        document.getElementById('close-mobile-ficha').addEventListener('click', () => {
            window.close();
            if (!window.closed) {
                if (window.history.length > 1) window.history.back();
                else window.location.replace('about:blank');
            }
        });
    </script>
</body>
</html>
