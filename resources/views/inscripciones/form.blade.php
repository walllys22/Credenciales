@extends('layouts.app')
@section('section', 'Inscripción')
@section('title', $inscripcion->exists ? 'Editar inscripción' : 'Nueva inscripción')
@section('subtitle', 'Asigna un participante y su cargo. La tarifa se calcula según su grado.')
@section('actions')<a href="{{ route('inscripciones.index') }}" class="btn btn-outline-secondary">← Volver a inscripciones</a>@endsection
@section('content')
<form method="POST" action="{{ $inscripcion->exists ? route('inscripciones.update', $inscripcion) : route('inscripciones.store') }}" class="panel form-panel">
@csrf @if($inscripcion->exists) @method('PUT') @endif
<div class="form-section-heading"><span class="step-number">01</span><div><h2>Datos de la inscripción</h2><p>Todos los campos son obligatorios.</p></div></div>
<div class="mb-4"><label for="evento_id" class="form-label">Evento <span class="required">*</span></label><select class="form-select @error('evento_id') is-invalid @enderror" name="evento_id" id="evento_id" required><option value="">Seleccionar evento</option>@foreach($eventos as $evento)<option value="{{ $evento->id }}" @selected($eventoSeleccionado == $evento->id)>{{ $evento->nombre }} · {{ $evento->fecha_inicio->format('d/m/Y') }} · {{ $evento->ciudad }}</option>@endforeach</select>@error('evento_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="mb-4"><label for="persona_id" class="form-label">Persona <span class="required">*</span></label><select class="form-select @error('persona_id') is-invalid @enderror" name="persona_id" id="persona_id" required><option value="">Seleccionar persona</option>@foreach($personas as $persona)<option value="{{ $persona->id }}" data-grado="{{ $persona->grado_id }}" @selected(old('persona_id', $inscripcion->persona_id) == $persona->id)>{{ $persona->nombre_completo }} · CI {{ $persona->documento_identidad }} · {{ $persona->grado->nombre }}</option>@endforeach</select>@error('persona_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<x-field name="cargo" label="Cargo" :value="$inscripcion->cargo" :required="true" maxlength="120"/>
<p class="form-text">Por ejemplo: participante, expositor, coordinador u organizador.</p>
<div class="mb-4">
    <label for="jefe_dojo" class="form-label">Jefe de Dojo</label>
    <select id="jefe_dojo" name="jefe_dojo" class="form-select @error('jefe_dojo') is-invalid @enderror" required>
        <option value="0" @selected(!old('jefe_dojo', $inscripcion->jefe_dojo))>No</option>
        <option value="1" @selected(old('jefe_dojo', $inscripcion->jefe_dojo))>Sí</option>
    </select>
    <p class="form-text">Si es Jefe de Dojo, la tarifa es de $us 100, convertida al tipo de cambio del evento.</p>
    @error('jefe_dojo')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="tariff-preview" role="status" aria-live="polite"><div><span class="eyebrow">TARIFA VIGENTE</span><p id="tariff-message">Selecciona un evento y una persona para consultar el costo.</p></div><strong id="tariff-amount">—</strong></div>
<p id="personas-disponibles" class="form-text" role="status" aria-live="polite">Solo se muestran personas que aún no están inscritas en el evento seleccionado.</p>
@if($eventos->isEmpty() || !$hayPersonas)<div class="alert alert-warning mt-3">Necesitas <a href="{{ route('personas.create') }}">registrar personas</a> y <a href="{{ route('eventos.create') }}">crear un evento</a> antes de inscribir participantes.</div>@endif
<div class="form-actions"><a href="{{ route('inscripciones.index') }}" class="btn btn-outline-secondary">Cancelar</a><button id="guardar-inscripcion" class="btn btn-primary" @disabled($eventos->isEmpty() || !$hayPersonas)>{{ $inscripcion->exists ? 'Guardar cambios' : 'Registrar inscripción' }}</button></div>
</form>
@endsection
@push('scripts')
@php($tarifas = $eventos->mapWithKeys(fn ($evento) => [$evento->id => $evento->grados->mapWithKeys(fn ($grado) => [$grado->id => $grado->pivot->costo])]))
@php($tarifasJefe = $eventos->mapWithKeys(fn ($evento) => [$evento->id => $evento->tipo_cambio ? (string) Brick\Math\BigDecimal::of('100')->multipliedBy($evento->tipo_cambio)->toScale(2, Brick\Math\RoundingMode::HalfUp) : null]))
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tarifas = {{ Illuminate\Support\Js::from($tarifas) }};
    const tarifasJefe = {{ Illuminate\Support\Js::from($tarifasJefe) }};
    const jefeDojo = document.getElementById('jefe_dojo');
    const evento = document.getElementById('evento_id');
    const persona = document.getElementById('persona_id');
    const opcionesPersonas = {{ Illuminate\Support\Js::from($opcionesPersonas) }};
    const guardar = document.getElementById('guardar-inscripcion');
    function actualizarPersonas() {
        const seleccionAnterior = persona.value;
        const disponibles = opcionesPersonas.filter(opcion => evento.value && !opcion.eventos_inscritos.some(id => String(id) === evento.value));
        persona.replaceChildren(new Option(!evento.value ? 'Selecciona primero un evento' : disponibles.length ? 'Seleccionar persona' : 'No hay personas disponibles para este evento', ''));
        disponibles.forEach(opcion => {
            const option = new Option(opcion.nombre, String(opcion.id));
            option.dataset.grado = String(opcion.grado_id);
            persona.add(option);
        });
        persona.value = disponibles.some(opcion => String(opcion.id) === seleccionAnterior) ? seleccionAnterior : '';
        persona.disabled = !evento.value || disponibles.length === 0;
        guardar.disabled = persona.disabled;
        document.getElementById('personas-disponibles').textContent = !evento.value ? 'Selecciona un evento para ver las personas disponibles.' : disponibles.length ? 'Solo se muestran personas que aún no están inscritas en este evento.' : 'Todas las personas registradas ya están inscritas en este evento.';
        actualizarTarifa();
    }
    function actualizarTarifa() {
        const grado = persona.selectedOptions[0]?.dataset.grado;
        const esJefe = jefeDojo.value === '1';
        const costo = !evento.value || !persona.value ? null : esJefe ? tarifasJefe[evento.value] : tarifas[evento.value]?.[grado];
        document.getElementById('tariff-message').textContent = !evento.value || !persona.value ? 'Selecciona un evento y una persona para consultar el costo.' : costo == null ? (esJefe ? 'Configura el tipo de cambio del evento para aplicar la tarifa de $us 100.' : 'Este evento no tiene una tarifa para el grado de la persona.') : esJefe ? 'Tarifa de Jefe de Dojo: $us 100 al tipo de cambio del evento.' : 'Costo según el grado del participante. No representa un pago realizado.';
        document.getElementById('tariff-amount').textContent = costo == null ? '—' : 'Bs ' + Number(costo).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    evento.addEventListener('change', actualizarPersonas); persona.addEventListener('change', actualizarTarifa); actualizarPersonas();
    jefeDojo.addEventListener('change', actualizarTarifa);
});
</script>
@endpush
