# Arquitectura de Base de Datos

## Entidades Principales

1. **Users**
   - Sistema de usuarios base con dos roles: `fisio` y `paciente`.
   - `fisio_id` (nullable): propietario del registro. Para un **paciente** es el
     fisioterapeuta que lo lleva; para un **fisio** es `NULL` (cada fisio es su
     propia isla y no depende de ningún otro profesional).
   - `deleted_at`: borrado lógico. Una cuenta desactivada desaparece de las listas
     y no puede iniciar sesión, pero **todos sus datos se conservan**.
   - `email_verified_at`: obligatorio para entrar en el panel del fisioterapeuta.
2. **Pacientes** (filas de `users` con `role = 'paciente'`)
   - Datos clínicos, historial, etc. Cada paciente pertenece a un único fisio.
3. **Estímulos**
   - Categorías o tipos de acciones base (fuerza, movilidad, neuro, etc).
   - `fisio_id` + `deleted_at`: catálogo **privado por fisioterapeuta**.
4. **Ejercicios**
   - Tareas específicas, relacionadas o categorizadas por los estímulos.
   - `fisio_id` + `deleted_at`: catálogo **privado por fisioterapeuta**.
5. **Planes**
   - Colección de días de entrenamiento (Plan Semanal) asignados a un paciente.
   - `fisio_id`: propietario (se rellena solo con el usuario autenticado).
   - `deleted_at`: archivado lógico. Archivar un plan **no** destruye sus días,
     sus ejercicios pautados ni el feedback clínico ya registrado.
6. **Plan_Dias**
   - Representa un día específico (Día 1, Día 2, etc.) dentro del Plan Semanal.
7. **Plan_Ejercicios** (Tabla pivote)
   - Relaciona un `Plan_Dia` con los Ejercicios, incluyendo las pautas específicas
     (repeticiones, series).
8. **Feedback**
   - Retorno del paciente respecto a un `Plan_Dia` específico.
   - **Campos IA:** Campos para almacenar análisis automatizado por IA de la
     evolución del paciente en ese día.
9. **Fisio_Invitations** (HUERFANA)
   - La tabla sigue existiendo por histórico de migraciones, pero el registro por
     invitación se sustituyó por el **registro abierto con verificación por email**.
     El modelo, el controlador y las vistas se han eliminado.

## Relaciones Principales
- Un Fisioterapeuta gestiona N Pacientes (`users.fisio_id`).
- Un Paciente tiene N Planes (semanales).
- Un Plan tiene N Plan_Dias (1 a 7).
- Un Plan_Dia contiene N Ejercicios (ManyToMany a través de Plan_Ejercicios).
- Un Ejercicio pertenece a un Estímulo.
- Un Plan_Dia tiene Feedback asociado.

## Reglas de Integridad

- **Nada se borra físicamente.** `users`, `planes`, `estimulos` y `ejercicios`
  usan borrado lógico (`deleted_at`). La tabla `Papelera` permite restaurar.
- **Aislamiento por propietario.** `planes`, `estimulos` y `ejercicios` filtran
  por `fisio_id` mediante el global scope `App\Scopes\FisioScope`, que se activa
  solo cuando el usuario autenticado es un fisioterapeuta.
- **Relaciones con `withTrashed()`.** Solo se pueden aplicar a relaciones cuyo
  modelo usa `SoftDeletes` (el macro solo existe en esos builders). Aplicarlo a
  `PlanDia` provoca `RelationNotFoundException`.
