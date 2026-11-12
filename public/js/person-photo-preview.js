(() => {
    const preview = document.createElement('div');
    preview.id = 'person-photo-preview';
    preview.className = 'person-photo-preview';
    preview.hidden = true;
    preview.setAttribute('aria-hidden', 'true');
    const largeImage = document.createElement('img');
    largeImage.alt = '';
    preview.append(largeImage);
    document.body.append(preview);
    let active = null;

    const hide = () => {
        active?.setAttribute('aria-expanded', 'false');
        active = null;
        preview.hidden = true;
        largeImage.removeAttribute('src');
    };

    const show = button => {
        const image = button.querySelector('img');
        if (!image || !button.isConnected) return;
        hide();
        active = button;
        const bounds = button.getBoundingClientRect();
        const size = Math.max(1, Math.min(240, window.innerWidth - 24, window.innerHeight - 24));
        const left = bounds.right + 12 + size <= window.innerWidth - 12 ? bounds.right + 12 : bounds.left - size - 12;
        preview.style.width = `${size}px`;
        preview.style.height = `${size}px`;
        preview.style.left = `${Math.max(12, Math.min(left, window.innerWidth - size - 12))}px`;
        preview.style.top = `${Math.max(12, Math.min(bounds.top, window.innerHeight - size - 12))}px`;
        largeImage.src = image.currentSrc || image.src;
        button.setAttribute('aria-expanded', 'true');
        preview.hidden = false;
    };

    // Native buttons also support Enter and Space, including touch activation.
    document.addEventListener('click', event => {
        const button = event.target.closest?.('[data-person-photo-preview]');
        if (!button || button === active) hide();
        else show(button);
    });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') hide(); });
    document.addEventListener('focusout', event => { if (event.target === active) hide(); });
    document.addEventListener('scroll', hide, true);
    window.addEventListener('resize', hide);
    window.addEventListener('pagehide', hide);
    largeImage.addEventListener('error', hide);
})();
