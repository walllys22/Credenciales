const passwordInput = document.getElementById('password');
const passwordToggle = document.getElementById('toggle-password');
const passwordEyeSlash = document.getElementById('password-eye-slash');

passwordToggle?.addEventListener('click', () => {
    const visible = passwordInput.type === 'password';
    passwordInput.type = visible ? 'text' : 'password';
    const label = visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
    passwordToggle.setAttribute('aria-label', label);
    passwordToggle.setAttribute('aria-pressed', String(visible));
    passwordToggle.title = label;
    passwordEyeSlash.style.display = visible ? '' : 'none';
    passwordEyeSlash.toggleAttribute('hidden', !visible);
});
