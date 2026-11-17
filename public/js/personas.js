document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('foto');
    const preview = document.getElementById('foto-preview');
    const error = document.getElementById('foto-client-error');
    if (!input || !preview || !error) return;
    let objectUrl;
    input.addEventListener('change', () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        input.setCustomValidity('');
        const file = input.files[0];
        const valid = !file || (['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.type) && file.size <= 5 * 1024 * 1024);
        error.hidden = true;
        error.textContent = valid ? '' : 'Selecciona una foto JPG, PNG, WEBP o GIF de hasta 5 MB.';
        if (!valid) { input.value = ''; input.setCustomValidity(error.textContent); window.showAppMessage?.(error.textContent); }
        if (valid && file) objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl || preview.dataset.original;
    });
    window.addEventListener('pagehide', () => { if (objectUrl) URL.revokeObjectURL(objectUrl); });
    async function cargarPredeterminada() {
        if (!input.dataset.defaultFile || input.files.length || document.getElementById('persona_id')?.value) return;
        try {
            const response = await fetch(input.dataset.defaultFile);
            if (!response.ok) return;
            const blob = await response.blob();
            // No reemplazar una foto que el usuario seleccionó durante la carga.
            if (input.files.length || document.getElementById('persona_id')?.value) return;
            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'default.jpg', { type: 'image/jpeg' }));
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        } catch { /* El servidor aplica la misma foto predeterminada si no se adjunta otra. */ }
    }
    cargarPredeterminada();
});
