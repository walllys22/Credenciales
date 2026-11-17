const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function modal() {
    const element = () => ({ listeners: {}, attributes: {}, addEventListener(name, fn) { this.listeners[name] = fn; }, setAttribute(name, value) { this.attributes[name] = value; }, removeAttribute(name) { delete this.attributes[name]; } });
    const elements = Object.fromEntries(['confirmar-eliminar-inscripcion', 'confirmar-eliminar-inscripcion-boton', 'eliminar-inscripcion-participante', 'eliminar-inscripcion-evento', 'eliminar-inscripcion-1', 'eliminar-inscripcion-2'].map(id => [id, element()]));
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/inscripciones-eliminar.js'), 'utf8'), {
        document: { addEventListener: (_, fn) => fn(), getElementById: id => elements[id], querySelectorAll: () => [elements['eliminar-inscripcion-1'], elements['eliminar-inscripcion-2']] },
    });
    return { elements, show: elements['confirmar-eliminar-inscripcion'].listeners['show.bs.modal'], hide: elements['confirmar-eliminar-inscripcion'].listeners['hidden.bs.modal'], button: elements['confirmar-eliminar-inscripcion-boton'] };
}

test('warning selects only the requested registration and cancellation clears the submission target', () => {
    const { elements, show, hide, button } = modal();
    show({ relatedTarget: { dataset: { deleteForm: 'eliminar-inscripcion-1', participante: 'Ana <Pérez>', evento: 'Seminario' } } });
    assert.equal(button.attributes.form, 'eliminar-inscripcion-1');
    assert.equal(button.disabled, false);
    assert.equal(elements['eliminar-inscripcion-participante'].textContent, 'Ana <Pérez>');
    assert.equal(elements['eliminar-inscripcion-evento'].textContent, 'Seminario');
    hide();
    assert.equal(button.disabled, true);
    assert.equal(button.attributes.form, undefined);
    show({ relatedTarget: { dataset: { deleteForm: 'eliminar-inscripcion-2', participante: 'Luis', evento: 'Otro evento' } } });
    assert.equal(button.attributes.form, 'eliminar-inscripcion-2');
    elements['eliminar-inscripcion-2'].listeners.submit();
    assert.equal(button.disabled, true);
});

test('warning cannot open with a missing registration form', () => {
    const { show, button } = modal();
    let prevented = false;
    show({ preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(button.disabled, true);
});
