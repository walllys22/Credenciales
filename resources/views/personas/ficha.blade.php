<article class="credential-card credential-file" @if($zoom ?? false) data-credential-zoom role="button" tabindex="0" aria-label="Ampliar ficha de la inscripción" aria-expanded="false" aria-controls="credential-zoom" @endif>
    <img class="credential-template" src="{{ asset('images/Ficha1.png') }}?v={{ filemtime(public_path('images/Ficha1.png')) }}" alt="" aria-hidden="true">
    <div class="credential-card-content">
        <div class="credential-file-heading"><img class="credential-photo" src="{{ $fotoUrl ?? $persona->foto_url }}" alt="Foto de {{ $persona->nombre_completo }}"><p class="credential-event-title">{{ $evento->nombre }}</p></div>
        <div class="credential-document">CI: {{ $persona->documento_identidad }}</div>
        <dl class="credential-data"><div><dt>Nombre:</dt><dd>{{ $persona->nombre_completo }}</dd></div><div><dt>Grado:</dt><dd>{{ $persona->grado->nombre }}</dd></div><div><dt>Cargo:</dt><dd>{{ $inscripcion->cargo }}</dd></div><div><dt>Teléfono:</dt><dd>{{ $persona->telefono }}</dd></div><div><dt>Fechas:</dt><dd>{{ $evento->fecha_inicio->format('d/m/Y') }} al {{ $evento->fecha_final->format('d/m/Y') }}</dd></div></dl>
    </div>
    <div class="credential-bottom"><img src="{{ asset('images/Logo.png') }}?v={{ filemtime(public_path('images/Logo.png')) }}" alt="Control de Inscripciones"><span>#{{ str_pad($inscripcion->id, 6, '0', STR_PAD_LEFT) }}</span></div>
</article>
