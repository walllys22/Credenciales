// El modal comparte la función y el color del botón que lo abre.
document.addEventListener('show.bs.modal', event => {
    if (!event.target.matches?.('.modal')) return;
    const origen = event.relatedTarget?.closest('[data-action-style]');
    if (origen?.dataset.actionStyle) event.target.dataset.actionStyle = origen.dataset.actionStyle;
});
