const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function preview(count) {
    const button = () => ({ listeners: {}, addEventListener(type, handler) { this.listeners[type] = handler; } });
    const previous = button(), next = button(), status = {};
    const navigation = { hidden: true, querySelector: selector => ({ '[data-batch-previous]': previous, '[data-batch-next]': next, '[data-batch-status]': status })[selector] };
    const pages = Array.from({ length: count }, () => ({ top: 1000, scrollIntoView() { this.scrolled = true; }, getBoundingClientRect() { return { top: this.top }; } }));
    const listeners = {};
    const window = { innerHeight: 900, matchMedia: () => ({ matches: true }), addEventListener(type, handler) { listeners[type] = handler; }, requestAnimationFrame(handler) { handler(); return 1; } };
    const document = { querySelectorAll: () => pages, querySelector: () => navigation, addEventListener(type, handler) { handler(); } };
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/credenciales-lote-paginas.js'), 'utf8'), { document, window });
    return { previous, next, status, navigation, pages, listeners };
}

test('navega entre páginas y actualiza sus límites y el desplazamiento manual', () => {
    const view = preview(2);
    assert.equal(view.navigation.hidden, false);
    assert.equal(view.previous.disabled, true);
    assert.equal(view.status.textContent, 'Página 1 de 2');
    view.next.listeners.click();
    assert.equal(view.pages[1].scrolled, true);
    assert.equal(view.status.textContent, 'Página 2 de 2');
    assert.equal(view.next.disabled, true);
    view.pages[0].top = 0;
    view.listeners.scroll();
    assert.equal(view.status.textContent, 'Página 1 de 2');
    view.next.listeners.click();
    view.previous.listeners.click();
    assert.equal(view.pages[0].scrolled, true);
});

test('oculta la navegación cuando hay una sola hoja', () => {
    assert.equal(preview(1).navigation.hidden, true);
});
