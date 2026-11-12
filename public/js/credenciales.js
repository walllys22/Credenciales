document.addEventListener('DOMContentLoaded', () => {
    const preview = document.createElement('div');
    preview.id = 'credential-zoom';
    preview.className = 'credential-zoom';
    preview.hidden = true;
    preview.setAttribute('role', 'region');
    preview.setAttribute('aria-label', 'Credencial ampliada');
    document.body.append(preview);
    let active = null;

    function hideZoom() {
        active?.setAttribute('aria-expanded', 'false');
        active = null;
        preview.hidden = true;
        preview.replaceChildren();
    }

    function showZoom(card) {
        if (active === card) { hideZoom(); return; }
        hideZoom();
        const bounds = card.getBoundingClientRect();
        if (!bounds.width || !bounds.height) return;
        const scale = Math.min(1.8, (window.innerWidth - 32) / bounds.width, (window.innerHeight - 32) / bounds.height);
        const width = bounds.width * scale;
        const height = bounds.height * scale;
        const clone = card.cloneNode(true);
        ['id', 'data-credential-zoom', 'role', 'tabindex', 'aria-expanded', 'aria-controls', 'aria-label'].forEach(name => clone.removeAttribute(name));
        clone.style.width = `${bounds.width}px`;
        clone.style.height = `${bounds.height}px`;
        clone.style.transform = `scale(${scale})`;
        clone.style.transformOrigin = 'top left';
        clone.querySelectorAll('img').forEach(image => { image.loading = 'eager'; });
        preview.style.width = `${width}px`;
        preview.style.height = `${height}px`;
        const left = bounds.right + 12 + width + 8 <= window.innerWidth - 12 ? bounds.right + 12 : bounds.left - width - 20;
        preview.style.left = `${Math.max(12, Math.min(left, window.innerWidth - width - 20))}px`;
        preview.style.top = `${Math.max(12, Math.min(bounds.top, window.innerHeight - height - 20))}px`;
        preview.replaceChildren(clone);
        active = card;
        card.setAttribute('aria-expanded', 'true');
        preview.hidden = false;
    }

    document.addEventListener('click', event => {
        if (preview.contains(event.target)) return;
        const card = event.target.closest?.('[data-credential-zoom]');
        if (card) { event.preventDefault(); showZoom(card); }
        else hideZoom();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            const previous = active;
            hideZoom();
            previous?.focus({ preventScroll: true });
        } else if ((event.key === 'Enter' || event.key === ' ') && event.target.matches?.('[data-credential-zoom]')) {
            event.preventDefault();
            showZoom(event.target);
        }
    });
    document.addEventListener('focusin', event => { if (active && !active.contains(event.target) && !preview.contains(event.target)) hideZoom(); });
    document.addEventListener('scroll', hideZoom, true);
    window.addEventListener('resize', hideZoom);
    window.addEventListener('pagehide', hideZoom);
    window.addEventListener('beforeprint', hideZoom);
    const button = document.getElementById('print-credential');
    button?.addEventListener('click', async () => {
        button.disabled = true;
        try {
            await Promise.all(Array.from(document.querySelectorAll('.credential-sheet img'), image => image.decode?.().catch(() => {})));
            await document.fonts?.ready;
            window.print();
        } finally {
            button.disabled = false;
        }
    });
});
