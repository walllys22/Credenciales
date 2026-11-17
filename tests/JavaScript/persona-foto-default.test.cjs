const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function start({ persona = '', response } = {}) {
    const input = { files: [], dataset: { defaultFile: '/images/default.jpg' }, listeners: {},
        setCustomValidity() {}, addEventListener(name, fn) { this.listeners[name] = fn; },
        dispatchEvent(event) { this.listeners[event.type]?.(); } };
    const preview = { src: '/images/default.jpg', dataset: { original: '/images/default.jpg' } };
    const elements = { foto: input, 'foto-preview': preview, 'foto-client-error': {}, persona_id: { value: persona } };
    let fetches = 0;
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/personas.js'), 'utf8'), {
        document: { addEventListener: (_, fn) => fn(), getElementById: id => elements[id] },
        window: { addEventListener() {} },
        fetch: () => { fetches++; return response || Promise.resolve({ ok: true, blob: async () => ({ size: 3461 }) }); },
        DataTransfer: class { files = []; items = { add: file => this.files.push(file) }; },
        File: class { constructor(parts, name, options) { this.name = name; this.type = options.type; this.size = parts[0].size; } },
        Event: class { constructor(type) { this.type = type; } },
        URL: { createObjectURL: file => `blob:${file.name}`, revokeObjectURL() {} },
    });
    return { input, preview, fetches: () => fetches };
}

test('a new participant starts with default.jpg selected in the file input', async () => {
    const form = start();
    await new Promise(setImmediate);
    assert.equal(form.input.files[0].name, 'default.jpg');
    assert.equal(form.input.files[0].type, 'image/jpeg');
    assert.equal(form.preview.src, 'blob:default.jpg');
});
test('existing participants keep their own photo', async () => {
    const form = start({ persona: '4' });
    await new Promise(setImmediate);
    assert.equal(form.fetches(), 0);
    assert.equal(form.input.files.length, 0);
});
test('a user-selected replacement takes priority while default.jpg is downloading', async () => {
    let finish;
    const form = start({ response: new Promise(resolve => { finish = resolve; }) });
    const uploaded = { name: 'foto-personal.jpg', type: 'image/jpeg', size: 1000 };
    form.input.files = [uploaded];
    finish({ ok: true, blob: async () => ({ size: 3461 }) });
    await new Promise(setImmediate);
    assert.equal(form.input.files[0], uploaded);
});
