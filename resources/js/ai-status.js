/**
 * Consulta periódica del estado del análisis de IA.
 *
 * El feedback del paciente se guarda al instante, pero el informe que genera
 * Gemini se produce en segundo plano (cola de Laravel). Sin esto, el
 * fisioterapeuta tenía que recargar la página a mano para verlo.
 *
 * El polling solo se activa en los elementos que están realmente pendientes:
 * cuando el análisis llega, el elemento deja de pedirlo.
 */

const INTERVALO_MS = 5000;
const TIMEOUT_MS = 120000; // 2 minutos: a partir de ahí se deja de insistir.

function TextoEstado(elemento, texto, clase) {
    elemento.textContent = texto;
    elemento.className = 'badge ' + clase;
}

async function consultar(elemento) {
    const url = elemento.dataset.analisisUrl;

    try {
        const respuesta = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!respuesta.ok) {
            return false;
        }

        const datos = await respuesta.json();

        if (datos.estado === 'listo' && datos.analisis) {
            TextoEstado(elemento, '🤖 Análisis disponible', 'badge-success');
            elemento.removeAttribute('data-analisis-url');
            elemento.dataset.estado = 'listo';

            // Se recarga la página para que aparezca el informe completo.
            window.setTimeout(() => window.location.reload(), 600);

            return true;
        }

        // La IA no ha podido generar el informe (cuota agotada, clave
        // inválida...). Se explica y se deja de preguntar: no va a cambiar
        // solo y el panel tiene que decirlo.
        if (datos.estado === 'error') {
            TextoEstado(elemento, '🤖 IA no disponible', 'badge-danger');
            elemento.title = datos.mensaje || '';
            elemento.removeAttribute('data-analisis-url');
            elemento.dataset.estado = 'error';

            return true;
        }

        return false;
    } catch (error) {
        // Si falla la red se reintenta en el siguiente ciclo.
        return false;
    }
}

function iniciar(elemento) {
    const inicio = Date.now();

    const intervalo = window.setInterval(async () => {
        if (Date.now() - inicio > TIMEOUT_MS) {
            window.clearInterval(intervalo);
            TextoEstado(elemento, 'Pendiente', 'badge-warning');
            elemento.removeAttribute('data-analisis-url');
            return;
        }

        const listo = await consultar(elemento);

        if (listo) {
            window.clearInterval(intervalo);
        }
    }, INTERVALO_MS);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-analisis-url]').forEach((elemento) => {
        if (elemento.dataset.estado === 'pendiente') {
            iniciar(elemento);
        }
    });
});
