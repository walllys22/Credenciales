document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('print-certificate');
    button?.addEventListener('click', async () => {
        button.disabled = true;
        try {
            await document.fonts.load('18pt "Agnesa Kyoto"');
            await document.fonts.ready;
            await Promise.all(Array.from(document.querySelectorAll('.certificate img'), image => image.decode?.().catch(() => {})));
            window.print();
        } finally {
            button.disabled = false;
        }
    });
});
