/**
 * Estado del botón de envío del feedback del paciente.
 *
 * Evita el doble envío y explica que la IA está procesando la respuesta en
 * segundo plano. Sustituye al JavaScript embebido en la vista.
 */
export function initFeedbackForms() {
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
