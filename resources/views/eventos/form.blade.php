@extends('layouts.app')
@section('section', 'Evento')
@section('title', $evento->exists ? 'Editar evento' : 'Nuevo evento')
@section('subtitle', 'Define los detalles del encuentro y las tarifas del evento.')
@section('actions')<a href="{{ route('eventos.index') }}" class="btn btn-outline-secondary btn-project" data-action-style="back" aria-label="Volver a eventos" title="Volver a eventos"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i><span class="button-label">Volver a eventos</span></a>@endsection
@section('content')
<form method="POST" enctype="multipart/form-data" action="{{ $evento->exists ? route('eventos.update', $evento) : route('eventos.store') }}" class="panel form-panel">
@csrf @if($evento->exists) @method('PUT') @endif
<div class="form-section-heading"><span class="step-number">01</span><div><h2>Información del evento</h2><p>Los campos con * son obligatorios.</p></div></div>
<div class="row">
<x-field class="col-12" name="nombre" label="Nombre del evento" :value="$evento->nombre" :required="true" maxlength="255"/>
<div class="col-12 mb-4">
    <label for="imagen" class="form-label">Imagen del evento</label>
    <div class="event-upload">
        <img id="imagen-preview" src="{{ $evento->imagen_url }}" data-original="{{ $evento->imagen_url }}" alt="Vista previa de la imagen del evento" width="120" height="140">
        <div class="flex-grow-1">
            <input type="file" id="imagen" name="imagen" class="form-control @error('imagen') is-invalid @enderror" accept="image/jpeg,image/png,image/webp,image/gif" aria-describedby="imagen-help">
            @error('imagen')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <p class="form-text" id="imagen-help">Opcional. JPG, PNG, WEBP o GIF. Máximo 10 MB y 12000 × 12000 píxeles. Si no eliges otra imagen, se conserva la actual.</p>
            <p class="text-danger small" id="imagen-client-error" role="alert" hidden></p>
            @if($evento->imagen)
                <div class="form-check mt-2"><input class="form-check-input" type="checkbox" value="1" id="quitar_imagen" name="quitar_imagen" @checked(old('quitar_imagen'))><label for="quitar_imagen" class="form-check-label">Quitar imagen actual</label></div>
            @endif
        </div>
    </div>
</div>
<x-field class="col-md-6" name="fecha_inicio" label="Fecha de inicio" type="date" :value="$evento->fecha_inicio?->format('Y-m-d')" :required="true"/>
<x-field class="col-md-6" name="fecha_final" label="Fecha final" type="date" :value="$evento->fecha_final?->format('Y-m-d')" :required="true"/>
<x-field class="col-md-6" name="lugar" label="Lugar" :value="$evento->lugar" :required="true" maxlength="255"/>
<x-field class="col-md-6" name="ciudad" label="Ciudad" :value="$evento->ciudad" :required="true" maxlength="255"/>
</div>
<div class="form-section-heading mt-4"><span class="step-number">02</span><div><h2>Costo por categoría</h2><p>Las tarifas por categoría se muestran en dólares y en bolivianos según el tipo de cambio.</p></div></div>
<div class="row"><div class="col-md-6 mb-4">
    <label for="tipo_cambio" class="form-label">Tipo de cambio (Bs.- por 1 $us) <span class="required">*</span></label>
    <input type="number" id="tipo_cambio" name="tipo_cambio" min="0.01" max="999999.99" step="0.01" class="form-control @error('tipo_cambio') is-invalid @enderror" value="{{ old('tipo_cambio', $evento->tipo_cambio) }}" @required(!$evento->exists || $evento->tipo_cambio !== null) placeholder="0.00">
    @error('tipo_cambio')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div></div>
@if($evento->exists && $evento->tipo_cambio === null)
<p class="form-text mb-3">Este evento tiene tarifas anteriores en Bs.-. Se conservarán hasta que ingreses el tipo de cambio y los costos en dólares.</p>
@endif
{{-- Conservar los costos personalizados de grados anteriores al guardar el evento. --}}
@error('costos_usd')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
@if($evento->exists && $evento->grados->contains(fn ($grado) => !in_array($grado->nombre, \App\Models\Grado::DETALLADOS, true)))
<p class="form-text">Las tarifas personalizadas anteriores se conservan al guardar.</p>
@endif
@foreach($grados as $grado)
    @php($tarifa = $evento->grados->firstWhere('id', $grado->id)?->pivot)
    @php($costoUsd = in_array($grado->nombre, \App\Models\Grado::DETALLADOS, true) || (!$evento->exists && $grado->categoria()) ? $grado->costoBaseUsd() : ($tarifa?->costo_usd !== null ? number_format((float) $tarifa->costo_usd, 2, '.', '') : ''))
    <input type="hidden" name="costos_usd[{{ $grado->id }}]" value="{{ old('costos_usd.'.$grado->id, $costoUsd) }}">
    @error('costos_usd.'.$grado->id)<div class="alert alert-danger" role="alert">{{ $grado->nombre }}: {{ $message }}</div>@enderror
@endforeach
<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead><tr><th>Categoría</th><th class="text-end">Costo $us</th><th class="text-end">Costo Bs.-</th></tr></thead>
        <tbody>
        @foreach(['Kyu' => '30.00', 'Dan' => '50.00', 'Jefes de Dojo' => '100.00'] as $categoria => $costo)
            <tr data-tarifa-grado>
                <td>{{ $categoria }}</td>
                <td class="text-end">$us {{ number_format((float) $costo, 2, ',', '.') }}<input type="hidden" data-costo-usd value="{{ $costo }}"></td>
                <td class="text-end"><div class="input-group justify-content-end"><span class="input-group-text">Bs.-</span><input data-costo-bs type="text" class="form-control bg-light text-end" style="max-width: 12rem" readonly aria-label="Costo en bolivianos de {{ $categoria }}" aria-live="polite" placeholder="Sin tipo de cambio"></div></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if($grados->isEmpty())<div class="alert alert-warning">Debes <a href="{{ route('grados.index') }}">agregar al menos un grado</a> para configurar las tarifas.</div>@endif
<div class="form-actions"><a href="{{ route('eventos.index') }}" class="btn btn-outline-secondary btn-project" data-action-style="cancel" aria-label="Cancelar" title="Cancelar"><i class="fa-solid fa-ban" aria-hidden="true"></i><span class="button-label">Cancelar</span></a><button class="btn btn-primary btn-project" @disabled($grados->isEmpty()) data-action-style="save" aria-label="{{ $evento->exists ? 'Guardar cambios' : 'Crear evento' }}" title="{{ $evento->exists ? 'Guardar cambios' : 'Crear evento' }}"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span class="button-label">{{ $evento->exists ? 'Guardar cambios' : 'Crear evento' }}</span></button></div>
</form>
@endsection
@push('scripts')
<script src="{{ asset('js/eventos.js') }}?v={{ filemtime(public_path('js/eventos.js')) }}" defer></script>
@endpush
