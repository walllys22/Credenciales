document.addEventListener('DOMContentLoaded', () => {
    const tipoCambio = document.getElementById('tipo_cambio');
    const camposCosto = document.querySelectorAll('[data-tarifa-grado]');
    const scaledInteger = (value, places) => {
        if (!/^\d+(\.\d+)?$/.test(value)) return null;
        const [integer, decimals = ''] = value.split('.');
        if (decimals.length > places) return null;
        return BigInt(integer) * (10n ** BigInt(places)) + BigInt(decimals.padEnd(places, '0') || '0');
    };
    function actualizarCostos() {
        const cambio = scaledInteger(tipoCambio?.value || '', 2);
        camposCosto.forEach(campo => {
            const usd = campo.querySelector('[data-costo-usd]');
            const bs = campo.querySelector('[data-costo-bs]');
            const centavosUsd = scaledInteger(usd.value, 2);
            if (cambio === null || cambio <= 0n || centavosUsd === null) {
                bs.value = !tipoCambio?.value && !usd.value ? bs.dataset.anterior || '' : '';
                return;
            }
            const centavosBs = (centavosUsd * cambio + 50n) / 100n;
            bs.value = `${centavosBs / 100n}.${String(centavosBs % 100n).padStart(2, '0')}`;
        });
    }
    function actualizarTipoCambio(completar = false) {
        const valor = tipoCambio?.value || '';
        if (/^\d+(\.\d+)?$/.test(valor) && (completar || (valor.split('.')[1] || '').length > 2)) {
            const [entero, decimales = ''] = valor.split('.');
            const centavos = BigInt(entero) * 100n + BigInt(decimales.padEnd(2, '0').slice(0, 2)) + ((decimales[2] || '0') >= '5' ? 1n : 0n);
            tipoCambio.value = `${centavos / 100n}.${String(centavos % 100n).padStart(2, '0')}`;
        }
        actualizarCostos();
    }
    tipoCambio?.addEventListener('input', () => actualizarTipoCambio());
    tipoCambio?.addEventListener('change', () => actualizarTipoCambio(true));
    tipoCambio?.addEventListener('blur', () => actualizarTipoCambio(true));
    camposCosto.forEach(campo => campo.querySelector('[data-costo-usd]').addEventListener('input', actualizarCostos));
    actualizarTipoCambio(true);
    const input = document.getElementById('imagen');
    const preview = document.getElementById('imagen-preview');
    const remove = document.getElementById('quitar_imagen');
    const error = document.getElementById('imagen-client-error');
    if (!input || !preview) return;
    let objectUrl;
    input.addEventListener('change', () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        const file = input.files[0];
        const valid = !file || (['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.type) && file.size <= 5 * 1024 * 1024);
        error.hidden = true;
        error.textContent = valid ? '' : 'Selecciona una imagen JPG, PNG, WEBP o GIF de hasta 5 MB.';
        if (!valid) { input.value = ''; window.showAppMessage?.(error.textContent); }
        if (valid && file) { objectUrl = URL.createObjectURL(file); if (remove) remove.checked = false; }
        preview.src = objectUrl || preview.dataset.original;
    });
    remove?.addEventListener('change', () => { if (remove.checked) { input.value = ''; if (objectUrl) URL.revokeObjectURL(objectUrl); objectUrl = null; preview.src = '/images/evento-default.svg'; } else preview.src = preview.dataset.original; });
    window.addEventListener('pagehide', () => { if (objectUrl) URL.revokeObjectURL(objectUrl); });
});
