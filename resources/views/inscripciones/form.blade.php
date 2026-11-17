@extends('layouts.app')
@section('section', 'Inscripción')
@section('title', $inscripcion->exists ? 'Editar inscripción' : 'Nueva inscripción')
@section('subtitle', 'Registra los datos personales, la foto y la inscripción en un solo paso.')
@section('actions')<a href="{{ route('inscripciones.index') }}" class="btn btn-outline-secondary btn-project" data-action-style="back" aria-label="Volver a inscripciones" title="Volver a inscripciones"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i><span class="button-label">Volver a inscripciones</span></a>@endsection
@section('content')
<form enctype="multipart/form-data" method="POST" action="{{ $inscripcion->exists ? route('inscripciones.update', $inscripcion) : route('inscripciones.store') }}" class="panel form-panel">
@csrf @if($inscripcion->exists) @method('PUT') @endif
<input type="hidden" name="registro_integrado" value="1">
@if($inscripcion->exists)
<input type="hidden" id="persona_id" name="persona_id" value="{{ $inscripcion->persona_id }}">
<p class="form-text">Los datos personales y la foto son compartidos por las inscripciones de este participante.</p>
@else
<input type="hidden" id="persona_id" name="persona_id" value="{{ $persona->exists ? $persona->id : '' }}">
@endif
@error('persona_id')<div class="text-danger mb-3">{{ $message }}</div>@enderror
@include('personas.datos')
<div class="row align-items-center mt-4 mb-4">
    <div class="col-md-6"><div class="form-section-heading mb-0"><span class="step-number">03</span><div><h2>Datos de la inscripción</h2><p>Elige el evento y el cargo del participante.</p></div></div></div>
    <div class="col-md-6">
        <label for="pais_id" class="form-label">País del participante</label>
        <select id="pais_id" name="pais_id" class="form-select @error('pais_id') is-invalid @enderror">
            <option value="">Seleccionar país</option>
            @foreach($paises as $pais)<option value="{{ $pais->id }}" @selected(old('pais_id', $persona->pais_id) == $pais->id)>{{ $pais->nombre }}</option>@endforeach
        </select>
        @error('pais_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<div class="mb-4"><label for="evento_id" class="form-label">Evento <span class="required">*</span></label><select class="form-select @error('evento_id') is-invalid @enderror" name="evento_id" id="evento_id" required><option value="">Seleccionar evento</option>@foreach($eventos as $evento)<option value="{{ $evento->id }}" @selected($eventoSeleccionado == $evento->id)>{{ $evento->nombre }} · {{ $evento->fecha_inicio->format('d/m/Y') }} · {{ $evento->ciudad }}</option>@endforeach</select>@error('evento_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<x-field name="cargo" label="Cargo" :value="$inscripcion->cargo" :required="true" maxlength="120"/>
<p class="form-text">Por ejemplo: participante, expositor, coordinador u organizador.</p>
<div class="row">
<div class="col-md-6 mb-4">
    <label for="jefe_dojo" class="form-label">Jefe de Dojo</label>
    <select id="jefe_dojo" name="jefe_dojo" class="form-select @error('jefe_dojo') is-invalid @enderror" required>
        <option value="0" @selected(!old('jefe_dojo', $inscripcion->jefe_dojo))>No</option>
        <option value="1" @selected(old('jefe_dojo', $inscripcion->jefe_dojo))>Sí</option>
    </select>
    <p class="form-text">Dan y Jefe de Dojo: $us 100. Kyu: $us 30; Dan sin jefatura: $us 50. Se convierte al tipo de cambio del evento.</p>
    @error('jefe_dojo')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-6 mb-4">
    <div class="form-label">Invitado sin costo</div>
    <input type="hidden" name="invitado_sin_costo" value="0">
    <div class="form-check mt-3">
        <input id="invitado_sin_costo" name="invitado_sin_costo" type="checkbox" value="1" class="form-check-input @error('invitado_sin_costo') is-invalid @enderror" @checked(old('invitado_sin_costo', $inscripcion->invitado_sin_costo))>
        <label for="invitado_sin_costo" class="form-check-label">Invitado sin costo</label>
        @error('invitado_sin_costo')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <p class="form-text">Inscripción gratuita, incluso si es Jefe de Dojo.</p>
</div>
</div>
<div class="mb-4">
    <label for="observaciones" class="form-label">Observaciones</label>
    <textarea id="observaciones" class="form-control" rows="1" readonly>{{ old('invitado_sin_costo', $inscripcion->invitado_sin_costo) ? 'invitado sin costo' : '' }}</textarea>
</div>
<div class="tariff-preview" role="status" aria-live="polite"><div><span class="eyebrow">TARIFA VIGENTE</span><p id="tariff-message">Selecciona un evento y una persona para consultar el costo.</p></div><strong id="tariff-amount">—</strong></div>
@if($eventos->isEmpty())<div class="alert alert-warning mt-3">Necesitas <a href="{{ route('eventos.create') }}">crear un evento</a> antes de inscribir participantes.</div>@endif
<div class="form-actions"><a href="{{ route('inscripciones.index') }}" class="btn btn-outline-secondary btn-project" data-action-style="cancel" aria-label="Cancelar" title="Cancelar"><i class="fa-solid fa-ban" aria-hidden="true"></i><span class="button-label">Cancelar</span></a><button id="guardar-inscripcion" class="btn btn-primary btn-project" @disabled($eventos->isEmpty() || $grados->isEmpty()) data-action-style="save" aria-label="{{ $inscripcion->exists ? 'Guardar cambios' : 'Registrar inscripción' }}" title="{{ $inscripcion->exists ? 'Guardar cambios' : 'Registrar inscripción' }}"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span class="button-label">{{ $inscripcion->exists ? 'Guardar cambios' : 'Registrar inscripción' }}</span></button></div>
</form>
@endsection
@push('scripts')
@php($tarifas = $eventos->mapWithKeys(fn ($evento) => [$evento->id => $evento->grados->mapWithKeys(fn ($grado) => [$grado->id => $grado->pivot->costo])]))
@php($tarifasJefe = $eventos->mapWithKeys(fn ($evento) => [$evento->id => $evento->tipo_cambio ? (string) Brick\Math\BigDecimal::of('100')->multipliedBy($evento->tipo_cambio)->toScale(2, Brick\Math\RoundingMode::HalfUp) : null]))
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tarifas = {{ Illuminate\Support\Js::from($tarifas) }};
    const tarifasJefe = {{ Illuminate\Support\Js::from($tarifasJefe) }};
    const gradosDan = {{ Illuminate\Support\Js::from($grados->filter(fn ($grado) => $grado->esDan())->pluck('id')) }};
    const jefeDojo = document.getElementById('jefe_dojo');
    const invitado = document.getElementById('invitado_sin_costo');
    const evento = document.getElementById('evento_id');
    const persona = document.getElementById('persona_id');
    const opcionesPersonas = {{ Illuminate\Support\Js::from($opcionesPersonas) }};
    const guardar = document.getElementById('guardar-inscripcion');
    const grado = document.getElementById('grado_id');
    const foto = document.getElementById('foto');
    const preview = document.getElementById('foto-preview');
    const datos = ['primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'documento_identidad', 'sexo', 'telefono', 'grado_id', 'pais_id', 'organizacion'];
    function cargarPersona() {
        const opcion = opcionesPersonas.find(opcion => String(opcion.id) === persona.value);
        datos.forEach(campo => { document.getElementById(campo).value = opcion?.datos[campo] ?? ''; });
        foto.value = '';
        foto.setCustomValidity('');
        document.getElementById('foto-client-error').hidden = true;
        foto.required = !opcion && !foto.dataset.defaultFile;
        preview.src = opcion?.foto_url || '{{ asset('images/default.jpg') }}';
        preview.dataset.original = preview.src;
        actualizarTarifa();
    }
    function actualizarTarifa() {
        const esJefe = jefeDojo.value === '1' && gradosDan.some(id => String(id) === grado.value);
        const opcion = opcionesPersonas.find(opcion => String(opcion.id) === persona.value);
        const duplicado = opcion?.eventos_inscritos.some(id => String(id) === evento.value);
        document.getElementById('observaciones').value = invitado.checked ? 'invitado sin costo' : '';
        const listo = evento.value && grado.value;
        const costo = !listo ? null : invitado.checked ? '0.00' : esJefe ? tarifasJefe[evento.value] : tarifas[evento.value]?.[grado.value];
        document.getElementById('tariff-message').textContent = duplicado ? 'Este participante ya está inscrito en el evento seleccionado.' : !listo ? 'Selecciona un evento y un grado para consultar el costo.' : invitado.checked ? 'Invitado sin costo. No corresponde realizar ningún pago.' : costo == null ? (esJefe ? 'Configura el tipo de cambio del evento para aplicar la tarifa de $us 100.' : 'Este evento no tiene una tarifa para el grado de la persona.') : esJefe ? 'Tarifa de Jefe de Dojo: $us 100 al tipo de cambio del evento.' : 'Costo según el grado del participante. No representa un pago realizado.';
        document.getElementById('tariff-amount').textContent = costo == null ? '—' : 'Bs ' + Number(costo).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        guardar.disabled = Boolean(duplicado) || evento.options.length < 2 || grado.options.length < 2;
    }
    evento.addEventListener('change', actualizarTarifa);
    persona.addEventListener('change', cargarPersona);
    grado.addEventListener('change', actualizarTarifa);
    jefeDojo.addEventListener('change', actualizarTarifa);
    invitado.addEventListener('change', actualizarTarifa);
    foto.required = !persona.value && !foto.dataset.defaultFile;
    actualizarTarifa();
});
</script>
<script src="{{ asset('js/personas.js') }}?v={{ filemtime(public_path('js/personas.js')) }}" defer></script>
@endpush
