# Arquitectura de Base de Datos

## Entidades Principales

1. **Users**
   - Sistema de usuarios base. Incluye roles (ej. mediante un campo rol o relación).
2. **Pacientes** (Relacionado con usuarios)
   - Datos clínicos, historial, etc.
3. **Estímulos**
   - Categorías o tipos de acciones base (fuerza, movilidad, neuro, etc).
4. **Ejercicios**
   - Tareas específicas, relacionadas o categorizadas por los estímulos.
13. **Planes**
   - Colección de días de entrenamiento (Plan Semanal) asignados a un paciente.
14. **Plan_Dias (NUEVO)**
   - Representa un día específico (Día 1, Día 2, etc.) dentro del Plan Semanal.
15. **Plan_Ejercicios** (Tabla pivote)
   - Relaciona un `Plan_Dia` con los Ejercicios, incluyendo las pautas específicas (repeticiones, series).
16. **Feedback**
   - Retorno del paciente respecto a un `Plan_Dia` específico.
   - **Campos IA:** Campos para almacenar análisis automatizado por IA de la evolución del paciente en ese día.

## Relaciones Principales
- Un Fisioterapeuta gestiona N Pacientes.
- Un Paciente tiene N Planes (semanales).
- Un Plan tiene N Plan_Dias (1 a 7).
- Un Plan_Dia contiene N Ejercicios (ManyToMany a través de Plan_Ejercicios).
- Un Ejercicio pertenece a un Estímulo.
- Un Plan_Dia tiene Feedback asociado.
