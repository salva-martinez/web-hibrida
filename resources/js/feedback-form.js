/**
 * Estado del botón de envío del feedback del paciente.
 *
 * Evita el doble envío y explica que la IA está procesando la respuesta en
 * segundo plano. Sustituye al JavaScript embebido en la vista.
 */
export function initFeedbackForms() {
    // El slider de dureza actualiza su lectura en tiempo real.
    document.querySelectorAll('[data-dureza-display]').forEach((input) => {
        const lectura = document.getElementById(input.dataset.durezaDisplay);

        if (!lectura) {
            return;
        }

        input.addEventListener('input', () => {
            lectura.textContent = input.value;
        });
    });

    document.querySelectorAll('.feedbackForm').forEach((form) => {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('.submitBtn');

            if (btn) {
                btn.disabled = true;
                btn.textContent = '⏳ Enviando feedback…';
                btn.style.opacity = '0.7';
                btn.style.cursor = 'wait';
            }
        });
    });
}
