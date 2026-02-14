# FisioApp - Gestión de Rehabilitación Inteligente 🏋️‍♂️🧠

Aplicación web premium para fisioterapeutas y pacientes que integra Inteligencia Artificial para el seguimiento clínico, diseño de planes dinámicos y análisis de progresión.

---

## 🚀 Nuevas Funcionalidades (v2.0)

### 🤖 Asistente Clínico IA (Admin)
- **Chat Contextual**: El fisioterapeuta puede chatear con una IA que tiene acceso a **todo el historial** del paciente (planes pasados, feedback, notas de dolor).
- **Análisis de Progresión**: Capacidad para detectar patrones de dolor crónico o fatiga acumulada a lo largo de varias semanas.

### 📋 Gestión de Planes Inteligente
- **Duplicador de Planes (Smart Cloning)**: Crea nuevos ciclos de entrenamiento en segundos clonando el plan anterior con un solo clic.
- **Inactivación Automática**: El sistema garantiza que el paciente solo vea su plan más reciente, archivando automáticamente los anteriores.
- **Reordenación de Estímulos**: Lógica automática que mantiene los ejercicios organizados por tipo (Básico, Auxiliar, Metabólico) sin esfuerzo manual.

---

## 🧠 El Cerebro de la App: Gemini AI

La plataforma utiliza la API de **Google Gemini** para transformar datos subjetivos en decisiones clínicas:
1.  **Feedback IA**: Analiza RPE, dolor y evolución tras cada sesión para generar un resumen ejecutivo.
2.  **Clinical Chat**: Permite preguntas complejas como *"¿Por qué Ana ha reportado dolor en la rodilla en los últimos 3 planes?"*.

---

## 📖 Manual de Uso

### 🩺 Perfil Fisioterapeuta (Administrador)
1.  **Gestión de Estímulos**: Define las categorías de trabajo (ej: Fuerza, Movilidad). El orden que asignes aquí se respetará automáticamente en todos los planes.
2.  **Creación de Planes**:
    - Ve a la lista de **Pacientes**.
    - Haz clic en **"Añadir Plan"**.
    - Si el paciente es antiguo, el sistema te preguntará si quieres **Duplicar el plan anterior** o empezar uno de cero.
    - Selecciona los ejercicios, asigna series/reps y guarda. El plan anterior se inactivará solo.
3.  **Seguimiento e IA**:
    - Desde la ficha del paciente o el detalle del plan, revisa el **Análisis de IA** generado tras el feedback del paciente.
    - Usa el **Chat IA** para profundizar en el estado del paciente usando lenguaje natural.

### 🏃 Perfil Paciente
1.  **Acceso**: Login rápido con **Nombre + Primer Apellido + Segundo Apellido**. (Password: `password`).
2.  **Entrenamiento**: Visualiza tu rutina con vídeos integrados. El diseño estilo Excel facilita la lectura de cargas.
3.  **Feedback**: Al terminar, pulsa **"Enviar Feedback"**. El sistema mostrará un estado de carga mientras la IA analiza tu sesión. Puedes consultar tus planes antiguos en la sección "Historial".

---

## 🔑 Datos de Prueba (Demo)

### Admin
- **Email**: `fisio@fisioapp.com` | **Pass**: `password`

### Pacientes (Login con Nombre + Apellidos)
- **Carlos García López**: Evolución estándar (historial de 3 planes).
- **Ana Martínez Ruiz**: Caso clínico de **dolor de rodilla** (ideal para probar el Chat IA).
- **Beto Sánchez Gómez**: Caso de cargas insuficientes (RPE bajo).

---

## 🛠️ Instalación Rápida (Sail)

```bash
# 1. Preparar entorno
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build

# 2. Base de datos e IA
./vendor/bin/sail artisan migrate:fresh --seed
# Clave API requerida en .env: GEMINI_API_KEY=tu_clave
```

---
