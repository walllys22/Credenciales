<div class="form-section-heading"><span class="step-number">01</span><div><h2>Datos personales</h2><p>Los campos con * son obligatorios.</p></div></div>
<div class="row">
<div class="col-12 mb-4">
    <label for="foto" class="form-label">Foto de la persona @if(!$persona->exists)<span class="required">*</span>@endif</label>
    <div class="person-photo-upload">
        <img id="foto-preview" src="{{ $persona->foto_url }}" alt="Vista previa de la foto" width="130" height="150" data-original="{{ $persona->foto_url }}">
        <div class="flex-grow-1">
            <input type="file" id="foto" name="foto" class="form-control @error('foto') is-invalid @enderror" accept="image/jpeg,image/png,image/webp,image/gif" @required(!$persona->exists) aria-describedby="foto-help" @error('foto') aria-invalid="true" @enderror>
            @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <p class="form-text" id="foto-help">JPG, PNG, WEBP o GIF. Peso máximo: 5 MB. Dimensiones máximas: 6000 × 6000 píxeles.@if($persona->exists) Si no seleccionas otra foto, se conserva la actual.@endif</p>
            <p id="foto-client-error" class="text-danger small" role="alert" hidden></p>
        </div>
    </div>
</div>
<x-field class="col-md-6" name="primer_nombre" label="Primer nombre" :value="$persona->primer_nombre" :required="true" maxlength="100"/>
<x-field class="col-md-6" name="segundo_nombre" label="Segundo nombre" :value="$persona->segundo_nombre" maxlength="100"/>
<x-field class="col-md-6" name="primer_apellido" label="Primer apellido" :value="$persona->primer_apellido" :required="true" maxlength="100"/>
<x-field class="col-md-6" name="segundo_apellido" label="Segundo apellido" :value="$persona->segundo_apellido" maxlength="100"/>
<x-field class="col-md-6" name="documento_identidad" label="Documento de identidad" :value="$persona->documento_identidad" maxlength="50"/>
<div class="col-md-6 mb-3"><label for="sexo" class="form-label">Sexo <span class="required">*</span></label><select id="sexo" name="sexo" class="form-select @error('sexo') is-invalid @enderror" required><option value="">Seleccionar</option>@foreach(['Masculino', 'Femenino', 'Otro'] as $sexo)<option @selected(old('sexo', $persona->sexo) === $sexo)>{{ $sexo }}</option>@endforeach</select>@error('sexo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<x-field class="col-md-6" name="fecha_nacimiento" label="Fecha de nacimiento" type="date" :value="$persona->fecha_nacimiento?->format('Y-m-d')" :max="now()->format('Y-m-d')"/>
<x-field class="col-md-6" name="telefono" label="Teléfono" type="tel" :value="$persona->telefono" maxlength="30"/>
</div>
<div class="row align-items-center mt-4">
<div class="col-md-6"><div class="form-section-heading"><span class="step-number">02</span><div><h2>Grado del participante</h2><p>El grado determina la tarifa en cada evento.</p></div></div></div>
<div class="col-md-6"><label class="form-label" for="grado_id">Grado <span class="required">*</span></label><select name="grado_id" id="grado_id" class="form-select @error('grado_id') is-invalid @enderror" required><option value="">Seleccionar grado</option>@foreach($grados as $grado)<option value="{{ $grado->id }}" @selected(old('grado_id', $persona->grado_id) == $grado->id)>{{ $grado->nombre }}</option>@endforeach</select>@error('grado_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
<p class="form-text mt-2">¿Necesitas otro grado? <a href="{{ route('grados.index') }}">Gestionar grados</a></p>
</div>
</div>
@if($grados->isEmpty())<div class="alert alert-warning">Agrega al menos un grado antes de registrar personas.</div>@endif
