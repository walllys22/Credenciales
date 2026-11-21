const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function form(rate, amounts) {
    const element = value => ({ value, listeners: {}, addEventListener(name, fn) { this.listeners[name] = fn; } });
    const tipoCambio = element(rate);
    const rows = amounts.map(value => {
        const usd = element(value);
        const bs = { value: '', dataset: { anterior: '' } };
        return { usd, bs, querySelector: name => name === '[data-costo-usd]' ? usd : bs };
    });
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/eventos.js'), 'utf8'), {
        document: { addEventListener: (_, fn) => fn(), getElementById: id => id === 'tipo_cambio' ? tipoCambio : null, querySelectorAll: () => rows },
    });
    return { tipoCambio, rows };
}

test('Dan and Kyu recalculate immediately when USD or rate changes', () => {
    const { tipoCambio, rows } = form('6.96', ['100', '50']);
    assert.equal(rows[0].bs.value, '696.00');
    assert.equal(rows[1].bs.value, '348.00');
    tipoCambio.value = '7.10'; tipoCambio.listeners.input();
    assert.equal(rows[0].bs.value, '710.00');
    assert.equal(rows[1].bs.value, '355.00');
    rows[0].usd.value = '25'; rows[0].usd.listeners.input();
    assert.equal(rows[0].bs.value, '177.50');
});

test('currency preview uses decimal rounding and preserves free or empty rates', () => {
    const { rows } = form('6.97', ['0.10', '0', '']);
    assert.equal(rows[0].bs.value, '0.70');
    assert.equal(rows[1].bs.value, '0.00');
    assert.equal(rows[2].bs.value, '');
    rows[0].usd.value = ''; rows[0].usd.listeners.input();
    assert.equal(rows[0].bs.value, '');
});

test('rate is limited to two decimals and immediately recalculates both amounts', () => {
    const { tipoCambio, rows } = form('12.109997', ['50', '30']);
    assert.equal(tipoCambio.value, '12.11');
    assert.equal(rows[0].bs.value, '605.50');
    assert.equal(rows[1].bs.value, '363.30');
    tipoCambio.value = '12.2599'; tipoCambio.listeners.input();
    assert.equal(tipoCambio.value, '12.26');
    assert.equal(rows[0].bs.value, '613.00');
    assert.equal(rows[1].bs.value, '367.80');
    tipoCambio.value = '12'; tipoCambio.listeners.change();
    assert.equal(tipoCambio.value, '12.00');
    assert.equal(rows[0].bs.value, '600.00');
});

test('category table recalculates the three displayed tariffs', () => {
    const { tipoCambio, rows } = form('11.70', ['30', '50', '100']);
    assert.deepEqual(rows.map(row => row.bs.value), ['351.00', '585.00', '1170.00']);
    tipoCambio.value = '12'; tipoCambio.listeners.change();
    assert.deepEqual(rows.map(row => row.bs.value), ['360.00', '600.00', '1200.00']);
});
