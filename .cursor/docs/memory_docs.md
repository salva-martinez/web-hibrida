# Memoria del Proyecto: Web Híbrida

## Resumen del Proyecto
Aplicación web diseñada para la gestión clínica en el área de fisioterapia. Facilita la comunicación y el seguimiento entre dos perfiles principales: **Fisioterapeuta (Admin)** y **Paciente**. El fisioterapeuta puede asignar planes de ejercicios, basados en estímulos, y el paciente puede visualizar sus planes y proporcionar feedback, el cual incluye integración con IA.

## Stack Tecnológico
- **Backend:** PHP 8.2+, Laravel 12 (v12.62.0)
- **Frontend:** TailwindCSS v4, Vite, Blade Templates, Vanilla JS
- **Base de Datos:** SQLite (desarrollo) / Relacional estándar (producción). Gestionada vía Migraciones de Laravel.
- **Autorización:** Sesiones nativas de Laravel, segmentadas por middleware (`role:fisio`, `role:paciente`).

## Actualizaciones Recientes
- **14 de Junio de 2026:** Refactorización de la arquitectura de Planes para soportar "Planificador Semanal Dinámico". Introducción del modelo `PlanDia` para agrupar ejercicios por días de la semana y permitir Feedback y análisis de IA por cada día independiente.
- **13 de Junio de 2026:** Framework y dependencias actualizadas a la última versión estable (Laravel 12.62.0, Symfony 7.4.x / 8.1.x, etc.). Se garantizan las mejoras de seguridad y rendimiento.

## Arquitectura Base
- **Patrón MVC extendido:** Controladores separados en namespaces (`Admin\`, `Paciente\`, `Auth\`).
- **Capa de Servicios:** Lógica de negocio compleja alojada en `app/Services/`.
- **Rutas:** Definidas en `routes/web.php` con prefijos `/admin` y `/paciente`.

## Estado Actual
- **Autenticación:** Implementada y funcional (Login, Logout) con redirecciones basadas en el rol.
- **Panel Fisio (Admin):** CRUD básico implementado para Pacientes, Estímulos, Ejercicios y Planes de ejercicios. Sistema de chat iniciado.
- **Panel Paciente:** Dashboard implementado, visualización de planes de ejercicios e interfaz de feedback (con campos AI).

## Notas Importantes
- *Siempre consultar este documento antes de generar nuevas funcionalidades estructurales.*
