@php($participante = $inscripcion->persona)
<div class="modal fade" data-action-style="view" id="ver-participante-{{ $inscripcion->id }}" tabindex="-1" aria-labelledby="ver-participante-titulo-{{ $inscripcion->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="ver-participante-titulo-{{ $inscripcion->id }}">Datos del participante</h2>
            </div>
            <div class="modal-body">
                <div class="row g-4 mb-4">
                    <div class="col-sm-3 text-center">
                        <img src="{{ $participante->foto_url }}" alt="Foto de {{ $participante->nombre_completo }}" width="130" height="150" class="rounded img-fluid" style="width:130px;height:150px;object-fit:cover" loading="lazy">
                        <div class="d-flex align-items-center justify-content-center gap-2 mt-2">
                            @if($participante->pais?->bandera_url)
                                <img src="{{ $participante->pais->bandera_url }}" alt="Bandera de {{ $participante->pais->nombre }}" width="32" height="21" style="object-fit:contain;flex-shrink:0">
                            @endif
                            <span>{{ $participante->pais?->nombre ?? 'País sin registrar' }}</span>
                        </div>
                    </div>
                    <div class="col-sm-9">
                        <h3 class="h5 mb-3">{{ $participante->nombre_completo }}</h3>
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Código de participante</dt><dd class="col-sm-7">#{{ str_pad($participante->id, 4, '0', STR_PAD_LEFT) }}</dd>
                            <dt class="col-sm-5">Primer nombre</dt><dd class="col-sm-7">{{ $participante->primer_nombre }}</dd>
                            <dt class="col-sm-5">Segundo nombre</dt><dd class="col-sm-7">{{ $participante->segundo_nombre ?: '—' }}</dd>
                            <dt class="col-sm-5">Primer apellido</dt><dd class="col-sm-7">{{ $participante->primer_apellido }}</dd>
                            <dt class="col-sm-5">Segundo apellido</dt><dd class="col-sm-7">{{ $participante->segundo_apellido ?: '—' }}</dd>
                            <dt class="col-sm-5">Documento de identidad</dt><dd class="col-sm-7">{{ $participante->documento_identidad ?: '—' }}</dd>
                            <dt class="col-sm-5">Sexo</dt><dd class="col-sm-7">{{ $participante->sexo }}</dd>
                            <dt class="col-sm-5">Fecha de nacimiento</dt><dd class="col-sm-7">{{ $participante->fecha_nacimiento?->format('d/m/Y') ?? '—' }}</dd>
                            <dt class="col-sm-5">Teléfono</dt><dd class="col-sm-7">{{ $participante->telefono ?: '—' }}</dd>
                            <dt class="col-sm-5">Grado</dt><dd class="col-sm-7"><x-grado-badge :nombre="$participante->grado->nombre"/></dd>
                        </dl>
                    </div>
                </div>
                <h3 class="h5 border-top pt-3 mb-3">Datos de la inscripción</h3>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Código de inscripción</dt><dd class="col-sm-8">#{{ str_pad($inscripcion->id, 6, '0', STR_PAD_LEFT) }}</dd>
                    <dt class="col-sm-4">Fecha de registro</dt><dd class="col-sm-8">{{ $inscripcion->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    <dt class="col-sm-4">Evento</dt><dd class="col-sm-8">{{ $inscripcion->evento->nombre }}</dd>
                    <dt class="col-sm-4">Fechas del evento</dt><dd class="col-sm-8">{{ $inscripcion->evento->fecha_inicio->format('d/m/Y') }} al {{ $inscripcion->evento->fecha_final->format('d/m/Y') }}</dd>
                    <dt class="col-sm-4">Lugar / Ciudad</dt><dd class="col-sm-8">{{ $inscripcion->evento->lugar }} · {{ $inscripcion->evento->ciudad }}</dd>
                    <dt class="col-sm-4">Cargo</dt><dd class="col-sm-8">{{ $inscripcion->cargo }}</dd>
                    <dt class="col-sm-4">Jefe de Dojo</dt><dd class="col-sm-8">{{ $inscripcion->jefe_dojo ? 'Sí' : 'No' }}</dd>
                    <dt class="col-sm-4">Invitado sin costo</dt><dd class="col-sm-8">{{ $inscripcion->invitado_sin_costo ? 'Sí' : 'No' }}</dd>
                    <dt class="col-sm-4">Observaciones</dt><dd class="col-sm-8">{{ $inscripcion->observaciones ?: 'Sin observaciones' }}</dd>
                    <dt class="col-sm-4">Costo</dt><dd class="col-sm-8">Bs {{ number_format($inscripcion->tarifa_vigente, 2, ',', '.') }}</dd>
                    <dt class="col-sm-4">Pagado</dt><dd class="col-sm-8">Bs {{ number_format($inscripcion->total_pagado, 2, ',', '.') }}</dd>
                    <dt class="col-sm-4">Descuento</dt><dd class="col-sm-8">Bs {{ number_format($inscripcion->pagos_sum_descuento ?? 0, 2, ',', '.') }}</dd>
                    <dt class="col-sm-4">Saldo</dt><dd class="col-sm-8">Bs {{ number_format($inscripcion->saldo_pendiente, 2, ',', '.') }}</dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-project" data-action-style="close" data-bs-dismiss="modal" aria-label="Cerrar datos del participante" title="Cerrar"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="button-label">Cerrar</span></button>
            </div>
        </div>
    </div>
</div>
