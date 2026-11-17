const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const context = { module: { exports: {} } };
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/reporte-paginas.js'), 'utf8'), context);
const paginate = (...args) => JSON.parse(JSON.stringify(context.module.exports.agruparFilas(...args)));

test('a short report fits in one page with its totals', () => {
    assert.deepEqual(paginate([20, 30], 100, 15), [{ filas: [0, 1], totales: true }]);
});
test('multiple pages preserve every row once and show totals only at the end', () => {
    const pages = paginate([40, 60, 55, 30, 20], 100, 20);
    assert.deepEqual(pages, [
        { filas: [0, 1], totales: false },
        { filas: [2, 3], totales: false },
        { filas: [4], totales: true },
    ]);
    assert.deepEqual(pages.flatMap(page => page.filas), [0, 1, 2, 3, 4]);
});
test('totals move together to a new page when the previous page is full', () => {
    assert.deepEqual(paginate([50, 50], 100, 15), [
        { filas: [0, 1], totales: false }, { filas: [], totales: true },
    ]);
});
test('empty reports and oversized rows do not create endless empty pages', () => {
    assert.deepEqual(paginate([], 100, 15), [{ filas: [], totales: true }]);
    assert.deepEqual(paginate([150, 20], 100, 15), [
        { filas: [0], totales: false }, { filas: [1], totales: true },
    ]);
});
