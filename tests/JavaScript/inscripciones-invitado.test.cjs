const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function form({ guest = false, leader = '0', rate = '696.00', tariffs = { 1: { 1: '120.50' } } } = {}) {
    const field = value => ({ value, checked: false, dataset: {}, options: [{}, {}], listeners: {}, setCustomValidity() {}, addEventListener(name, fn) { this.listeners[name] = fn; } });
    const elements = Object.fromEntries(['jefe_dojo', 'invitado_sin_costo', 'evento_id', 'persona_id', 'guardar-inscripcion', 'tariff-message', 'tariff-amount', 'observaciones', 'grado_id', 'pais_id', 'organizacion', 'foto', 'foto-preview', 'foto-client-error', 'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'documento_identidad', 'sexo', 'telefono'].map(id => [id, field('')]));
    elements.evento_id.value = '1';
    elements.persona_id.value = '1';
    elements.grado_id.value = '1';
    elements.jefe_dojo.value = leader;
    elements.invitado_sin_costo.checked = guest;
    const persona = elements.persona_id;
    persona.options = [];
    persona.replaceChildren = option => { persona.options = [option]; };
    persona.add = option => persona.options.push(option);
    Object.defineProperty(persona, 'selectedOptions', { get: () => persona.options.filter(option => option.value === persona.value) });
    let script = fs.readFileSync(path.join(__dirname, '../../resources/views/inscripciones/form.blade.php'), 'utf8').split('<script>')[1].split('</script>')[0];
    script = script.replace(/{{ Illuminate\\Support\\Js::from\(\$tarifas\) }}/, JSON.stringify(tariffs))
        .replace(/{{ Illuminate\\Support\\Js::from\(\$tarifasJefe\) }}/, JSON.stringify({ 1: rate }))
        .replace(/{{ Illuminate\\Support\\Js::from\(\$grados->filter.*? }}/, JSON.stringify([1]))
        .replace(/{{ Illuminate\\Support\\Js::from\(\$opcionesPersonas\) }}/, JSON.stringify([{ id: 1, grado_id: 1, nombre: 'Ana', eventos_inscritos: [2], datos: { primer_nombre: 'Ana', grado_id: '1', pais_id: '2', organizacion: 'Shorin Ryu Shorinkan Bolivia' }, foto_url: '/personas/1/foto' }]));
    script = script.replace("{{ asset('images/default.jpg') }}", '/images/default.jpg');
    vm.runInNewContext(script, {
        document: { addEventListener: (_, fn) => fn(), getElementById: id => elements[id] },
        Option: function (text, value) { this.text = text; this.value = value; this.dataset = {}; },
    });
    return elements;
}

test('guest checkbox updates the free preview and observations and restores the grade tariff', () => {
    const elements = form();
    assert.match(elements['tariff-amount'].textContent, /120[,.]50/);
    const guest = elements.invitado_sin_costo;
    guest.checked = true;
    guest.listeners.change();
    assert.match(elements['tariff-amount'].textContent, /0[,.]00/);
    assert.equal(elements.observaciones.value, 'invitado sin costo');
    assert.match(elements['tariff-message'].textContent, /Invitado sin costo/);
    guest.checked = false;
    guest.listeners.change();
    assert.match(elements['tariff-amount'].textContent, /120[,.]50/);
    assert.equal(elements.observaciones.value, '');
});

test('saved guest overrides dojo leader and missing tariffs on initial load', () => {
    const elements = form({ guest: true, leader: '1', rate: null, tariffs: {} });
    assert.match(elements['tariff-amount'].textContent, /0[,.]00/);
    assert.equal(elements.observaciones.value, 'invitado sin costo');
    assert.equal(elements['guardar-inscripcion'].disabled, false);
    elements.invitado_sin_costo.checked = false;
    elements.invitado_sin_costo.listeners.change();
    assert.equal(elements['tariff-amount'].textContent, '—');
    assert.match(elements['tariff-message'].textContent, /Configura el tipo de cambio/);
});

test('existing participant loads personal data and photo and switching to new clears them', () => {
    const elements = form();
    elements.persona_id.listeners.change();
    assert.equal(elements.primer_nombre.value, 'Ana');
    assert.equal(elements['foto-preview'].src, '/personas/1/foto');
    assert.equal(elements.foto.required, false);
    assert.equal(elements.pais_id.value, '2');
    assert.equal(elements.organizacion.value, 'Shorin Ryu Shorinkan Bolivia');
    elements.evento_id.value = '2';
    elements.evento_id.listeners.change();
    assert.equal(elements['guardar-inscripcion'].disabled, true);
    assert.match(elements['tariff-message'].textContent, /ya está inscrito/);
    elements.persona_id.value = '';
    elements.persona_id.listeners.change();
    assert.equal(elements.primer_nombre.value, '');
    assert.equal(elements.grado_id.value, '');
    assert.equal(elements.pais_id.value, '');
    assert.equal(elements.organizacion.value, '');
    assert.equal(elements.foto.required, true);
    assert.equal(elements['guardar-inscripcion'].disabled, false);
});

test('a Kyu dojo leader keeps the Kyu tariff', () => {
    const elements = form({ leader: '1', tariffs: { 1: { 1: '585.00', 2: '351.00' } } });
    elements.grado_id.value = '2';
    elements.grado_id.listeners.change();
    assert.match(elements['tariff-amount'].textContent, /351[,.]00/);
});
