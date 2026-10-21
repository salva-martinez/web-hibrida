/**
 * Confirmaciones antes de enviar un formulario.
 *
 * Sustituye a los "onsubmit=\"return confirm(...)\"" que estaban embebidos en
 * las vistas: ahora cada formulario lleva un atributo data-confirmar y este
 * módulo decide si se envía o no.
 */
export function initConfirmaciones() {
    document.querySelectorAll('form[data-confirmar]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirmar)) {
                event.preventDefault();
            }
        });
    });
}
