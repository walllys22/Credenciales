<form action="{{ $action }}" method="POST" class="d-inline" data-confirm="{{ $message ?? '¿Eliminar este registro? Esta acción no se puede deshacer.' }}">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-sm btn-delete btn-project" aria-label="Eliminar {{ $label ?? 'registro' }}" data-action-style="delete" title="Eliminar"><i class="fa-solid fa-trash-can" aria-hidden="true"></i><span class="button-label">Eliminar</span></button>
</form>
