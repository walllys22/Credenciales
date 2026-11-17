(() => {
    // Mantiene filas completas y coloca los totales sólo en la última hoja.
    function agruparFilas(alturas, espacio, alturaTotales, limite = 15) {
        const paginas = [{ filas: [], totales: false }];
        let usada = 0;
        alturas.forEach((altura, indice) => {
            if (paginas.at(-1).filas.length && (usada + altura > espacio || paginas.at(-1).filas.length >= limite)) {
                paginas.push({ filas: [], totales: false });
                usada = 0;
            }
            paginas.at(-1).filas.push(indice);
            usada += altura;
        });
        if (alturaTotales > 0) {
            if (paginas.at(-1).filas.length && usada + alturaTotales > espacio) {
                paginas.push({ filas: [], totales: false });
            }
            paginas.at(-1).totales = true;
        }
        return paginas;
    }

    if (typeof module !== 'undefined' && module.exports) module.exports = { agruparFilas };
    if (typeof document === 'undefined') return;

    document.addEventListener('DOMContentLoaded', async () => {
        const source = document.getElementById('report-source');
        const preview = document.getElementById('report-preview-pages');
        const navigation = document.getElementById('report-page-navigation');
        if (!source || !preview || !navigation) return;
        const previous = document.getElementById('report-previous-page');
        const next = document.getElementById('report-next-page');
        const status = document.getElementById('report-page-status');
        let pages = [];
        let current = 0;
        let resizeTimer;
        let scrollFrame;
        let lastWidth = 0;

        function actualizarControles() {
            navigation.hidden = pages.length < 2;
            previous.disabled = current === 0;
            next.disabled = current >= pages.length - 1;
            status.textContent = `Página ${current + 1} de ${pages.length}`;
        }

        function actualizarPaginaVisible() {
            scrollFrame = null;
            if (!pages.length) return;
            const reference = Math.min(160, window.innerHeight / 3);
            current = 0;
            pages.forEach((page, index) => {
                if (page.getBoundingClientRect().top <= reference) current = index;
            });
            actualizarControles();
        }

        function crearVista() {
            // No sustituir nodos durante beforeprint: Chrome puede omitir su pintura.
            if (window.matchMedia('print').matches) return;
            source.classList.remove('report-source-with-preview');
            const rows = [...source.querySelector('.report-participants').rows];
            const totals = source.querySelector('.report-payment-totals');
            const headerHeight = source.querySelector('.report-page-header').getBoundingClientRect().height + source.querySelector('thead').getBoundingClientRect().height;
            const pxPerMm = 96 / 25.4;
            // Carta: 279.4 mm, márgenes 10+10 mm y 18 mm reservados al pie.
            const available = Math.max(1, (279.4 - 20 - 18) * pxPerMm - headerHeight - 4);
            const groups = agruparFilas(rows.map(row => row.getBoundingClientRect().height), available, totals.getBoundingClientRect().height);
            const fragment = document.createDocumentFragment();
            pages = groups.map((group, index) => {
                const page = source.cloneNode(true);
                page.removeAttribute('id');
                page.classList.add('report-preview-page');
                page.setAttribute('aria-label', `Página ${index + 1} de ${groups.length}`);
                page.querySelector('.report-participants').replaceChildren(...group.filas.map(row => rows[row].cloneNode(true)));
                if (group.filas.length === 15 && !group.totales) {
                    [...page.querySelector('.report-participants').rows].forEach(row => { row.style.height = `${available / 15}px`; });
                }
                if (!group.totales) page.querySelector('.report-payment-totals').remove();
                page.querySelector('.report-print-footer').remove();
                fragment.append(page);
                return page;
            });
            preview.replaceChildren(fragment);
            preview.hidden = false;
            source.classList.add('report-source-with-preview');
            current = Math.min(current, pages.length - 1);
            actualizarControles();
        }

        function irA(indice) {
            current = Math.max(0, Math.min(indice, pages.length - 1));
            pages[current]?.scrollIntoView({
                block: 'start',
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            });
            actualizarControles();
        }
        previous.addEventListener('click', () => irA(current - 1));
        next.addEventListener('click', () => irA(current + 1));
        window.addEventListener('scroll', () => {
            if (!scrollFrame) scrollFrame = window.requestAnimationFrame(actualizarPaginaVisible);
        }, { passive: true });

        function reprogramar() {
            window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(crearVista, 120);
        }
        window.addEventListener('resize', reprogramar);
        window.addEventListener('afterprint', reprogramar);
        await document.fonts?.ready;
        try { await source.querySelector('.report-letterhead').decode(); } catch { /* La impresión informa el error de imagen. */ }
        crearVista();
        window.prepararHojasReporte = () => crearVista();
        if (typeof ResizeObserver !== 'undefined') {
            const observer = new ResizeObserver(entries => {
                const width = entries[0].contentRect.width;
                if (Math.abs(width - lastWidth) > 1) {
                    lastWidth = width;
                    reprogramar();
                }
            });
            observer.observe(document.getElementById('main-content'));
        }
    });
})();
