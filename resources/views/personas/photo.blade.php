@if($persona->foto)
    <button type="button" class="person-photo-trigger" data-person-photo-preview aria-label="Ampliar foto de {{ $persona->nombre_completo }}" aria-expanded="false" aria-controls="person-photo-preview">
        <img class="person-photo" src="{{ $persona->foto_url }}" alt="Foto de {{ $persona->nombre_completo }}" width="60" height="60" loading="lazy">
    </button>
@else
    <img class="person-photo" src="{{ $persona->foto_url }}" alt="Sin foto de {{ $persona->nombre_completo }}" width="60" height="60" loading="lazy">
@endif
