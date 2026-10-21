/**
 * Pestañas del formulario de acceso (fisioterapeuta / paciente).
 *
 * Sustituye al JavaScript embebido en la vista.
 */
export function initLoginTabs() {
    const contenedor = document.getElementById('login-tabs');
    if (!contenedor) {
        return;
    }

    function switchTab(button, type) {
        document.querySelectorAll('.login-tab').forEach((t) => t.classList.remove('active'));
        document.querySelectorAll('.login-form').forEach((f) => f.classList.remove('active'));

        button.classList.add('active');

        const form = document.getElementById('form-' + type);
        if (form) {
            form.classList.add('active');
        }
    }

    document.querySelectorAll('[data-login-tab]').forEach((btn) => {
        btn.addEventListener('click', () => switchTab(btn, btn.dataset.loginTab));
    });
}
