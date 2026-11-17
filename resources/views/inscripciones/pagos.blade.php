@extends('layouts.app')
@section('section', 'Inscripción')
@section('title', 'Agregar pago')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pagos.css') }}?v={{ filemtime(public_path('css/pagos.css')) }}">
@endpush
@section('actions')
    <a href="{{ route('inscripciones.index') }}" class="btn btn-outline-secondary btn-project" data-action-style="back" aria-label="Volver a inscripciones" title="Volver a inscripciones"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i><span class="button-label">Volver a inscripciones</span></a>
@endsection

@section('content')
<section class="panel payment-summary">
    <div>
        <h2>{{ $inscripcion->persona->nombre_completo }}</h2>
        <p>Documento: {{ $inscripcion->persona->documento_identidad }} · {{ $inscripcion->persona->grado->nombre }}</p>
    </div>
    <div class="payment-event">{{ $inscripcion->evento->nombre }} · {{ $inscripcion->cargo }}</div>
    <div class="payment-exchange">Tipo de Cambio: <strong>{{ $inscripcion->evento->tipo_cambio ? 'Bs '.number_format($inscripcion->evento->tipo_cambio, 2, ',', '.').' por dólar' : 'Sin configurar' }}</strong></div>
    <div class="payment-totals">
        <div>Tarifa vigente: <strong>Bs {{ number_format($tarifa, 2, ',', '.') }}</strong></div>
        <div>Total pagado: <strong>Bs {{ number_format($totalPagado, 2, ',', '.') }}</strong></div>
        @if($totalDescuento > 0)<div>Descuento por preinscripción: <strong>Bs {{ number_format($totalDescuento, 2, ',', '.') }}</strong></div>@endif
        <div>Saldo pendiente: <strong>Bs {{ number_format($saldoPendiente, 2, ',', '.') }}</strong></div>
        @if($saldoAFavor > 0)<div>Saldo a favor: <strong>Bs {{ number_format($saldoAFavor, 2, ',', '.') }}</strong></div>@endif
    </div>
</section>

@if($inscripcion->invitado_sin_costo)
<div class="panel mb-4">
    <label for="observaciones-inscripcion" class="form-label">Observaciones</label>
    <textarea id="observaciones-inscripcion" class="form-control" rows="1" readonly>{{ $inscripcion->observaciones }}</textarea>
    <p class="form-text">Inscripción gratuita. No corresponde registrar cobros.</p>
</div>
@else
<form method="POST" action="{{ route('inscripciones.pagos.store', $inscripcion) }}" class="panel payment-form mb-4">
    @csrf
    <div class="payment-form-heading">
      <div class="form-section-heading">
        <span class="step-number"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i></span>
        <div><h2>Registrar pago</h2><p>El pago se guardará para esta persona y este evento.</p></div>
      </div>
      <fieldset class="payment-currency">
        <legend class="visually-hidden">Moneda del pago</legend>
        <label><input type="radio" name="moneda" value="BOB" @checked(old('moneda', 'BOB') === 'BOB') required> Bs.-</label>
        <label><input type="radio" name="moneda" value="USD" @checked(old('moneda') === 'USD') required> Dólares</label>
      </fieldset>
    </div>
    <div class="payment-fields">
    <div class="payment-fields-row">
        <div class="mb-3">
            <label for="monto" class="form-label">Monto pagado</label>
            <input id="monto" name="monto" type="number" min="0.01" max="9999999999.99" step="0.01" value="{{ old('monto') }}" class="form-control @error('monto') is-invalid @enderror" required @error('monto') aria-invalid="true" aria-describedby="monto-error" @enderror>
            @error('monto')<div id="monto-error" class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="payment-date-and-discount">
            <x-field name="fecha" label="Fecha" type="date" :value="now()->toDateString()" :max="now()->toDateString()" :required="true" />
            <div class="form-check payment-total-check">
                <input id="pago_total" name="pago_total" type="checkbox" value="1" class="form-check-input" @checked(old('pago_total'))>
                <label for="pago_total" class="form-check-label">Pago total<br>por descuento</label>
            </div>
        </div>
    </div>
    <div class="mb-3">
        <label for="observacion" class="form-label">Observaciones</label>
        <textarea id="observacion" name="observacion" rows="1" maxlength="500" class="form-control @error('observacion') is-invalid @enderror" @error('observacion') aria-invalid="true" aria-describedby="observacion-error" @enderror>{{ old('observacion') }}</textarea>
        @error('observacion')<div id="observacion-error" class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    </div>
    <div class="form-actions">
        <a href="{{ route('inscripciones.index') }}" class="btn btn-outline-secondary btn-project" data-action-style="cancel" aria-label="Cancelar" title="Cancelar"><i class="fa-solid fa-ban" aria-hidden="true"></i><span class="button-label">Cancelar</span></a>
        <button type="submit" class="btn btn-primary btn-project" data-action-style="save" aria-label="Guardar pago" title="Guardar pago"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span class="button-label">Guardar pago</span></button>
    </div>
</form>
@endif

<section class="panel">
    <div class="panel-toolbar"><div><h2>Historial de pagos</h2><p>Los pagos en dólares se descuentan al tipo de cambio guardado en cada pago. El saldo restante se expresa en bolivianos.</p></div></div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Fecha</th><th>Monto pagado</th><th>Saldo pendiente (Bs)</th><th>Observación</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @forelse($pagos as $pago)
                    <tr>
                        <td class="text-nowrap">{{ $pago->fecha->format('d/m/Y') }}</td>
                        <td class="text-nowrap">
                            @if($pago->moneda === 'USD')
                                USD {{ number_format($pago->monto_original, 2, ',', '.') }}
                                <small class="d-block">Equivale a Bs {{ number_format($pago->monto, 2, ',', '.') }} · TC {{ number_format($pago->tipo_cambio, 2, ',', '.') }}</small>
                            @else
                                Bs {{ number_format($pago->monto, 2, ',', '.') }}
                            @endif
                        </td>
                        <td class="text-nowrap">Bs {{ number_format($pago->saldo_pendiente, 2, ',', '.') }}</td>
                        <td>{{ $pago->observacion ?? '—' }}@if($pago->pago_total)<small class="d-block">Pago total por descuento de preinscripción: Bs {{ number_format($pago->descuento, 2, ',', '.') }}</small>@endif</td>
                        <td class="text-end">
                            @if($pago->id === $pagoEliminableId)
                            <button type="button" class="btn action-button action-delete btn-project" data-bs-toggle="modal" data-bs-target="#eliminar-pago-{{ $pago->id }}" title="Eliminar pago" aria-label="Eliminar pago del {{ $pago->fecha->format('d/m/Y') }} de {{ $inscripcion->persona->nombre_completo }}" data-action-style="delete"><i class="fa-solid fa-trash-can" aria-hidden="true"></i><span class="button-label">Eliminar pago</span></button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">Esta inscripción todavía no tiene pagos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-pagination">{{ $pagos->links() }}</div>
</section>
@foreach($pagos as $pago)
    @if($pago->id === $pagoEliminableId)
    <div class="modal fade payment-delete-modal" data-action-style="delete" id="eliminar-pago-{{ $pago->id }}" tabindex="-1" aria-labelledby="eliminar-pago-title-{{ $pago->id }}" aria-describedby="eliminar-pago-description-{{ $pago->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="eliminar-pago-title-{{ $pago->id }}"><i class="fa-solid fa-trash-can me-2" aria-hidden="true"></i>Eliminar pago</h2>
                    <button type="button" class="btn-close btn-project" data-bs-dismiss="modal" aria-label="Cerrar" data-action-style="close" title="Cerrar"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="button-label">Cerrar</span></button>
                </div>
                <div class="modal-body">
                    <p id="eliminar-pago-description-{{ $pago->id }}">¿Confirmas que deseas eliminar este pago? Esta acción no se puede deshacer.</p>
                    <dl class="payment-delete-details">
                        <dt>Participante</dt><dd>{{ $inscripcion->persona->nombre_completo }}</dd>
                        <dt>Evento</dt><dd>{{ $inscripcion->evento->nombre }}</dd>
                        <dt>Fecha</dt><dd>{{ $pago->fecha->format('d/m/Y') }}</dd>
                        <dt>Monto pagado</dt><dd>{{ $pago->moneda === 'USD' ? 'USD '.number_format($pago->monto_original, 2, ',', '.') : 'Bs '.number_format($pago->monto, 2, ',', '.') }}</dd>
                        @if($pago->moneda === 'USD')
                            <dt>Tipo de cambio</dt><dd>Bs {{ number_format($pago->tipo_cambio, 2, ',', '.') }} por dólar</dd>
                            <dt>Equivalente</dt><dd>Bs {{ number_format($pago->monto, 2, ',', '.') }}</dd>
                        @endif
                        <dt>Observación</dt><dd>{{ $pago->observacion ?: 'Sin observación' }}</dd>
                        @if($pago->pago_total)<dt>Descuento</dt><dd>Bs {{ number_format($pago->descuento, 2, ',', '.') }} por preinscripción. Se anulará al eliminar este pago.</dd>@endif
                    </dl>
                    <p class="text-danger mb-0">Al eliminarlo se actualizarán el total pagado y el saldo pendiente.</p>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="confirmar-eliminar-pago-{{ $pago->id }}" name="confirmar_eliminacion" value="1" form="eliminar-pago-form-{{ $pago->id }}" data-payment-delete-confirm required>
                        <label class="form-check-label" for="confirmar-eliminar-pago-{{ $pago->id }}">Estoy seguro de eliminar este pago.</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-project" data-bs-dismiss="modal" data-action-style="cancel" aria-label="Cancelar" title="Cancelar"><i class="fa-solid fa-ban" aria-hidden="true"></i><span class="button-label">Cancelar</span></button>
                    <form id="eliminar-pago-form-{{ $pago->id }}" method="POST" action="{{ route('inscripciones.pagos.destroy', [$inscripcion, $pago]) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-project" data-payment-delete-submit disabled data-action-style="delete" aria-label="Sí, eliminar pago" title="Sí, eliminar pago"><i class="fa-solid fa-trash-can" aria-hidden="true"></i><span class="button-label">Sí, eliminar pago</span></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach
@endsection
@push('scripts')
<script>
document.querySelectorAll('.payment-delete-modal').forEach(modal => {
    const checkbox = modal.querySelector('[data-payment-delete-confirm]');
    const button = modal.querySelector('[data-payment-delete-submit]');
    const form = button.form;
    const resetConfirmation = () => {
        checkbox.checked = false;
        button.disabled = true;
    };
    checkbox.addEventListener('change', () => { button.disabled = !checkbox.checked; });
    modal.addEventListener('show.bs.modal', resetConfirmation);
    modal.addEventListener('hidden.bs.modal', resetConfirmation);
    form.addEventListener('submit', event => {
        if (!checkbox.checked) {
            event.preventDefault();
            checkbox.focus();
        } else {
            button.disabled = true;
        }
    });
});
</script>
@endpush
