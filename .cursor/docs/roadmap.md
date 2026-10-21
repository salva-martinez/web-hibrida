# Backlog de Mejoras y Roadmap

## Tareas Pendientes (TODO)
- [ ] Enviar el email de verificación por SMTP real (hoy `MAIL_MAILER=log`, el enlace sale por log).
- [ ] Implementar notificaciones para los pacientes cuando se les asigne un plan nuevo.
- [ ] Migrar los datos existentes a la nueva columna `fisio_id` en entornos con varios profesionales (la migración `2026_10_21_090200` asigna todo al primer fisio).
- [ ] Resolver la ambigüedad de "restaurar" cuando un plan archivado era `activo` y el paciente ya tiene otro plan activo (hoy se desactiva el otro).
- [ ] Limpiar la tabla `fisio_invitations` (huérfana desde la retirada del registro por invitación).

## Tareas en Progreso (DOING)
- [ ] ...

## Tareas Completadas (DONE)
- [x] **Aislamiento por fisioterapeuta** ("cada fisio es una isla"): global scope `FisioScope`, `fisio_id` en usuarios/planes/estímulos/ejercicios y verificaciones explícitas de pertenencia (Oct 2026).
- [x] **Registro abierto de fisioterapeutas** con verificación de email obligatoria, sustituyendo al sistema de invitación por token (Oct 2026).
- [x] **Borrado lógico + Papelera**: nada se borra físicamente; pacientes, planes, estímulos y ejercicios se desactivan y se restauran (Oct 2026).
- [x] **Suite de tests**: 43 tests (aislamiento, registro, papelera, relaciones eager-loaded, clon de planes y `PlanService`) (Oct 2026).
- [x] Corrección del XSS almacenado en el `onclick` del vídeo del paciente y en el constructor de planes (Oct 2026).
- [x] Corrección del bug que hacía perder los días 2..N al duplicar un plan (Oct 2026).
- [x] Corrección del fallo que impedía borrar la cuenta de otro fisioterapeuta (Oct 2026).
- [x] Actualización del core a Laravel 12.62.0 y dependencias relacionadas (Junio 2026).
- [x] Setup inicial del proyecto Laravel 12 y Tailwind v4.
- [x] Definición de rutas y middlewares base.
- [x] Estructura de base de datos inicial (migraciones).
