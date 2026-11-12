window.showAppMessage = (message, type = 'danger') => {
    const container = document.getElementById('app-notifications');
    if (!container) return;
    const alert = document.createElement('div');
    alert.className = `alert alert-${['success', 'danger', 'warning', 'info'].includes(type) ? type : 'info'}`;
    alert.setAttribute('role', type === 'danger' ? 'alert' : 'status');
    alert.textContent = message;
    container.append(alert);
    window.setTimeout(() => alert.remove(), 3000);
};

document.addEventListener('DOMContentLoaded', () => {
    const notifications = document.getElementById('app-notifications');
    document.querySelectorAll('.alert').forEach(alert => {
        notifications?.append(alert);
        window.setTimeout(() => alert.remove(), 3000);
    });
    const toggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const collapse = document.getElementById('sidebar-collapse');
    const setCollapsed = collapsed => { document.body.classList.toggle('sidebar-collapsed', collapsed); collapse?.setAttribute('aria-expanded', String(!collapsed)); collapse?.setAttribute('aria-label', collapsed ? 'Expandir menú' : 'Contraer menú'); };
    try { setCollapsed(localStorage.getItem('sidebar-collapsed') === 'true'); } catch (_) {}
    collapse?.addEventListener('click', () => { const collapsed = !document.body.classList.contains('sidebar-collapsed'); setCollapsed(collapsed); try { localStorage.setItem('sidebar-collapsed', String(collapsed)); } catch (_) {} });
    document.getElementById('clear-cache-form')?.addEventListener('submit', event => { const button = event.currentTarget.querySelector('button'); button.disabled = true; button.setAttribute('aria-busy', 'true'); });
    function closeMenu() { sidebar.classList.remove('open'); toggle.setAttribute('aria-expanded', 'false'); }
    toggle?.addEventListener('click', () => { const open = sidebar.classList.toggle('open'); toggle.setAttribute('aria-expanded', String(open)); });
    document.addEventListener('click', event => { if (sidebar?.classList.contains('open') && !sidebar.contains(event.target) && !toggle.contains(event.target)) closeMenu(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && sidebar?.classList.contains('open')) { closeMenu(); toggle.focus(); } });
    document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => { if (!window.confirm(form.dataset.confirm)) event.preventDefault(); }));
    const inicio = document.getElementById('fecha_inicio');
    const final = document.getElementById('fecha_final');
    if (inicio && final) { const update = () => { final.min = inicio.value; }; inicio.addEventListener('change', update); update(); }
});
