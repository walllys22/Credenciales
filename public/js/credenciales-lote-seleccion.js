document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-credenciales-lote');
    if (!form) return;
    const key = `credenciales-lote-${form.dataset.evento}`;
    const boxes = [...document.querySelectorAll('.seleccionar-credencial')];
    const all = document.getElementById('seleccionar-pagina');
    const fields = document.getElementById('seleccion-lote-campos');
    const button = document.getElementById('imprimir-lote');
    const status = document.getElementById('seleccion-lote-estado');
    let selected = new Set();
    try { selected = new Set(JSON.parse(sessionStorage.getItem(key) || '[]')); } catch (_) { /* Storage may be unavailable. */ }
    function update() {
        boxes.forEach(box => { box.checked = selected.has(box.value); });
        all.checked = boxes.length > 0 && boxes.every(box => box.checked);
        all.indeterminate = boxes.some(box => box.checked) && !all.checked;
        fields.replaceChildren();
        selected.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'inscripciones[]'; input.value = id;
            fields.append(input);
        });
        button.disabled = selected.size < 2 || selected.size > 500;
        status.textContent = `${selected.size} inscritos seleccionados. Seleccione entre 2 y 500 para imprimir.`;
        try { sessionStorage.setItem(key, JSON.stringify([...selected])); } catch (_) { /* Selection remains on this page. */ }
    }
    boxes.forEach(box => box.addEventListener('change', () => {
        if (box.checked) selected.add(box.value); else selected.delete(box.value);
        update();
    }));
    all.addEventListener('change', () => {
        boxes.forEach(box => { if (all.checked) selected.add(box.value); else selected.delete(box.value); });
        update();
    });
    document.getElementById('limpiar-seleccion-lote').addEventListener('click', () => { selected.clear(); update(); });
    form.addEventListener('submit', event => { if (selected.size < 2 || selected.size > 500) event.preventDefault(); });
    update();
});
