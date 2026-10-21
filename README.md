# FisioApp - Gestión de Rehabilitación Inteligente 🏋️‍♂️🧠

Aplicación web premium para fisioterapeutas y pacientes que integra Inteligencia Artificial para el seguimiento clínico, diseño de planes dinámicos y análisis de progresión.

---

## 🚀 Funcionalidades

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

## 🔐 Acceso, Aislamiento y Borrado Lógico

### Registro abierto con verificación por email
Los fisioterapeutas se registran libremente en `/register` (ya **no hace falta invitación**).
La cuenta nace inactiva: hay que abrir el enlace de verificación que llega por email
antes de poder entrar al panel.

> En desarrollo el mailer está en modo `log`, así que el enlace aparece en
> `storage/logs/laravel.log`. En producción hay que configurar `MAIL_MAILER` con un
> SMTP real (ver `.env.example`).

### Cada fisio es una isla
Los pacientes, los planes y el catálogo de ejercicios y estímulos de un fisioterapeuta
**nunca son visibles para otro profesional**. El aislamiento se aplica por dos vías
complementarias:

- Un **global scope** (`App\Scopes\FisioScope`) que filtra automáticamente las
  consultas de planes, ejercicios y estímulos por propietario.
- Comprobaciones **explícitas** de pertenencia en los controladores, para los
  recursos que se localizan por ID (fichas de paciente, chat IA, papelera).

### Nada se borra físicamente
No existe el borrado definitivo. Desactivar un paciente, plan, estímulo o ejercicio
lo saca de las listas activas y le impide el acceso, pero **conserva intactos** los
días del plan, los ejercicios pautados y todo el feedback clínico registrado.

Todo lo desactivado se recupera desde **Papelera** (`/admin/papelera`).

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
4.  **Papelera**: Recupera cualquier paciente, plan, estímulo o ejercicio que hayas desactivado.

### 🏃 Perfil Paciente
1.  **Acceso**: Login rápido con **Nombre + Primer Apellido + Segundo Apellido + Fecha de nacimiento**.
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
# 1. Dependencias (antes de levantar los contenedores: Sail usa ./vendor para construir la imagen)
composer install
npm install && npm run build

# 2. Entorno
cp .env.example .env
./vendor/bin/sail artisan key:generate
# Clave API requerida en .env: GEMINI_API_KEY=tu_clave

# 3. Contenedores y base de datos
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed

# 4. Worker de la cola (IMPRESCINDIBLE: el análisis de IA del feedback se genera en segundo plano)
./vendor/bin/sail artisan queue:work
```

> ⚠️ Sin el worker de la cola, el feedback del paciente se guarda pero el **análisis de
> IA nunca se genera**. En desarrollo, `composer dev` levanta servidor, cola, logs y
> Vite en un solo comando.

### Tests

```bash
./vendor/bin/sail artisan test      # o: php artisan test
```

La suite cubre el aislamiento entre profesionales, el registro con verificación de
email, la papelera, el clon de planes y las reglas de negocio de `PlanService`.

---

## 🔬 Investigación de Tecnologías y APIs

Para el desarrollo de **FisioApp**, se realizó una investigación técnica para seleccionar herramientas que equilibraran potencia, facilidad de integración y sostenibilidad.

### 1. Google Gemini API (Modelo: `gemini-1.5-flash`)
*   **Motivación**: Se eligió la versión *Flash* por su baja latencia y alta eficiencia en tareas de análisis de texto comparado con modelos más pesados.
*   **Integración**: Mediante el cliente HTTP nativo de Laravel (`Http::withHeaders`), enviando prompts estructurados en JSON.
*   **Coste y Límites**: 
    *   **Coste**: Gratuito dentro del *Free Tier* de Google AI Studio (hasta un límite generoso para desarrollo).
    *   **Límites**: 15 RPM (peticiones por minuto) y 1 millón de TPM (tokens por minuto). El sistema implementa **lógica de reintentos automática** para gestionar estos límites.
*   **Documentación**: [Google AI Model Reference](https://ai.google.dev/models/gemini).

### 2. Laravel Framework (v11)
*   **Motivación**: Proporciona una estructura robusta para la gestión de usuarios, bases de datos y seguridad.
*   **Uso Clave**: Eloquent ORM para el historial clínico y Middleware personalizado para la seguridad de roles.

### 3. YouTube Embed
*   **Integración**: Extracción de ID de vídeo mediante expresiones regulares para carga dinámica en Modales. Sin coste de hosting de vídeo.

---

## 🤖 Uso de la IA en el Desarrollo

Este proyecto ha sido desarrollado en una modalidad de **Pair Programming con Inteligencia Artificial**, utilizando el agente avanzado **Antigravity (Google DeepMind)**.

### Herramientas y Modelos:
*   **Modelo Principal**: Gemini 1.5 Pro.
*   **Entorno**: Integración directa en el IDE para manipulación de archivos y ejecución de comandos en tiempo real.

### Metodología de Uso:
1.  **Arquitectura**: La IA propuso la estructura de base de datos (`Schema`) basándose en los requisitos clínicos iniciales.
2.  **Implementación de Lógica Compleja**: Desarrollo de algoritmos de reordenación de estímulos y clonación inteligente de planes.
3.  **Diseño UI/UX**: Generación de estilos CSS bajo la estética *Premium Glassmorphism* y micro-animaciones para mejorar la experiencia del paciente.
4.  **Debugging Asistido**: Resolución de problemas críticos como bucles de redirección (`ERR_TOO_MANY_REDIRECTS`) y optimización de *timeouts* en la API de Gemini.
5.  **Testing**: Generación de seeders masivos (`PlanHistorialSeeder`) para simular meses de evolución clínica.

---

## 📋🤖 Prompts de IA

```bash

Me gustaria que hicieses una app de pauta de ejercicios de fisioterapia.

Mi cliente es un fisioterapeuta que utiliza un excel para pautar ejercicios de rehabilitacion para sus pacientes, su excel se diferencia en, estimulo, que se centra en los ejercicios basicos, auxiliaries y metabolicos luego esta ejercicios, los ejercicios pertenecen a un grupo de estimulos, por ejemplo en el estimulo basico hace sentadillas, press banca, etc, en el grupo de estimulos auxiliares hay ejercicios como zancadas, extensiones de rodilla, cruces polea, triceps polea, etc y el ultimo grupo de estimulos es el metabolico que se centra en ejercicios de cardio mayoritariamente, cinta, correr, caminar y demas. Ahora cada ejercicio tiene sus series y repeticiones propias y una intensidad para cada ejercicio que va desde 1 a 10, y al final de cada tabla un cuestionario sobre la dureza y comentario sobre como ha ido el dia. En resumen, cada estimulo tiene sus ejercicios y cada ejercicio tiene sus series repeticiones y intensidad y el feedback del cliente --MUY IMPORTANTE: El bloque ejercicios son ejercicios sacados del canal de mi cliente el fisioterapeuta y son enlaces en el excel. Me gustaria mantener esa mecanica, que en el ejercicio de la tabla del paciente salga el video de i cliente el fisiotrepeuta--

Me gustaria hacer una aplicacion web con laravel y despliegue por sail con todo lo anterior pasado a codigo. La aplicacion debera tener la parte del fisioterapeuta donde creara los ejercicios dara de alta a nuevos clientes y les pautara los ejercicios con sus respectivos videos y otra parte para el publico donde el paciente vera los ejercicios y el video del ejercicio pautados por el fisioterapeuta y podra añadirle el feedback, lo ejercicios se guardaran en un perfil paciente y ahi saldran tanto los nuevos como los antiguos, simplemente pulsando adelante atras podra cambiar de plantilla, el feedback de cada paciente le llegara a mi cliente el fisio en la repectiva ficha que tenga mi cliente de ese paciente.

Mi amigo se logeara con su cuenta de "fisioterapeuta admin" y el paciente con sus nombre y sus 2 apellidos, el flujo sera, el paciente pone sus credenciales y le sale ya su perfil con los ejercicios

La aplicacion dispondra de entidades como el paciente, el fisio admin, esimulos y ejercicios y estaran relacionados del plan,fisio crea estimulos, dentro de estimulos añade ejercicios y a cada ejercicio su video, sus series repes y su intensidad, fiso crea paciente(fisio puede eliminar, editar, crear, listar todo: paciente, estimulo y ejercicio), fisio pacta ejercicios a paciente, paciente ve sus ejercicios(no puede ni crear ni editarlos ni eliminarlos), la aplicacion a poder ser usare una inst5ancia de aws para desplegarla, de momento, y estara su repositorio en github, lo que quiero es que este el despliegue automatico, es decir que cuando haga push en mi rama local se actualize en el servidor y la aplicacion sera un laravel/sail con deploy, primero puedes hacer el deploy en local y cuando funcione todo bien miramos para deployearlo en aws no me corre prisa

La foto es como son sus actuales fichas de ejercicios 


```

```bash 

me gustaria que incorporases un chat en el feedback del los palnes en la parte de fisioterapeuta que le permita preguntar sobre los datos recabados y ser mas preciso con el seguimiento y los ejercicios pautados, tambien quiero que la ia se fije en todos los planes y feedback dados por cada paciente difernte, es decir, de cada paciente tiene sus planes y feedback y los analiza todos en lugar de uno a uno. En resumen, un chat en el feedback de fisio para que el fisio pueda consultar dudas e info y que la ia se fije en todo el historial de un paciente no solo de uno en uno el feedback del paciente esta perfecto como esta ese no lo toques o lo necesario para realicar los cambios

```

```bash 

te voy a pedir una serie de arreglos y correcciones para implementar y pulir mas aun la aplicacion
1 Cuando edite un orden de estimulo y lo ponga antes o despues de uno que ya estaba, que el numero del que ya estaba cambien en orden con el orden del esimulo editado, es decir, si edito el orden de estimulo 3 y lo pongo en 2 que el que estaba en 2 ahora sea el 3 y el estimulo editado se queda en 2 asi como si subo un puesto en el orden (te adjunto imagen para que lo veas mas claro)
2. Incorporar de alguna forma un inactivador de planes automatico y que pueda ser reversible, por ejemplo si se detecta un plan nuevo a x paciente que el anterior se  inactive automaticamente, y no se pueda editar solo eliminar y ver, y si el fisio decide retomar el plan lo active y ya se pueda editar 
3.Implementar un copiador de planes por si quiere mantener el mismo plan de ejercicios pero solo cambiando la intensidad, las repeticiones, el peso o las series del ejercicio, inculso añadir o quitar ejercio, estimulo, etc,. Podria implementarse de forma que a la hora de imlementar un plan nuevo en un paciente le pregunte si quiere continuar pero modifcando el plan actual o crear uno nuevo e inactivar el antiguo.
Quiero que hagas los test necesarios para comprobar que funciona bien las nuevas implementaciones y si encuentras un error o bug vuelvas a realicar los tests hasta encontrar su error

```

![qr](./screenshots/Captura%20desde%202026-02-16%2019-43-51.png)