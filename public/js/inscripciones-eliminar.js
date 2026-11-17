document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('confirmar-eliminar-inscripcion');
    if (!modal) return;
    const confirmar = document.getElementById('confirmar-eliminar-inscripcion-boton');
    modal.addEventListener('show.bs.modal', event => {
        const origen = event.relatedTarget;
        const formId = origen?.dataset.deleteForm;
        confirmar.disabled = !formId || !document.getElementById(formId);
        if (confirmar.disabled) {
            event.preventDefault();
            return;
        }
        document.getElementById('eliminar-inscripcion-participante').textContent = origen.dataset.participante;
        document.getElementById('eliminar-inscripcion-evento').textContent = origen.dataset.evento;
        confirmar.setAttribute('form', formId);
    });
    modal.addEventListener('hidden.bs.modal', () => {
        confirmar.disabled = true;
        confirmar.removeAttribute('form');
    });
    document.querySelectorAll('form[id^="eliminar-inscripcion-"]').forEach(form => {
        form.addEventListener('submit', () => { confirmar.disabled = true; });
    });
});
