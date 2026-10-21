import { initBootstrap } from './bootstrap';
import './ai-status';
import { initChatIa } from './chat-ia';
import { initFeedbackForms } from './feedback-form';
import { initLoginTabs } from './login-tabs';
import { initPlanEditor } from './plan-editor';
import { initVideoModal } from './video-modal';

document.addEventListener('DOMContentLoaded', () => {
    initBootstrap();

    // Cada módulo comprueba si su elemento existe en la página: así el mismo
    // bundle sirve para todas las vistas sin código condicional.
    initPlanEditor();
    initChatIa();
    initVideoModal();
    initLoginTabs();
    initFeedbackForms();
});
