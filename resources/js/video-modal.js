/**
 * Modal de vídeo (panel del paciente).
 *
 * Muestra el vídeo de YouTube del ejercicio en un modal y las pestañas de
 * día del plan. Sustituye al JavaScript embebido en la vista.
 */
export function initVideoModal() {
    const modal = document.getElementById('videoModal');
    const frame = document.getElementById('videoFrame');
    const titulo = document.getElementById('videoTitle');

    // -----------------------------------------------------------------
    // Modal de vídeo
    // -----------------------------------------------------------------
    function openVideo(url, title) {
        if (!url || !modal || !frame) {
            return;
        }

        frame.src = url;
        if (titulo) {
            titulo.textContent = title;
        }
        modal.classList.add('active');
    }

    function closeVideo(event) {
        // Solo se cierra si se ha pulsado fuera del contenido del modal.
        if (event && event.target !== event.currentTarget) {
            return;
        }

        if (frame) {
            frame.src = ''; // detiene la reproducción
        }
        if (modal) {
            modal.classList.remove('active');
        }
    }

    if (modal) {
        modal.addEventListener('click', closeVideo);

        document.querySelectorAll('[data-video-close]').forEach((boton) => {
            boton.addEventListener('click', () => closeVideo());
        });

        document.querySelectorAll('[data-video-url]').forEach((elemento) => {
            elemento.addEventListener('click', (e) => {
                e.preventDefault();
                openVideo(elemento.dataset.videoUrl, elemento.dataset.videoTitle);
            });
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeVideo();
        }
    });

    // -----------------------------------------------------------------
    // Pestañas de día
    // -----------------------------------------------------------------
    function switchDayTab(dayNum) {
        document.querySelectorAll('[id^="day-content-"]').forEach((el) => {
            el.style.display = 'none';
        });

        document.querySelectorAll('[id^="tab-btn-"]').forEach((btn) => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });

        const content = document.getElementById(`day-content-${dayNum}`);
        const btn = document.getElementById(`tab-btn-${dayNum}`);

        if (content) {
            content.style.display = 'block';
        }
        if (btn) {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary');
        }
    }

    document.querySelectorAll('[data-day-tab]').forEach((btn) => {
        btn.addEventListener('click', () => switchDayTab(btn.dataset.dayTab));
    });
}
