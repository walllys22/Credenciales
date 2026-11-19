document.addEventListener('DOMContentLoaded', () => {
    const pages = [...document.querySelectorAll('.batch-page')];
    const navigation = document.querySelector('.batch-page-navigation');
    if (!navigation || !pages.length) return;
    const previous = navigation.querySelector('[data-batch-previous]');
    const next = navigation.querySelector('[data-batch-next]');
    const status = navigation.querySelector('[data-batch-status]');
    let current = 0;
    let frame = null;

    function update() {
        navigation.hidden = pages.length < 2;
        previous.disabled = current === 0;
        next.disabled = current === pages.length - 1;
        status.textContent = `Página ${current + 1} de ${pages.length}`;
    }

    function goTo(index) {
        current = Math.max(0, Math.min(index, pages.length - 1));
        pages[current].scrollIntoView({
            block: 'start',
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        });
        update();
    }

    previous.addEventListener('click', () => goTo(current - 1));
    next.addEventListener('click', () => goTo(current + 1));
    window.addEventListener('scroll', () => {
        if (frame !== null) return;
        frame = window.requestAnimationFrame(() => {
            frame = null;
            const reference = Math.min(160, window.innerHeight / 3);
            current = 0;
            pages.forEach((page, index) => {
                if (page.getBoundingClientRect().top <= reference) current = index;
            });
            update();
        });
    }, { passive: true });
    update();
});
