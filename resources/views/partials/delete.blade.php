<form action="{{ $action }}" method="POST" class="d-inline" data-confirm="{{ $message ?? '¿Eliminar este registro? Esta acción no se puede deshacer.' }}">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-sm btn-delete" aria-label="Eliminar {{ $label ?? 'registro' }}">Eliminar</button>
</form>
