/**
 * Chat con el asistente de IA (panel del fisioterapeuta).
 *
 * Pregunta sobre el historial clínico del paciente y muestra la respuesta.
 * Sustituye al JavaScript embebido en la vista.
 */
export function initChatIa() {
    const contenedor = document.getElementById('chat-ia');
    if (!contenedor) {
        return;
    }

    const url = contenedor.dataset.chatUrl;
    const patientId = contenedor.dataset.patientId;

    const mensajes = document.getElementById('chat-mensajes');
    const input = document.getElementById('chat-input');
    const boton = document.getElementById('chat-send');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    if (!mensajes || !input || !boton) {
        return;
    }

    function appendMessage(text, sender) {
        const div = document.createElement('div');
        div.className = `message ${sender}`;
        div.style.cssText = sender === 'user'
            ? 'align-self: flex-end; background: #4f46e5; color: white; padding: .7rem 1rem; border-radius: 12px 12px 2px 12px; max-width: 85%; margin-left: auto;'
            : 'align-self: flex-start; background: white; color: #1f2937; padding: .7rem 1rem; border-radius: 12px 12px 12px 2px; max-width: 85%; white-space: pre-wrap;';
        // textContent (no innerHTML): la respuesta de la IA nunca se interpreta como HTML.
        div.textContent = text;
        mensajes.appendChild(div);
        mensajes.scrollTop = mensajes.scrollHeight;
    }

    async function sendMessage() {
        const message = input.value.trim();
        if (!message) {
            return;
        }

        appendMessage(message, 'user');
        input.value = '';
        input.disabled = true;
        boton.disabled = true;
        boton.textContent = 'Pensando…';

        try {
            const respuesta = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ message: message, patient_id: patientId }),
            });

            const data = await respuesta.json().catch(() => ({}));

            if (respuesta.ok) {
                appendMessage(data.response || 'La IA no ha devuelto ninguna respuesta.', 'ai');
            } else {
                appendMessage('Error: ' + (data.error || `no se pudo conectar con la IA (${respuesta.status})`), 'ai');
            }
        } catch (error) {
            appendMessage('Error de conexión con el servicio de IA.', 'ai');
            console.error(error);
        } finally {
            boton.disabled = false;
            boton.textContent = 'Enviar ➤';
            input.focus();
        }
    }

    boton.addEventListener('click', sendMessage);

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });
}
