# Memoria del Proyecto: Web Híbrida

## Resumen del Proyecto
Aplicación web diseñada para la gestión clínica en el área de fisioterapia. Facilita la comunicación y el seguimiento entre dos perfiles principales: **Fisioterapeuta (Admin)** y **Paciente**. El fisioterapeuta puede asignar planes de ejercicios, basados en estímulos, y el paciente puede visualizar sus planes y proporcionar feedback, el cual incluye integración con IA.

## Stack Tecnológico
- **Backend:** PHP 8.2+, Laravel 12 (v12.62.0)
- **Frontend:** Design system CSS propio (`resources/css/app.css`), Vite, Blade Templates, JavaScript modular (ES modules en `resources/js/`). **No se usa Tailwind.**
- **Base de Datos:** SQLite (desarrollo) / Relacional estándar (producción). Gestionada vía Migraciones de Laravel.
- **Autorización:** Sesiones nativas de Laravel, segmentadas por middleware (`role:fisio`, `role:paciente`).

## Actualizaciones Recientes
- **21 de Octubre de 2026:** Aislamiento por fisioterapeuta ("cada fisio es una isla"), registro abierto con verificación por email, borrado lógico con Papelera y primera suite de tests (43 tests). Ver detalle en la sección "Aislamiento Multi-Fisio" más abajo.
- **14 de Junio de 2026:** Refactorización de la arquitectura de Planes para soportar "Planificador Semanal Dinámico". Introducción del modelo `PlanDia` para agrupar ejercicios por días de la semana y permitir Feedback y análisis de IA por cada día independiente.
- **13 de Junio de 2026:** Framework y dependencias actualizadas a la última versión estable (Laravel 12.62.0, Symfony 7.4.x / 8.1.x, etc.). Se garantizan las mejoras de seguridad y rendimiento.

## Arquitectura Base
- **Patrón MVC extendido:** Controladores separados en namespaces (`Admin\`, `Paciente\`, `Auth\`).
- **Capa de Servicios:** Lógica de negocio compleja alojada en `app/Services/`.
- **Rutas:** Definidas en `routes/web.php` con prefijos `/admin` y `/paciente`.

## Estado Actual
- **Autenticación:** Login de fisio (email + contraseña, **con verificación de email obligatoria**) y login de paciente (nombre + 2 apellidos + fecha de nacimiento). Registro abierto de fisioterapeutas en `/register`.
- **Aislamiento:** Cada fisio solo ve y toca sus pacientes, planes, ejercicios y estímulos.
- **Papelera:** Nada se borra físicamente; todo se desactiva y se puede restaurar.
- **Panel Fisio (Admin):** CRUD de Pacientes, Estímulos, Ejercicios y Planes, Chat IA y Papelera.
- **Panel Paciente:** Dashboard, visualización de planes con vídeo e interfaz de feedback (con análisis IA).
- **Tests:** 43 tests en `tests/Feature` y `tests/Unit`. Ejecutar con `php artisan test`.

## Notas Importantes
- *Siempre consultar este documento antes de generar nuevas funcionalidades estructurales.*

---

## Aislamiento Multi-Fisio — 21 de Octubre de 2026

Implementación del modelo "cada fisio es una isla": los pacientes, planes y el
catálogo de ejercicios de un fisioterapeuta nunca son visibles para otro.

### Decisiones de diseño
- **Alcance:** Se eligió el aislamiento total (no el modelo por clínica/equipo).
  Cada fisio gestiona su propia cartera y su propio catálogo.
- **Registro:** Abierto, con verificación de email obligatoria antes de entrar al
  panel. Se eliminó el sistema de invitación por token (tabla `fisio_invitations`
  queda huérfana por histórico de migraciones).
- **Borrado:** Nada se borra físicamente. `users`, `planes`, `estimulos` y
  `ejercicios` usan `SoftDeletes` y se recuperan desde la sección **Papelera**.

### Piezas técnicas
| Pieza | Ubicación | Función |
|---|---|---|
| Global scope | `app/Scopes/FisioScope.php` | Filtra por `fisio_id` cuando el usuario autenticado es un fisio. Aplicado a `Plan`, `Estimulo`, `Ejercicio`. |
| Scopes explícitos | `User::scopePacientes()`, `User::scopeDeFisio()` | Filtran la cartera de pacientes sin depender de la sesión. |
| Autorización | `User::perteneceAlFisioAutenticado()` + `autorizarPaciente()` en los controladores | Comprobación explícita de pertenencia (evita depender solo del scope). |
| Verificación de email | `Auth\VerifyEmailController` + middleware `verified` | La cuenta nace inactiva; el enlace firmado la activa. |
| Papelera | `Admin\PapeleraController` + `admin/papelera.blade.php` | Lista y restaura lo desactivado. |

### Reglas a no olvidar
1. **Cualquier modelo nuevo con datos clínicos debe llevar `fisio_id`** y aplicar el
   global scope, o quedaría fuera del alcance de otros profesionales.
2. **El modelo `User` NO lleva el global scope** a propósito: el propio fisio tiene
   `fisio_id = NULL` y se quedaría fuera de sus propias consultas.
3. **`withTrashed()` solo funciona en relaciones cuyo modelo usa `SoftDeletes`.**
   Aplicarlo a `PlanDia` lanza `RelationNotFoundException` ("relación inexistente"),
   que es un error muy difícil de diagnosticar. Ver `tests/Feature/RelacionesEagerLoadingTest.php`.
4. **Las relaciones `belongsTo` hacia un modelo con `SoftDeletes` sí admiten
   `withTrashed()`** (se hace forward al builder del modelo relacionado).

### Hallazgos corregidos en la misma tanda
- 🔴 **CRÍTICO:** `DELETE /admin/pacientes/{id}` borraba físicamente cualquier
  usuario, **incluidos los fisioterapeutas**. Ahora solo se puede desactivar un
  paciente propio y nunca se borra nada físicamente.
- 🟠 **ALTO:** El clon de un plan de N días solo conservaba el **Día 1** (los días
  2..N se perdían en silencio). Ahora el selector de días se preselecciona con el
  número de días del plan de origen.
- 🟠 **ALTO:** XSS almacenado en `paciente/plan.blade.php` (el nombre del ejercicio
  se interpolaba en un `onclick` inline). Corregido con `Js::from()`.
- 🟠 **ALTO:** XSS almacenado en el constructor de planes (`innerHTML` con datos de
  la BD). Corregido construyendo el DOM con `createElement` + `textContent`.
- 🟡 **MEDIO:** El dashboard del fisio cargaba feedbacks de otros profesionales y
  fallaba con "Attempt to read property titulo on null". Corregido filtrando por
  `whereHas('planDia.plan')` y usando operadores null-safe en la vista.
- 🟡 **MEDIO:** La migración `2026_06_14` fallaba en SQLite al hacer `dropColumn`
  dejando la clave foránea huérfana. Corregida separando `dropForeign` y `dropColumn`.
- 🟡 **MEDIO:** El seeder creaba **dos `PlanDia` con el mismo `numero_dia = 1`** en
  cada plan con feedback, dejando el feedback colgado de un día vacío. Corregido.
- 🟡 **MEDIO:** La fecha histórica del feedback sembrado se perdía (`created_at` no
  está en el `$fillable`). Corregido con `forceFill()`.

---

## Auditoría de Código — 2 de Julio de 2026

Auditoría completa realizada sobre todo el codebase. Se identificaron 10 hallazgos.

**Actualización 2 de Julio de 2026:** Los 10 hallazgos han sido corregidos (ver detalle de cada uno más abajo). Cambios destacados:
- Ruta de feedback corregida (`{planDia}`) y `updatePlan` reescrito para no borrar/recrear `PlanDia` (preserva feedback), desactivando otros planes activos al guardar.
- Login de pacientes ahora exige `fecha_nacimiento` como segundo factor (nueva columna en `users`, migración `2026_07_02_174252_add_fecha_nacimiento_to_users_table`). Los pacientes ya existentes en la BD de desarrollo se re-poblaron con su fecha; un paciente creado manualmente (`Alex Martinez Teran`, id 6) quedó sin fecha y no podrá iniciar sesión hasta que se edite su ficha desde el panel admin.
- Contraseña de pacientes generada con `Str::random(32)` en vez de `'password'` hardcoded (ya no se usa para login).
- Eager loading y vista de `admin/pacientes/show` adaptados a `planDias.feedback`; HTML malformado corregido.
- Rate limiting (`throttle:10,1`) añadido a los endpoints que llaman a Gemini.
- Prompt de Gemini delimita el comentario del paciente para mitigar prompt injection.
- `embed_url` devuelve `null` para vídeos no-YouTube.

### 🔴 CRÍTICO-1 — El envío de feedback siempre falla con error 500

**Archivos:** `routes/web.php:40` + `app/Http/Controllers/Paciente/PlanController.php:38`

La ruta define el parámetro `{plan}` pero el controlador espera `\App\Models\PlanDia $planDia`. Laravel no puede hacer implicit route model binding porque los nombres no coinciden. El contenedor IoC resuelve `$planDia` como una instancia vacía de PlanDia (con `id = null`). Al acceder a `$planDia->plan->paciente_id` en la línea 41, la relación devuelve `null` y PHP lanza un fatal error.

**Estado:** ✅ CORREGIDO  
**Fix:** Cambiar la ruta de `{plan}` a `{planDia}` en `routes/web.php:40`.

---

### 🔴 CRÍTICO-2 — Login de pacientes sin contraseña (autenticación solo por nombre)

**Archivo:** `app/Http/Controllers/Auth/LoginController.php:49-58`

Los pacientes no necesitan ningún secreto para autenticarse. Cualquier persona que conozca el nombre completo de un paciente puede acceder a su cuenta y ver sus planes, historial y datos clínicos. El `Auth::login($user, true)` crea además una sesión permanente.

**Estado:** ✅ CORREGIDO  
**Fix:** Añadir un PIN numérico, fecha de nacimiento, o cualquier secreto compartido como segundo factor de verificación de identidad.

---

### 🟠 ALTO-1 — Editar un plan elimina todo el historial de feedback del paciente

**Archivo:** `app/Services/PlanService.php:61`

`$plan->planDias()->delete()` en `updatePlan` cascada a la tabla `feedback` (definido como `ON DELETE CASCADE` en la migración `2026_06_14`). Cualquier edición menor del plan (título, fecha, ejercicio) destruye permanentemente todo el feedback clínico registrado por el paciente.

**Estado:** ✅ CORREGIDO  
**Fix:** Actualizar/parchear los `PlanDia` existentes en lugar de borrar y recrear. Solo eliminar los días que ya no estén presentes en el nuevo listado.

---

### 🟡 MEDIO-1 — `updatePlan` no desactiva otros planes al activar uno existente

**Archivo:** `app/Services/PlanService.php:50-67`

`createPlan` desactiva correctamente los demás planes activos del paciente antes de crear uno nuevo (línea 23-27), pero `updatePlan` no tiene esta lógica. Si el fisio edita un plan y lo marca como activo, el paciente puede terminar con múltiples planes activos simultáneamente.

**Estado:** ✅ CORREGIDO  
**Fix:** Añadir la misma lógica de desactivación al inicio de `updatePlan`, condicionada a `$data['activo'] === true`.

---

### 🟡 MEDIO-2 — Relaciones obsoletas tras el refactor a `plan_dias` (feedback invisible en admin)

**Archivo:** `app/Http/Controllers/Admin/PacienteController.php:54`

Tras la migración del 14 de junio, `PacienteController::show` carga relaciones que ya no existen directamente en el modelo `Plan`:
- `planesComoPaciente.feedback` — `Plan` no tiene `hasOne(Feedback)`.
- `planesComoPaciente.planEjercicios` — `Plan` no tiene `hasMany(PlanEjercicio)`.

La columna "Feedback" en `admin/pacientes/show.blade.php` siempre aparece vacía aunque el paciente haya registrado feedback.

**Estado:** ✅ CORREGIDO  
**Fix:** Cambiar el eager load a `planesComoPaciente.planDias.feedback` y adaptar la vista para iterar `$plan->planDias`.

---

### 🟡 MEDIO-3 — HTML malformado en la vista de detalle de paciente

**Archivo:** `resources/views/admin/pacientes/show.blade.php:60-61`

```blade
<style="color: var(--text-secondary)">   ← Falta el nombre de la etiqueta
    {{ Str::limit($plan->feedback->comentario, 40) }}</small>  ← Cierre sin apertura
```

El navegador ignora la etiqueta malformada y el texto del comentario se renderiza sin estilos. El `</small>` huérfano puede romper el layout.

**Estado:** ✅ CORREGIDO  
**Fix:** Reemplazar con `<small style="color: var(--text-secondary)">`.

---

### 🟡 MEDIO-4 — Sin rate limiting en endpoints que consumen la API de Gemini

**Archivo:** `routes/web.php:33,40`

Los endpoints `POST /admin/chat/send` y `POST /paciente/plan/{plan}/feedback` llaman a la API de Gemini sin ningún control de velocidad. Un usuario autenticado puede enviar cientos de peticiones por segundo, agotando la cuota o el presupuesto de la API.

**Estado:** ✅ CORREGIDO  
**Fix:** Añadir `->middleware('throttle:10,1')` (10 req/min) a ambas rutas en `routes/web.php`.

---

### 🟢 BAJO-1 — Prompt injection vía comentario del paciente

**Archivo:** `app/Services/GeminiService.php:31-35`

El campo `$comentario` del paciente se interpola directamente en el prompt clínico sin delimitadores ni saneamiento. Un paciente podría escribir instrucciones adversariales en el comentario para manipular el análisis clínico que la IA genera para el fisioterapeuta.

**Estado:** ✅ CORREGIDO  
**Fix:** Delimitar el contenido del usuario con marcadores explícitos (ej: `[INICIO_COMENTARIO]...[FIN_COMENTARIO]`) e indicar en el prompt del sistema que no se deben seguir instrucciones dentro de ese bloque.

---

### 🟢 BAJO-2 — `embed_url` devuelve URLs arbitrarias para vídeos no-YouTube

**Archivo:** `app/Models/Ejercicio.php:53`

Si `video_url` no coincide con ningún patrón de YouTube, `getEmbedUrlAttribute()` devuelve la URL original sin modificar (línea 53). Esta URL acaba cargada en un `<iframe>` en la vista del paciente, pudiendo mostrar contenido inesperado.

**Estado:** ✅ CORREGIDO  
**Fix:** Devolver `null` si la URL no es YouTube, en lugar de retornar la URL original.

---

### 🟢 BAJO-3 — Contraseña por defecto hardcoded para todos los pacientes

**Archivo:** `app/Http/Controllers/Admin/PacienteController.php:44`

Todos los pacientes se crean con `bcrypt('password')`. Si en el futuro se añade autenticación por contraseña, todos los usuarios existentes tendrían la misma contraseña conocida públicamente.

**Estado:** ✅ CORREGIDO  
**Fix:** Generar un token aleatorio con `Str::random(12)` o dejar el campo `null` hasta implementar el flujo de activación de cuenta.

---

### Tabla Resumen

| # | Severidad | Descripción | Archivo principal | Estado |
|---|-----------|-------------|-------------------|--------|
| 1 | 🔴 Crítico | Feedback siempre crashea (route mismatch `{plan}` vs `$planDia`) | `Paciente/PlanController.php:38` | ✅ Corregido |
| 2 | 🔴 Crítico | Login paciente sin contraseña (solo nombre completo) | `Auth/LoginController.php:49` | ✅ Corregido |
| 3 | 🟠 Alto | Editar plan borra todo el feedback vía CASCADE | `PlanService.php:61` | ✅ Corregido |
| 4 | 🟡 Medio | `updatePlan` no desactiva otros planes activos | `PlanService.php:50` | ✅ Corregido |
| 5 | 🟡 Medio | Relaciones obsoletas post-refactor, feedback invisible en admin | `PacienteController.php:54` | ✅ Corregido |
| 6 | 🟡 Medio | HTML malformado (`<style=...>` sin nombre de etiqueta) | `show.blade.php:60` | ✅ Corregido |
| 7 | 🟡 Medio | Sin rate limiting en endpoints de Gemini AI | `routes/web.php:33,40` | ✅ Corregido |
| 8 | 🟢 Bajo | Prompt injection vía campo comentario del paciente | `GeminiService.php:31` | ✅ Corregido |
| 9 | 🟢 Bajo | `embed_url` devuelve URLs arbitrarias (no YouTube) | `Ejercicio.php:53` | ✅ Corregido |
| 10 | 🟢 Bajo | Contraseña por defecto `'password'` hardcoded | `PacienteController.php:44` | ✅ Corregido |
