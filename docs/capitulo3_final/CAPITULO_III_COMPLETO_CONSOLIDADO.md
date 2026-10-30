# CAPÍTULO III. INGENIERÍA DEL PROYECTO
## Estructura General y Planificación del Proyecto de Grado

**Proyecto:** Sistema Web de Gestión y Reserva de Canchas de Wally con Asistente Virtual Inteligente (NLP)  
**Postulante:** Jhesmar Fuentes  
**Marco Metodológico:** Marco Ágil Scrum (4 Sprints de desarrollo incremental)

---

### ÍNDICE DE CONTENIDOS DEL CAPÍTULO III

* **3.1. Requerimientos generales del sistema**
  * 3.1.1. Requerimientos funcionales (RF)
  * 3.1.2. Requerimientos no funcionales (RNF)
  * 3.1.3. Identificación y descripción de los actores principales (Administrador, Recepcionista y Cliente)
  * 3.1.4. Diagrama general de casos de uso del sistema
  * 3.1.5. Diagrama general de clases del dominio
  * 3.1.6. Diseño general de la base de datos
    * Modelo entidad-relación (MER)
    * Modelo relacional y normalización (3FN)
    * Descripción del diccionario de datos y tablas principales
  * 3.1.7. Arquitectura del sistema (Patrón MVC multicapa, servicios REST y servicios externos integrados)
* **3.2. Aplicación de la metodología Scrum**
  * 3.2.1. Organización del equipo y responsabilidades en Scrum (Product Owner, Scrum Master, Developer)
  * 3.2.2. Elaboración del Product Backlog priorizado (Historias HU-01 a HU-07)
  * 3.2.3. Planificación y cronograma de los cuatro sprints
  * 3.2.4. Criterios de aceptación (Formato BDD / Gherkin) y Definición de Terminado (Definition of Done - DoD)
* **3.3. Implementación del sistema mediante sprints**
  * **3.3.1. Sprint 1: Acceso y usuarios**
    * 3.3.1.1. Objetivo del sprint
    * 3.3.1.2. Sprint Backlog (HU-01: Inicio de Sesión / HU-02: Gestionar usuarios)
    * 3.3.1.3. Especificación de casos de uso del módulo (Iniciar Sesión / Gestionar usuarios y roles)
    * 3.3.1.4. Diseño y modelado UML del módulo (Casos de uso, Clases, Secuencia, Actividades, Mockups e Interfaces)
    * 3.3.1.5. Implementación del código fuente y validaciones (Controladores, Modelos y Reglas de Validación)
    * 3.3.1.6. Pruebas del sprint (casos funcionales manuales de HU-01 y HU-02, y pruebas automatizadas con PHPUnit)
    * 3.3.1.7. Resultado e incremento entregable del sprint
  * **3.3.2. Sprint 2: Canchas y horarios**
    * 3.3.2.1. Objetivo del sprint
    * 3.3.2.2. Sprint Backlog (HU-03: Administrar canchas / HU-04: Gestionar horarios)
    * 3.3.2.3. Diseño y modelado UML del módulo (Casos de uso, Clases, Secuencia, Actividades, Mockups e Interfaces)
    * 3.3.2.4. Implementación del código fuente y validaciones (Controladores de canchas y horarios, Cálculo de disponibilidad)
    * 3.3.2.5. Pruebas del sprint (Caja negra y pruebas automatizadas con PHPUnit)
    * 3.3.2.6. Resultado e incremento entregable del sprint
  * **3.3.3. Sprint 3: Reservas y chatbot**
    * 3.3.3.1. Objetivo del sprint
    * 3.3.3.2. Sprint Backlog (HU-05: Administrar reservas / HU-06: Gestión de Chatbot NLP)
    * 3.3.3.3. Historia de Usuario 05 (HU-05): Administrar reservas (Casos de uso, Clases, Actividades, Secuencia, Modelo de Base de Datos, Tablas, Atributos, PK/FK y Normalizaciones)
    * 3.3.3.4. Historia de Usuario 06 (HU-06): Gestión de Chatbot NLP (Casos de uso, Clases, Actividades, Secuencia, Modelo de Base de Datos, Tablas, Atributos, PK/FK y Normalizaciones)
    * 3.3.3.5. Implementación del código fuente y validaciones (Transacciones de reserva y procesamiento conversacional)
    * 3.3.3.6. Pruebas del sprint (Caja negra funcional y pruebas automatizadas con PHPUnit)
    * 3.3.3.7. Resultado e incremento entregable del sprint
  * **3.3.4. Sprint 4: Dashboard y reportes**
    * 3.3.4.1. Objetivo del sprint
    * 3.3.4.2. Sprint Backlog (HU-07: Reportes y estadísticas)
    * 3.3.4.3. Historia de Usuario 07 (HU-07): Reportes y estadísticas (Casos de uso, Clases, Actividades, Secuencia, Modelo de Base de Datos, Tablas, Atributos, PK/FK y Normalizaciones)
    * 3.3.4.4. Implementación del código fuente y validaciones (Dashboard, agregación SQL/Eloquent y reportes PDF/Excel)
    * 3.3.4.5. Pruebas del sprint (Caja negra y pruebas automatizadas con PHPUnit)
    * 3.3.4.6. Resultado e incremento entregable del sprint
* **3.4. Integración y validación final del sistema**
  * 3.4.1. Pruebas de integración global entre módulos y servicios externos
  * 3.4.2. Pruebas funcionales de extremo a extremo (End-to-End)
  * 3.4.3. Matriz de trazabilidad y validación de requerimientos vs. HU-01 a HU-07
  * 3.4.4. Resultados finales y balance de cobertura de pruebas

---

# 3.1. REQUERIMIENTOS GENERALES DEL SISTEMA

El presente apartado establece la base de ingeniería de software para el Sistema de Gestión y Reserva de Canchas de Wally con Asistente Virtual Inteligente (NLP). Se definen de manera formal las capacidades funcionales, las restricciones de calidad técnica, la identificación y descripción de los actores principales, los modelos estructurales de alto nivel (Casos de Uso, Clases del Dominio, Base de Datos) y el patrón arquitectural del proyecto implementado bajo el framework Laravel.

---

### 3.1.1. Requerimientos Funcionales (RF)

Los requerimientos funcionales especifican el comportamiento observable, las transformaciones de datos y los servicios directos que el sistema provee a los usuarios. Se encuentran estrictamente alineados con las siete Historias de Usuario oficiales del Product Backlog (HU-01 a HU-07):

| Código | Requerimiento Funcional | Descripción Detallada | Historia Relacionada | Prioridad |
| :--- | :--- | :--- | :---: | :---: |
| **RF-01** | Autenticación y Control de Acceso Multi-Rol | El sistema debe permitir el inicio de sesión seguro verificando credenciales locales (correo/contraseña con Bcrypt) y mediante autenticación externa con Google Auth, validando el estado activo de la cuenta y reconociendo los roles: Administrador, Recepcionista y Cliente. | HU-01 | Alta |
| **RF-02** | Gestión Integral de Usuarios y Roles | El sistema debe permitir al Administrador registrar nuevos usuarios, editar datos personales, asignar roles, activar o desactivar cuentas y eliminar registros según las reglas de negocio establecidas. | HU-02 | Alta |
| **RF-03** | Administración de Canchas y Balones | El sistema debe permitir al Administrador registrar, actualizar y dar de baja canchas de wally (nombre, precio base por turno, color representativo, estado de disponibilidad e imágenes) y balones deportivos con sus tarifas de alquiler. | HU-03 | Alta |
| **RF-04** | Registro de Cierres Temporales y Mantenimientos | El sistema debe permitir al Administrador registrar períodos de bloqueo o mantenimiento preventivo sobre canchas específicas, impidiendo la programación de turnos en dichos rangos de tiempo. | HU-03 | Alta |
| **RF-05** | Configuración de Horarios de Atención | El sistema debe permitir parametrizar las franjas horarias comerciales de atención por día de la semana y por cancha, definiendo la duración estándar de los turnos de juego. | HU-04 | Alta |
| **RF-06** | Cálculo y Consulta de Disponibilidad de Canchas | El sistema debe calcular en tiempo real los turnos libres por cancha y fecha, excluyendo reservas vigentes (confirmadas o pendientes) y cierres de mantenimiento para evitar solapamientos. | HU-04 | Alta |
| **RF-07** | Registro y Administración de Reservas de Canchas | El sistema debe permitir a clientes y recepcionistas registrar reservas seleccionando cancha, fecha, hora y balón opcional, calculando el importe total y administrando los estados: Pendiente, Confirmada, Completada y Cancelada. | HU-05 | Muy Alta |
| **RF-08** | Control Operativo Presencial en Recepción | El sistema debe permitir al personal de recepción registrar la llegada física del cliente (Check-in), gestionar la entrega y devolución de balones deportivos, y registrar la salida (Check-out) con liquidación de saldos. | HU-05 | Alta |
| **RF-09** | Sincronización Externa de Citas con Google Calendar | El sistema debe interactuar con el servicio externo de Google Calendar para sincronizar y agendar automáticamente los turnos de reserva confirmados. | HU-05 | Media |
| **RF-10** | Asistente Virtual Inteligente con Chatbot NLP | El sistema web debe incorporar una interfaz conversacional integrada que permita al Cliente consultar horarios libres, tarifas y procedimientos de reserva en lenguaje natural, comunicándose internamente con la API externa de Gemini para el procesamiento semántico. | HU-06 | Alta |
| **RF-11** | Dashboard Administrativo con Indicadores Operativos | El sistema debe presentar al Administrador un panel interactivo con métricas en tiempo real: ocupación porcentual de canchas, ingresos acumulados, turnos del día y métricas de desempeño conversacional del chatbot. | HU-07 | Media |
| **RF-12** | Generación y Exportación de Reportes Administrativos | El sistema debe generar reportes consolidados y filtrables por fecha, cancha y estado de reserva, permitiendo la exportación a documentos impresos en formato PDF y hojas de cálculo en formato Excel. | HU-07 | Media |

---

### 3.1.2. Requerimientos No Funcionales (RNF)

Los requerimientos no funcionales definen los atributos de calidad, rendimiento, seguridad y mantenibilidad que rigen la operación técnica de la plataforma:

| Código | Categoría | Requerimiento No Funcional | Criterio de Medición / Estándar |
| :--- | :--- | :--- | :--- |
| **RNF-01** | **Seguridad** | Cifrado y Protección de Credenciales | Contraseñas almacenadas bajo función hash unidireccional Bcrypt con factor de costo 12. Manejo obligatorio de tokens CSRF en formularios y protección contra inyecciones SQL mediante Eloquent ORM y sentencias preparadas PDO. |
| **RNF-02** | **Seguridad** | Control de Acceso Basado en Roles (RBAC) | Restricción estricta de rutas web mediante Middlewares de Laravel (`auth`, `role`). Cada usuario accede única y exclusivamente a los módulos autorizados para su rol. |
| **RNF-03** | **Rendimiento** | Tiempo de Respuesta del Servidor | La consulta de disponibilidad de turnos y la carga del dashboard administrativo deben completarse en un tiempo promedio inferior a 1.5 segundos en condiciones estándar de red local/intranet. |
| **RNF-04** | **Usabilidad** | Diseño Adaptativo (Responsive Design) | La interfaz web debe adaptarse fluidamente a dispositivos móviles, tablets y computadoras de escritorio, utilizando maquetación CSS moderna y diseño adaptable. |
| **RNF-05** | **Integridad** | Consistencia Transaccional ACID | El motor de base de datos debe asegurar transacciones atómicas con bloqueos pesimistas (`lockForUpdate`) al momento de registrar reservas para impedir condiciones de carrera y dobles reservas simultáneas. |
| **RNF-06** | **Disponibilidad** | Resiliencia ante Fallos en Servicios Externos | En caso de indisponibilidad temporal de servicios externos (Google Calendar o API de Gemini), el sistema debe registrar el incidente en logs y permitir que la operativa local continúe sin bloqueos críticos. |
| **RNF-07** | **Mantenibilidad** | Código Limpio y Arquitectura Modular | El código fuente PHP debe apegarse al estándar PSR-12, con controladores desacoplados, separación de capas MVC e inyección de dependencias en los servicios. |

---

### 3.1.3. Identificación y Descripción de los Actores

De acuerdo con la definición oficial del sistema, existen **exactamente tres actores principales** que interactúan directamente con las funcionalidades de la aplicación:

1. **Administrador:**
   * **Descripción:** Usuario con máximos privilegios en la plataforma. Es el responsable de la gobernanza de accesos, gestión de usuarios, asignación de roles, configuración del catálogo de canchas y balones, parametrización de horarios comerciales y cierres de mantenimiento, supervisión del Dashboard ejecutivo y generación o exportación de reportes administrativos y financieros en PDF y Excel.
2. **Recepcionista:**
   * **Descripción:** Personal operativo encargado de la atención en mostrador y gestión diaria del complejo deportivo. Tiene autorización para registrar reservas presenciales o telefónicas, consultar la disponibilidad de turnos en tiempo real, efectuar el cobro de turnos y ejecutar el control de entrada (Check-in, entrega de balones) y salida (Check-out) de los clientes en las canchas.
3. **Cliente:**
   * **Descripción:** Usuario final del complejo deportivo. Interactúa con el sistema para consultar información de canchas, verificar precios y horarios libres, gestionar sus reservas personales y utilizar la funcionalidad interna del Chatbot NLP para resolver dudas y agendar turnos mediante lenguaje natural.

> **Distinción obligatoria sobre Servicios Externos y Funcionalidades Integradas:**
> * **Chatbot NLP:** No es un actor principal ni una entidad humana externa; es una **funcionalidad interna del sistema web** con la que interactúa el actor **Cliente**. Internamente, el sistema consume la **API de Gemini** como servicio externo de procesamiento de lenguaje natural.
> * **Google Auth:** Es un **servicio externo de autenticación federada** utilizado para verificar la identidad del usuario mediante su cuenta Google. No es un actor principal.
> * **Google Calendar:** Es un **servicio externo de calendario** en la nube con el que el sistema interactúa de manera técnica para sincronizar y agendar los eventos de reservas confirmadas. No es un actor principal.

---

### 3.1.4. Diagrama General de Casos de Uso del Sistema

En estricto apego a las reglas de modelado, el diagrama general de casos de uso presenta **únicamente a los tres actores principales** (Administrador, Recepcionista y Cliente), compartiendo casos de uso cuando la funcionalidad es común y empleando relaciones `include` o `extend` debidamente justificadas:

```
+----------------------------------------------------------------------------------------------------+
|                          SISTEMA DE GESTIÓN Y RESERVA DE CANCHAS DE WALLY                          |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|    [Administrador] ---------\                                                                      |
|                              \                                                                     |
|    [Recepcionista] -----------> (CU-01: Iniciar Sesión)                                            |
|                              /          |                                                          |
|    [Cliente] ---------------/           |-- <<include>> ---> (Validar Credenciales y Estado)       |
|         |                               |                                                          |
|         |                               \-- <<include>> ---> (Identificar Rol y Acceso)            |
|         |                                                                                          |
|         +---------------------> (CU-02: Cerrar Sesión)                                             |
|         |                                                                                          |
|         +---------------------> (CU-03: Consultar Horarios y Disponibilidad) <--- [Recepcionista]  |
|         |                                         ^                                      |         |
|         |                                         |                                      |         |
|         +---------------------> (CU-04: Registrar Reserva de Cancha) <-------------------+         |
|         |                               |                                                |         |
|         |                               \-- <<include>> ---> (Calcular Total de Reserva) |         |
|         |                                                                                |         |
|         +---------------------> (CU-05: Realizar Consulta Mediante Chatbot NLP)          |         |
|                                                                                          |         |
|                                                                                          |         |
|    [Recepcionista] -----------> (CU-06: Controlar Check-in y Check-out Presencial) <-----+         |
|                                                                                          |         |
|                                                                                          |         |
|    [Administrador] -----------> (CU-07: Gestionar Usuarios y Roles)                      |         |
|         |                                                                                |         |
|         +---------------------> (CU-08: Administrar Canchas, Balones y Cierres)          |         |
|         |                                                                                |         |
|         +---------------------> (CU-09: Configurar Horarios Comerciales)                 |         |
|         |                                                                                |         |
|         +---------------------> (CU-10: Visualizar Dashboard e Indicadores)              |         |
|         |                                                                                |         |
|         \---------------------> (CU-11: Generar y Exportar Reportes PDF/Excel)           |         |
|                                                                                                    |
+----------------------------------------------------------------------------------------------------+
```

---

### 3.1.5. Diagrama General de Clases del Dominio

Las principales clases del modelo de dominio del sistema y sus relaciones estructurales se definen a continuación:

* **User:** Modela las cuentas de acceso al sistema (`id`, `name`, `email`, `password`, `rol`, `telefono`, `estado`).
  * Relación: Un `User` registra de `0..*` `Reservas` (cuando actúa como cliente).
  * Relación: Un `User` mantiene de `0..*` `Conversaciones` con el módulo del chatbot.
* **Cancha:** Modela el espacio deportivo físico (`id`, `nombre`, `precio_por_hora`, `color`, `activa`, `imagen`).
  * Relación: Una `Cancha` tiene parametrizados de `1..*` `Horarios` comerciales.
  * Relación: Una `Cancha` registra de `0..*` `CierresCancha` (bloqueos de mantenimiento).
  * Relación: Una `Cancha` recibe de `0..*` `Reservas`.
* **Horario:** Representa la regla comercial de disponibilidad semanal (`id`, `cancha_id`, `dia_semana`, `hora_inicio`, `hora_fin`, `activo`).
* **Balon:** Modela el material deportivo disponible para alquiler opcional (`id`, `marca`, `modelo`, `precio_alquiler`, `estado`, `imagen`).
  * Relación: Un `Balon` puede ser asignado a `0..*` `Reservas`.
* **Reserva:** Entidad transaccional central (`id`, `user_id`, `cancha_id`, `balon_id`, `fecha`, `hora_inicio`, `hora_fin`, `total`, `estado`, `check_in_at`, `check_out_at`).
  * Relación: Una `Reserva` se asocia con `0..1` registro técnico en `GoogleCalendarEvent`.
* **Conversacion:** Representa la sesión de diálogo asistido del cliente (`id`, `user_id`, `session_id`, `estado`).
  * Relación: Una `Conversacion` agrupa de `1..*` `MensajesChatbot`.
* **MensajeChatbot:** Registro atómico de los mensajes intercambiados (`id`, `conversacion_id`, `emisor`, `mensaje`, `intencion_detectada`, `score_confianza`).

---

### 3.1.6. Diseño General de la Base de Datos

#### A. Modelo Entidad-Relación (MER)
El diseño conceptual refleja fielmente la operativa del negocio:
* El cliente (`User`) realiza `Reservas`.
* Cada `Reserva` ocupa una `Cancha` en una fecha y turno específico, pudiendo incluir opcionalmente un `Balon`.
* Las excepciones de disponibilidad operativa se controlan mediante `CierresCancha` sin alterar la matriz base de `Horarios`.
* Las interacciones conversacionales se almacenan en `Conversaciones` y `MensajesChatbot`, permitiendo auditar la efectividad de las consultas del cliente.

#### B. Modelo Relacional y Normalización
El esquema relacional fue normalizado en la **Tercera Forma Normal (3FN)**:
1. **1FN:** Todos los atributos son atómicos y no existen grupos repetitivos. Fechas, horas e importes se almacenan con tipos de datos nativos (`DATE`, `TIME`, `DECIMAL(8,2)`).
2. **2FN:** Cumple 1FN y todos los atributos no clave dependen en su totalidad de la clave primaria simple (`BIGINT UNSIGNED AUTO_INCREMENT`).
3. **3FN:** Cumple 2FN y no existen dependencias funcionales transitivas entre campos no clave. Las relaciones se resuelven mediante claves foráneas indexadas (`FOREIGN KEY`) con integridad referencial.

#### C. Descripción del Diccionario de las Tablas Principales
* **`users`:** `id` (PK), `name` (VARCHAR), `email` (VARCHAR, Unique), `password` (VARCHAR, Nullable), `rol` (ENUM: 'admin', 'recepcionista', 'cliente'), `telefono` (VARCHAR), `estado` (BOOLEAN), `created_at`, `updated_at`.
* **`canchas`:** `id` (PK), `nombre` (VARCHAR, Unique), `precio_por_hora` (DECIMAL 8,2), `color` (VARCHAR), `activa` (BOOLEAN), `imagen` (VARCHAR).
* **`horarios`:** `id` (PK), `cancha_id` (FK -> canchas.id), `dia_semana` (TINYINT: 0..6), `hora_inicio` (TIME), `hora_fin` (TIME), `activo` (BOOLEAN).
* **`cierres_cancha`:** `id` (PK), `cancha_id` (FK -> canchas.id), `fecha_inicio` (DATE), `fecha_fin` (DATE), `hora_inicio` (TIME), `hora_fin` (TIME), `motivo` (VARCHAR).
* **`balones`:** `id` (PK), `marca` (VARCHAR), `modelo` (VARCHAR), `precio_alquiler` (DECIMAL 8,2), `estado` (VARCHAR), `imagen` (VARCHAR).
* **`reservas`:** `id` (PK), `user_id` (FK -> users.id), `cancha_id` (FK -> canchas.id), `balon_id` (FK -> balones.id, Nullable), `fecha` (DATE), `hora_inicio` (TIME), `hora_fin` (TIME), `total` (DECIMAL 8,2), `estado` (ENUM: 'pendiente', 'confirmada', 'completada', 'cancelada'), `check_in_at` (TIMESTAMP, Nullable), `check_out_at` (TIMESTAMP, Nullable).
* **`conversaciones` y `mensajes_chatbot`:** `id` (PK), `conversacion_id` (FK), `emisor` (ENUM: 'usuario', 'bot'), `mensaje` (TEXT), `intencion_detectada` (VARCHAR), `score_confianza` (DECIMAL 5,4).

---

### 3.1.7. Arquitectura del Sistema

El sistema implementa una **Arquitectura en Capas basada en el patrón Modelo-Vista-Controlador (MVC)** sobre Laravel:

1. **Capa de Presentación:** Vistas compiladas en Blade con maquetación web responsiva, componentes interactivos de calendario y widget flotante para el Chatbot NLP con comunicación asíncrona JSON (`Fetch API`).
2. **Capa de Control y Enrutamiento:** Controladores especializados (`LoginController`, `UserController`, `CanchaController`, `HorarioController`, `ReservaController`, `ChatbotController`, `DashboardController`, `ReporteController`) protegidos por Middlewares de sesión y verificación de rol (`RBAC`).
3. **Capa de Lógica del Negocio y Servicios:**
   * `DisponibilidadService`: Motor algorítmico que calcula los bloques horarios libres considerando horarios comerciales, reservas activas y cierres por mantenimiento.
   * `GeminiNLPService`: Conector interno que interactúa mediante HTTP REST con el servicio externo de Google Gemini API para interpretar consultas en lenguaje natural.
   * `GoogleCalendarService`: Servicio de sincronización técnica con la API de Google Calendar.
4. **Capa de Persistencia:** ORM Eloquent sobre motor relacional MySQL / MariaDB (InnoDB) garantizando integridad referencial, transacciones seguras y migraciones estructuradas.

---

# 3.2. APLICACIÓN DE LA METODOLOGÍA SCRUM

La construcción del Sistema de Gestión y Reserva de Canchas de Wally se ejecutó siguiendo el marco ágil de trabajo **Scrum**, permitiendo iteraciones cortas, retroalimentación continua y entregas de incrementos de software funcionales y testeados a lo largo de un ciclo planificado de ocho semanas.

---

### 3.2.1. Organización y Responsabilidades en Scrum

Para el presente proyecto de grado, se definieron los roles de Scrum adaptados al contexto de desarrollo académico-profesional:

* **Product Owner (PO):**
  * **Rol asignado:** Tutor / Representante del complejo deportivo (Administrador del negocio).
  * **Responsabilidades:** Define la visión del producto, establece las prioridades en el Product Backlog, valida los criterios de aceptación y certifica la entrega de valor al finalizar cada sprint.
* **Scrum Master (SM):**
  * **Rol asignado:** Jhesmar Fuentes (Investigador y Líder de Proyecto).
  * **Responsabilidades:** Vela por el cumplimiento de los principios y eventos Scrum (Sprint Planning, Daily Standup simulado, Sprint Review y Retrospectiva), asegurando la eliminación de impedimentos técnicos y metodológicos.
* **Equipo de Desarrollo (Development Team):**
  * **Rol asignado:** Jhesmar Fuentes (Desarrollador Full-Stack).
  * **Responsabilidades:** Análisis técnico, modelado de base de datos, diseño de diagramas UML, codificación en Laravel (PHP/Blade/JavaScript), integración de APIs y ejecución de pruebas de software.

---

### 3.2.2. Elaboración del Product Backlog

El Product Backlog se compone de **exactamente siete historias de usuario** generales y oficiales (HU-01 a HU-07), priorizadas según el valor operativo para el complejo deportivo y estimadas en Puntos de Historia (Story Points - SP) mediante la escala de Fibonacci (1, 2, 3, 5, 8, 13):

| ID | Historia de Usuario (HU) | Descripción Ágil | Prioridad | Estimación (SP) | Sprint Asignado |
| :---: | :--- | :--- | :---: | :---: | :---: |
| **HU-01** | Inicio de Sesión | Permitir el acceso al sistema según el rol del usuario. | Alta | 5 SP | Sprint 1 |
| **HU-02** | Gestionar usuarios | Registrar, editar, eliminar usuarios y asignar roles. | Alta | 5 SP | Sprint 1 |
| **HU-03** | Administrar canchas | Registrar y administrar canchas, balones y cierres por mantenimiento. | Alta | 5 SP | Sprint 2 |
| **HU-04** | Gestionar horarios | Configurar horarios y consultar la disponibilidad de las canchas. | Alta | 8 SP | Sprint 2 |
| **HU-05** | Administrar reservas | Registrar reservas, controlar pagos y gestionar la atención presencial. | Muy Alta | 13 SP | Sprint 3 |
| **HU-06** | Gestionar chatbot NLP | Permitir consultas sobre horarios, precios y reservas mediante un asistente virtual que comprenda el lenguaje natural. | Alta | 8 SP | Sprint 3 |
| **HU-07** | Reportes y estadísticas | Consultar indicadores en el dashboard y generar reportes administrativos en PDF y Excel. | Media | 8 SP | Sprint 4 |

**Total de Puntos de Historia del Proyecto:** **52 SP**  
**Velocidad promedio del equipo:** **13 SP por Sprint**

---

### 3.2.3. Planificación de los Cuatro Sprints

El proyecto se estructuró en **cuatro Sprints de dos semanas de duración cada uno** (ocho semanas de desarrollo en total), con un enfoque temático claro por iteración:

| Sprint | Semanas | Historias de usuario | Enfoque |
| :---: | :---: | :---: | :--- |
| **Sprint 1** | Semanas 1–2 | HU-01 y HU-02 | **Acceso y usuarios:** Mecanismos de autenticación, control de perfiles, permisos y administración de usuarios. |
| **Sprint 2** | Semanas 3–4 | HU-03 y HU-04 | **Canchas y horarios:** Infraestructura deportiva, balones, bloqueos de mantenimiento, parametrización horaria y motor de disponibilidad. |
| **Sprint 3** | Semanas 5–6 | HU-05 y HU-06 | **Reservas y chatbot:** Ciclo transaccional de reservas, control en recepción (Check-in/Check-out), Google Calendar y asistente conversacional NLP. |
| **Sprint 4** | Semanas 7–8 | HU-07 | **Dashboard y reportes:** Panel de indicadores ejecutivos, métricas del chatbot y módulo de generación de reportes PDF y Excel. |

```
+-------------------------------------------------------------------------------------------------------+
|                                     CRONOGRAMA DE SPRINTS (SCRUM)                                     |
+-------------------------------------------------------------------------------------------------------+
|  SPRINT 1 (Semanas 1 y 2): Acceso y usuarios (10 SP)                                                 |
|    - HU-01: Inicio de Sesión                                                                          |
|    - HU-02: Gestionar usuarios                                                                        |
|                                                                                                       |
|  SPRINT 2 (Semanas 3 y 4): Canchas y horarios (13 SP)                                                 |
|    - HU-03: Administrar canchas                                                                       |
|    - HU-04: Gestionar horarios                                                                        |
|                                                                                                       |
|  SPRINT 3 (Semanas 5 y 6): Reservas y chatbot (21 SP)                                                 |
|    - HU-05: Administrar reservas                                                                      |
|    - HU-06: Gestionar chatbot NLP                                                                     |
|                                                                                                       |
|  SPRINT 4 (Semanas 7 y 8): Dashboard y reportes (8 SP)                                                |
|    - HU-07: Reportes y estadísticas                                                                   |
+-------------------------------------------------------------------------------------------------------+
```

---

### 3.2.4. Criterios de Aceptación y Definición de Terminado

#### A. Formato de Criterios de Aceptación (Gherkin / BDD)
Cada Historia de Usuario se valida mediante escenarios formalizados en sintaxis BDD (*Given-When-Then* / *Dado-Cuando-Entonces*):
* **Ejemplo (HU-01: Inicio de Sesión):**
  * **Dado** que un usuario registrado cuenta con credenciales válidas y su cuenta está activa,
  * **Cuando** ingresa su correo electrónico y contraseña en el formulario de inicio de sesión,
  * **Entonces** el sistema valida los datos con la función hash Bcrypt, inicia la sesión segura y redirige al panel correspondiente a su rol.

#### B. Definición de Terminado (Definition of Done - DoD)
Para que un incremento de software o Historia de Usuario sea declarada formalmente terminada (*Done*), debe cumplir estrictamente con los siguientes requisitos:
1. **Codificación:** Código fuente escrito en PHP siguiendo el estándar PSR-12, sin variables hardcodeadas y con tipificación estricta en controladores y modelos.
2. **Revisión de Seguridad:** Formularios web protegidos contra ataques CSRF, validación de datos en servidor (`FormRequest`) y verificación de autorización mediante middlewares de rol (`CheckRole`).
3. **Persistencia y Base de Datos:** Migraciones ejecutadas sin errores, claves foráneas e índices creados según el diseño relacional en Tercera Forma Normal (3FN).
4. **Diseño de Interfaz:** Vistas Blade integradas con diseño adaptable a pantallas móviles y de escritorio.
5. **Pruebas Automatizadas:** Creación y ejecución exitosa de pruebas unitarias o de integración en **PHPUnit**, logrando un resultado satisfactorio (*OK - Tests passed*) sin fallos ni advertencias.
6. **Despliegue y Aceptación:** Funcionalidad desplegada en el entorno de desarrollo/pruebas local y validada satisfactoriamente por el Product Owner.

---

# 3.3. IMPLEMENTACIÓN DEL SISTEMA MEDIANTE SPRINTS

---

## 3.3.1. Sprint 1: Acceso y usuarios

### 3.3.1.1. Objetivo del sprint
El objetivo principal del Sprint 1 es implementar el mecanismo central de seguridad, control de acceso y gobernanza de cuentas de usuario del sistema. Esto comprende el proceso de autenticación segura de usuarios mediante credenciales verificadas contra la base de datos (con integración complementaria del servicio externo de autenticación con Google Auth), el control de sesiones activas, la validación obligatoria del estado de la cuenta (`activo`/`inactivo`), el direccionamiento inteligente a los paneles correspondientes según el rol (`Administrador`, `Recepcionista` y `Cliente`), y la gestión integral (registro, consulta, edición, activación, desactivación y eliminación controlada) de los usuarios y sus roles por parte del Administrador.

---

### 3.3.1.2. Sprint Backlog

A continuación se presenta el Sprint Backlog correspondiente al primer sprint de desarrollo:

| Nº | Historia | Tareas principales | Prioridad |
| :---: | :--- | :--- | :---: |
| **1** | **HU-01: Inicio de Sesión** | Diseñar login, validar credenciales, implementar sesión, controlar roles, cerrar sesión, validar errores. | Alta |
| **2** | **HU-02: Gestionar usuarios** | Listar, registrar, editar, asignar roles, activar/desactivar, validar datos y permisos. | Alta |

#### A. Detalle de la Historia de Usuario 01 (HU-01)
* **Historia de Usuario (Oficial):**  
  **HU-01: Inicio de Sesión**  
  *Permitir el acceso al sistema según el rol del usuario.*  
  *Narrativa ágil:* Como usuario del sistema, quiero iniciar y cerrar sesión de forma segura para acceder a las funcionalidades correspondientes a mi rol.
* **Criterios de aceptación:**
  * El usuario debe proporcionar credenciales válidas.
  * El sistema debe validar las credenciales.
  * Las contraseñas deben almacenarse de forma segura.
  * El sistema debe identificar el rol del usuario.
  * El usuario debe acceder únicamente a las funcionalidades autorizadas.
  * Un usuario inactivo no debe poder ingresar.
  * El cierre de sesión debe invalidar la sesión activa.
  * Las credenciales incorrectas deben mostrar un mensaje de error sin crear una sesión.
* **Tareas técnicas asociadas:**
  * Diseño de formulario de autenticación.
  * Validación de campos.
  * Autenticación de credenciales locales y manejo de autenticación con servicio externo (Google Auth).
  * Gestión de sesión.
  * Middleware de autorización por rol (`CheckRole`).
  * Redirección según rol.
  * Cierre de sesión.
  * Mensajes de error y control de intentos fallidos.

#### B. Detalle de la Historia de Usuario 02 (HU-02)
* **Historia de Usuario (Oficial):**  
  **HU-02: Gestionar usuarios**  
  *Registrar, editar, eliminar usuarios y asignar roles.*  
  *Narrativa ágil:* Como Administrador, quiero gestionar las cuentas de usuario y sus roles para controlar el acceso al sistema.
* **Criterios de aceptación:**
  * El Administrador puede consultar usuarios.
  * Puede registrar usuarios.
  * Puede modificar información de usuarios.
  * Puede asignar roles.
  * Puede cambiar el estado de una cuenta.
  * Puede eliminar o deshabilitar una cuenta según las reglas definidas.
  * El sistema debe validar los datos antes de guardar.
  * El correo de usuario debe ser único.
  * Los permisos deben corresponder al rol asignado.
* **Tareas técnicas asociadas:**
  * Diseño del módulo de usuarios.
  * Listado de usuarios con ordenamiento y filtros.
  * Registro de personal y clientes.
  * Edición de perfiles y contraseñas.
  * Asignación de roles (`admin`, `recepcionista`, `cliente`).
  * Activación/desactivación de cuentas.
  * Validaciones de unicidad y formato (`StoreUserRequest`, `UpdateUserRequest`).
  * Control de permisos administrativos.
  * Persistencia en base de datos MySQL.

---

### 3.3.1.3. Especificación de Casos de Uso del Módulo

A continuación se presentan las especificaciones formales de los casos de uso correspondientes a este módulo, respetando los tres actores principales del sistema:

#### Especificación de Caso de Uso: Iniciar Sesión (CU-01)

| Elemento | Descripción |
| :--- | :--- |
| **Caso de Uso** | Iniciar Sesión |
| **Actores** | Administrador, Recepcionista, Cliente |
| **Precondición** | El usuario debe estar registrado en el sistema, contar con credenciales válidas y tener un rol asignado. Además, su cuenta debe encontrarse activa. |
| **Postcondición** | El usuario accede al sistema y es redirigido al módulo o panel correspondiente según el rol asignado y los permisos establecidos. |
| **Flujo Básico** | 1. El usuario accede a la pantalla de inicio de sesión.<br>2. El sistema muestra el formulario de autenticación.<br>3. El usuario ingresa su correo y contraseña y selecciona la opción "Iniciar Sesión" (o selecciona la opción de autenticación externa).<br>4. El sistema valida que los campos requeridos sean correctos.<br>5. El sistema verifica las credenciales del usuario en la base de datos.<br>6. El sistema comprueba que la cuenta se encuentre activa.<br>7. El sistema identifica el rol del usuario, establece la sesión y accede al panel correspondiente según el rol. |
| **Flujo Alternativo** | • **Credenciales inválidas:** Si el correo no corresponde a un usuario registrado o la contraseña ingresada es incorrecta, el sistema muestra un mensaje indicando que las credenciales son inválidas y no crea la sesión.<br>• **Cuenta inactiva:** Si la cuenta del usuario se encuentra desactivada, el sistema informa que la cuenta no se encuentra autorizada y no permite iniciar sesión.<br>• **Campos incompletos o erróneos:** Si los campos obligatorios están vacíos o presentan un formato incorrecto, el sistema solicita corregir la información antes de continuar.<br>• **Acceso no autorizado:** Si el usuario intenta acceder a una funcionalidad que no corresponde a su rol, el sistema deniega el acceso mediante un error 403 Forbidden. |
| **Excepciones** | • Error de conexión con el motor de base de datos durante la validación de las credenciales.<br>• Tiempo de espera excedido durante la verificación de la petición HTTP.<br>• Error interno del servidor que impida completar el proceso de inicio de sesión. |

---

#### Especificación de Caso de Uso: Gestionar Usuarios y Roles (CU-02)

| Elemento | Descripción |
| :--- | :--- |
| **Caso de Uso** | Gestionar Usuarios y Roles |
| **Actores** | Administrador |
| **Precondición** | El Administrador debe haber iniciado sesión previamente y contar con una cuenta activa con permisos de administración. |
| **Postcondición** | Las cuentas de usuario son creadas, modificadas, activadas o desactivadas de manera persistente en la base de datos. |
| **Flujo Básico** | 1. El Administrador accede al módulo de "Usuarios".<br>2. El sistema consulta y despliega la lista actualizada de usuarios con sus datos (nombre, correo, rol y estado).<br>3. El Administrador selecciona una acción: "Registrar Nuevo Usuario", "Editar Usuario" o "Cambiar Estado".<br>4. El Administrador diligencia o modifica los datos solicitados (nombre, correo, teléfono, rol y estado).<br>5. El Administrador confirma el guardado de los cambios.<br>6. El sistema valida que los datos cumplan con las reglas de negocio (correo único, campos requeridos, rol válido).<br>7. El sistema persiste la información en la base de datos y retorna al listado mostrando un mensaje de confirmación exitosa. |
| **Flujo Alternativo** | • **Correo electrónico duplicado:** Si se intenta registrar un correo ya existente, el sistema bloquea el registro e indica que el correo ya se encuentra en uso.<br>• **Formato de datos no válido:** Si la contraseña no cumple la longitud mínima o faltan campos obligatorios, el sistema señala los campos erróneos.<br>• **Protección de auto-desactivación:** Si el Administrador intenta desactivar su propia cuenta en sesión, el sistema restringe la acción para evitar la pérdida de gobernanza administrativa. |
| **Excepciones** | • Fallo de persistencia o bloqueo de concurrencia al escribir en la base de datos.<br>• Excepción por intento de acceso sin privilegios de Administrador. |

---

### 3.3.1.4. Diseño y arquitectura del módulo

El módulo sigue el estándar formal de modelado UML, representado en tercera persona y enfocado exclusivamente en los tres actores principales del sistema:

#### A. Diagrama de Casos de Uso del Sprint 1
En este diagrama unificado se aprecia la interacción de los tres actores autorizados con las funcionalidades de acceso y usuarios:

```
+-------------------------------------------------------------------------------------------------------+
|                                  MÓDULO: ACCESO Y GESTIÓN DE USUARIOS                                 |
+-------------------------------------------------------------------------------------------------------+
|                                                                                                       |
|    [Administrador] ---------\                                                                         |
|                              \                                                                        |
|    [Recepcionista] -----------> (CU-01: Iniciar Sesión)                                               |
|                              /          |                                                             |
|    [Cliente] ---------------/           |-- <<include>> ---> (Validar Credenciales y Estado Activo)   |
|         |                               |                                                             |
|         |                               \-- <<include>> ---> (Identificar Rol y Redirigir a Panel)    |
|         |                                                                                             |
|         +---------------------> (CU-02: Cerrar Sesión)                                                |
|                                                                                                       |
|                                                                                                       |
|    [Administrador] -----------> (CU-03: Consultar Lista de Usuarios)                                  |
|         |                                                                                             |
|         +---------------------> (CU-04: Registrar Nuevo Usuario)                                      |
|         |                               |                                                             |
|         |                               \-- <<include>> ---> (Validar Correo Único y Asignar Rol)     |
|         |                                                                                             |
|         +---------------------> (CU-05: Modificar Usuario)                                            |
|         |                                                                                             |
|         \---------------------> (CU-06: Activar / Desactivar Cuenta de Usuario)                       |
|                                                                                                       |
+-------------------------------------------------------------------------------------------------------+
```

#### B. Diagrama de Clases del Módulo
Modela las entidades estructurales del sistema que participan en el Sprint 1:

* **Clase `User` (Modelo del Dominio):**
  * *Atributos:* `id: bigint`, `name: string`, `email: string`, `password: string`, `rol: enum ('admin', 'recepcionista', 'cliente')`, `estado: boolean`, `telefono: string`, `email_verified_at: timestamp`, `created_at: timestamp`.
  * *Métodos:* `esAdmin(): bool`, `esRecepcionista(): bool`, `esCliente(): bool`, `estaActivo(): bool`.
* **Clase `LoginController` (Controlador de Autenticación):**
  * *Métodos:* `showLoginForm(): View`, `login(Request): RedirectResponse`, `credentials(Request): array`, `redirectTo(): string`, `logout(Request): RedirectResponse`.
* **Clase `Admin\UserController` (Controlador Administrativo):**
  * *Métodos:* `index(): View`, `create(): View`, `store(StoreUserRequest): RedirectResponse`, `edit(User): View`, `update(UpdateUserRequest, User): RedirectResponse`, `destroy(User): RedirectResponse`.
* **Clase `StoreUserRequest` y `UpdateUserRequest` (Validaciones de Formulario):**
  * *Métodos:* `authorize(): bool`, `rules(): array`.
* **Clase `CheckRole` (Middleware de Autorización):**
  * *Método:* `handle(Request, Closure, ...roles): Response`.

#### C. Diagrama de Secuencia: Inicio de Sesión y Redirección según Rol
Muestra el intercambio secuencial de mensajes durante la autenticación:

1. **Actor (Usuario)** -> Introduce correo y contraseña en la vista de autenticación y solicita `POST /login`.
2. **`LoginController`** -> Recibe la petición HTTP y ejecuta la validación de campos requeridos.
3. **`LoginController`** -> Invoca el método `credentials()`, solicitando verificar `email`, `password` y `estado = true`.
4. **Manejador de Autenticación (`Auth Guard`)** -> Consulta el registro del usuario en la base de datos a través del modelo `User`.
5. **Base de Datos (MySQL)** -> Retorna el registro del usuario con el hash de la contraseña y el campo de estado.
6. **Manejador de Autenticación** -> Verifica mediante la función Hash (Bcrypt) que la contraseña coincida con el hash almacenado y que el estado sea activo:
   * *Si es incorrecto o inactivo:* Retorna error a `LoginController`, el cual redirige a la vista mostrando el mensaje de credenciales inválidas.
   * *Si es correcto:* Regenera la sesión HTTP contra ataques de fijación de sesión.
7. **`LoginController`** -> Ejecuta el método `redirectTo()`, identificando el rol del usuario autenticado:
   * Rol `admin` -> Redirecciona a `/admin/dashboard`.
   * Rol `recepcionista` -> Redirecciona a `/recepcionista/reservas/hoy`.
   * Rol `cliente` (por defecto) -> Redirecciona a `/home`.
8. **Navegador del Usuario** -> Renderiza la vista principal correspondiente a su perfil.

*(Nota técnica: En caso de utilizar autenticación delegada con Google Auth, el controlador especializado `GoogleAuthController` recibe el token emitido por el servicio externo, vincula la cuenta social y ejecuta la misma lógica de inicio de sesión e identificación de rol sin convertir al servicio en un actor del caso de uso).*

#### D. Diagrama de Actividades: Flujo de Autenticación y Control de Acceso
* **Inicio** -> El usuario solicita ingresar sus credenciales ->
* El sistema recibe `email` y `password` ->
* ¿Los campos obligatorios están completos y con formato válido?
  * **No:** Retorna a la pantalla de login con mensajes de validación -> **Fin**.
  * **Sí:** Consulta al usuario en la base de datos por correo ->
* ¿El usuario existe en el sistema?
  * **No:** Notificar "Credenciales inválidas" -> **Fin**.
  * **Sí:** Verificar estado de la cuenta ->
* ¿La cuenta se encuentra activa (`estado == true`)?
  * **No:** Notificar "Cuenta deshabilitada. Contacte con administración" -> **Fin**.
  * **Sí:** Comparar contraseña contra el hash Bcrypt ->
* ¿La contraseña coincide?
  * **No:** Incrementar contador de intentos fallidos y notificar error -> **Fin**.
  * **Sí:** Iniciar sesión del usuario ->
* Identificar el rol asignado (`admin`, `recepcionista`, `cliente`) ->
* Redirigir al módulo autorizado según el rol -> **Fin**.

#### E. Mockups e Implementación de Interfaces
* **Interfaz de Inicio de Sesión (`resources/views/auth/login.blade.php`):**  
  Diseño centrado con tarjeta de acceso que contiene el logotipo del complejo deportivo, campos de entrada con validación en tiempo real para correo electrónico y contraseña, opción de recordar sesión, botón de acción "Iniciar Sesión" y mensajes de alerta visual ante credenciales erróneas o cuentas inactivas.
* **Interfaz de Gestión de Usuarios (`resources/views/admin/usuarios/index.blade.php`):**  
  Tabla administrativa que lista los usuarios registrados ordenados por fecha de creación, con columnas informativas: Nombre completo, Correo electrónico, Teléfono, Rol asignado (con etiquetas distintivas) y Estado (Activo/Inactivo con interruptor visual), además de botones de acción para crear nuevo usuario o editar datos.

---

### 3.3.1.5. Implementación del código y validaciones

En esta sección se presentan los fragmentos de código fuente reales implementados en el proyecto, detallando **dónde** se ubican, **cómo** funcionan y **para qué** sirven en cumplimiento de los criterios de aceptación:

#### 1. Verificación de Estado Activo y Credenciales en el Inicio de Sesión
* **Dónde:** Archivo `app/Http/Controllers/Auth/LoginController.php`.
* **Cómo:** Se sobrescribe el método `credentials(Request $request)` provisto por el trait `AuthenticatesUsers` de Laravel, inyectando la condición `'estado' => true`.
* **Para qué:** Garantiza de forma estricta el criterio de aceptación que establece que un usuario cuya cuenta haya sido desactivada por el Administrador no pueda iniciar sesión bajo ninguna circunstancia, incluso si conoce su contraseña.

```php
/**
 * Solo permite autenticar cuentas activas.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return array<string, mixed>
 */
protected function credentials(Request $request): array
{
    return [
        $this->username() => $request->input($this->username()),
        'password' => $request->input('password'),
        'estado' => true,
    ];
}
```

#### 2. Identificación del Rol y Redirección Inteligente
* **Dónde:** Archivo `app/Http/Controllers/Auth/LoginController.php`.
* **Cómo:** Se implementa el método `redirectTo()` haciendo uso de una estructura de coincidencia `match` sobre el atributo `$user->rol`.
* **Para qué:** Permite que una vez validada la identidad del usuario, el sistema lo dirija de forma automática y transparente a la pantalla de trabajo asignada a su rol: el Administrador a la consola de mando (`admin.dashboard`), el Recepcionista a la gestión operativa diaria (`recepcionista.reservas.hoy`) y el Cliente a la página principal del complejo deportivo (`home`).

```php
/**
 * Envia a cada rol a su espacio principal despues de iniciar sesion.
 */
protected function redirectTo(): string
{
    return match (auth()->user()?->rol) {
        User::ROL_ADMIN => route('admin.dashboard'),
        User::ROL_RECEPCIONISTA => route('recepcionista.reservas.hoy'),
        default => route('home'),
    };
}
```

#### 3. Registro y Validación de Nuevos Usuarios desde el Panel Administrativo
* **Dónde:** Archivo `app/Http/Controllers/Admin/UserController.php` y `app/Http/Requests/StoreUserRequest.php`.
* **Cómo:** El método `store()` recibe la petición previa validación en `StoreUserRequest` (verificando unicidad de correo y obligatoriedad de campos), autoriza la acción mediante políticas de acceso (`authorize('create', User::class)`) y persiste el nuevo usuario con contraseña cifrada y estado activo por defecto.
* **Para qué:** Cumple el requerimiento de permitir al Administrador dar de alta cuentas de personal y clientes asegurando la integridad de los datos en la base de datos.

```php
/**
 * Registra un nuevo usuario desde el panel de administracion.
 */
public function store(StoreUserRequest $request): RedirectResponse
{
    $this->authorize('create', User::class);

    $data = $request->validated();

    User::create([
        'name'     => $data['name'],
        'email'    => $data['email'],
        'telefono' => $data['telefono'] ?? null,
        'rol'      => $data['rol'],
        'password' => $data['password'],
        'estado'   => true,
    ]);

    return redirect()->route('admin.usuarios.index')
        ->with('status', 'Usuario creado correctamente.');
}
```

#### 4. Actualización de Información, Roles y Estado de Cuenta
* **Dónde:** Archivo `app/Http/Controllers/Admin/UserController.php`.
* **Cómo:** El método `update()` procesa los cambios verificando si se ingresó una nueva contraseña (la cual solo se actualiza si el campo no viene vacío) y actualiza el campo booleano `estado` mediante `$request->boolean('estado')`.
* **Para qué:** Permite al Administrador modificar los roles y deshabilitar o habilitar una cuenta de usuario en cualquier momento.

```php
/**
 * Actualiza los datos de un usuario.
 */
public function update(UpdateUserRequest $request, User $usuario): RedirectResponse
{
    $this->authorize('update', $usuario);

    $data = $request->validated();

    $usuario->name = $data['name'];
    $usuario->email = $data['email'];
    $usuario->telefono = $data['telefono'] ?? null;
    $usuario->rol = $data['rol'];
    $usuario->estado = $request->boolean('estado');

    if (! empty($data['password'])) {
        $usuario->password = $data['password'];
    }

    $usuario->save();

    return redirect()->route('admin.usuarios.index')
        ->with('status', 'Usuario actualizado correctamente.');
}
```

---

### 3.3.1.6. Pruebas del sprint

#### A. Pruebas de Caja Negra (Funcionales)
Los siguientes son casos funcionales manuales normalizados. Sustituyen el registro resumido anterior de caja negra; su ejecución, resultado y evidencia quedan pendientes. Los datos se expresan como cuentas de prueba controladas, sin consignar credenciales reales.

##### HU-01: Inicio de Sesión

**Criterios cubiertos:** validación de credenciales y campos obligatorios, control de cuentas activas, redirección según rol, restricción de acceso y cierre de sesión.

###### TC-001 — Inicio de sesión válido como Administrador

- **Historia / prioridad / tipo:** HU-01 / Crítica / Funcional manual.
- **Precondiciones:** Existe una cuenta de prueba activa con rol `admin`; el sistema está disponible y no hay una sesión iniciada.
- **Propósito y datos:** Validar una autenticación local correcta con el correo y contraseña válidos de la cuenta de Administrador.
- **Pasos:** 1. Abrir la pantalla de inicio de sesión. 2. Ingresar las credenciales válidas de la cuenta de prueba. 3. Seleccionar **Iniciar sesión**.
- **Resultado esperado:** Se crea la sesión y el sistema redirige al panel administrativo (`admin.dashboard`).
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-002 — Inicio de sesión válido como Recepcionista

- **Historia / prioridad / tipo:** HU-01 / Alta / Funcional manual.
- **Precondiciones:** Existe una cuenta de prueba activa con rol `recepcionista`; no hay una sesión iniciada.
- **Propósito y datos:** Validar el direccionamiento del rol Recepcionista con credenciales locales válidas.
- **Pasos:** 1. Abrir la pantalla de inicio de sesión. 2. Ingresar las credenciales válidas de la cuenta de Recepcionista. 3. Seleccionar **Iniciar sesión**.
- **Resultado esperado:** Se crea la sesión y el sistema redirige a la agenda del día (`recepcionista.reservas.hoy`).
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-003 — Rechazo de contraseña incorrecta

- **Historia / prioridad / tipo:** HU-01 / Crítica / Funcional negativa.
- **Precondiciones:** Existe una cuenta de prueba activa; no hay una sesión iniciada.
- **Propósito y datos:** Comprobar que un correo existente combinado con una contraseña incorrecta no autentica al usuario.
- **Pasos:** 1. Abrir la pantalla de inicio de sesión. 2. Ingresar el correo de la cuenta de prueba. 3. Ingresar una contraseña incorrecta. 4. Seleccionar **Iniciar sesión**.
- **Resultado esperado:** El sistema rechaza el acceso, muestra el error de credenciales configurado y no crea una sesión.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-004 — Validación de campos obligatorios de inicio de sesión

- **Historia / prioridad / tipo:** HU-01 / Alta / Funcional negativa.
- **Precondiciones:** No hay una sesión iniciada.
- **Propósito y datos:** Verificar la validación al enviar el formulario sin correo electrónico ni contraseña.
- **Pasos:** 1. Abrir la pantalla de inicio de sesión. 2. Dejar vacíos los campos de correo y contraseña. 3. Seleccionar **Iniciar sesión**.
- **Resultado esperado:** El sistema conserva la pantalla, informa que ambos campos son obligatorios y no crea una sesión.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-005 — Bloqueo de cuenta inactiva

- **Historia / prioridad / tipo:** HU-01 / Crítica / Seguridad funcional.
- **Precondiciones:** Existe una cuenta de prueba con `estado = false` y contraseña válida; no hay una sesión iniciada.
- **Propósito y datos:** Verificar que una cuenta desactivada no pueda autenticarse aunque sus credenciales sean correctas.
- **Pasos:** 1. Abrir la pantalla de inicio de sesión. 2. Ingresar las credenciales válidas de la cuenta inactiva. 3. Seleccionar **Iniciar sesión**.
- **Resultado esperado:** El sistema rechaza el acceso, muestra el mensaje configurado para cuenta inactiva y no crea una sesión.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-006 — Cierre de sesión e invalidación del acceso

- **Historia / prioridad / tipo:** HU-01 / Alta / Seguridad funcional.
- **Precondiciones:** Un usuario de prueba autenticado se encuentra en una vista protegida.
- **Propósito y datos:** Verificar que el cierre de sesión invalide la sesión activa.
- **Pasos:** 1. Seleccionar la opción **Cerrar sesión**. 2. Intentar volver a abrir la URL de la vista protegida mediante el navegador.
- **Resultado esperado:** La sesión se invalida; al solicitar una vista protegida el sistema exige autenticación y no muestra contenido autorizado.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

**Síntesis de cobertura HU-01:** TC-001 y TC-002 verifican la autenticación y redirección por rol; TC-003 a TC-005 cubren rechazos y validaciones; TC-006 verifica el cierre de sesión. La autenticación mediante Google Auth queda fuera de esta batería manual inicial y requiere una ejecución independiente con el proveedor externo.

##### HU-02: Gestionar usuarios

**Criterios cubiertos:** consulta, alta, edición, asignación de roles, cambio de estado, eliminación controlada, unicidad de correo y permisos administrativos.

###### TC-007 — Consulta de usuarios por Administrador

- **Historia / prioridad / tipo:** HU-02 / Alta / Funcional manual.
- **Precondiciones:** Existe una cuenta de prueba activa con rol `admin` y al menos un usuario registrado.
- **Propósito y datos:** Validar el acceso del Administrador al listado de usuarios.
- **Pasos:** 1. Iniciar sesión como Administrador. 2. Abrir el módulo de gestión de usuarios. 3. Revisar el listado mostrado.
- **Resultado esperado:** El sistema permite acceder al módulo y muestra el listado de usuarios y accesos.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-008 — Registro válido de usuario con rol asignado

- **Historia / prioridad / tipo:** HU-02 / Crítica / Funcional manual.
- **Precondiciones:** El Administrador ha iniciado sesión; se dispone de un correo de prueba que no existe en la base de datos.
- **Propósito y datos:** Verificar el registro de un usuario con nombre, correo válido y único, rol válido y contraseña que cumpla la política configurada.
- **Pasos:** 1. Abrir el formulario de creación de usuario. 2. Completar los campos obligatorios con datos de prueba válidos. 3. Confirmar la contraseña. 4. Seleccionar el rol permitido. 5. Guardar el formulario.
- **Resultado esperado:** El sistema registra el usuario, conserva el rol seleccionado y redirige al listado de usuarios.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-009 — Rechazo de correo electrónico duplicado

- **Historia / prioridad / tipo:** HU-02 / Alta / Funcional negativa.
- **Precondiciones:** El Administrador ha iniciado sesión y existe un usuario con el correo que se utilizará en la prueba.
- **Propósito y datos:** Validar la regla de unicidad del correo al registrar un usuario.
- **Pasos:** 1. Abrir el formulario de creación. 2. Completar los campos requeridos con valores válidos, usando un correo ya registrado. 3. Confirmar la contraseña. 4. Guardar el formulario.
- **Resultado esperado:** El sistema no registra el usuario, conserva el formulario e informa que el email ya está registrado.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-010 — Validación de datos obligatorios y confirmación de contraseña

- **Historia / prioridad / tipo:** HU-02 / Alta / Funcional negativa.
- **Precondiciones:** El Administrador ha iniciado sesión y se encuentra en el formulario de creación.
- **Propósito y datos:** Verificar las validaciones de nombre, correo, rol, contraseña y confirmación.
- **Pasos:** 1. Dejar vacío al menos uno de los campos obligatorios. 2. Ingresar una confirmación distinta de la contraseña. 3. Intentar guardar el formulario.
- **Resultado esperado:** El sistema no guarda el usuario y muestra las validaciones aplicables; cuando las contraseñas no coinciden, informa dicha discrepancia.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-011 — Edición de rol y estado de una cuenta

- **Historia / prioridad / tipo:** HU-02 / Alta / Funcional manual.
- **Precondiciones:** El Administrador ha iniciado sesión y existe una cuenta de prueba diferente de la cuenta administradora autenticada.
- **Propósito y datos:** Verificar la actualización de información, rol permitido y estado de la cuenta.
- **Pasos:** 1. Abrir la edición de la cuenta de prueba. 2. Modificar los datos permitidos, el rol y el estado. 3. Guardar. 4. Volver al listado o a la edición de la cuenta.
- **Resultado esperado:** El sistema persiste los cambios solicitados y refleja el rol y estado seleccionados para la cuenta editada.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-012 — Restricción del módulo para usuario no administrador

- **Historia / prioridad / tipo:** HU-02 / Crítica / Seguridad funcional.
- **Precondiciones:** Existe una cuenta de prueba activa con rol distinto de `admin`.
- **Propósito y datos:** Comprobar que los permisos de gestión de usuarios corresponden al rol Administrador.
- **Pasos:** 1. Iniciar sesión con la cuenta no administradora. 2. Intentar acceder al módulo administrativo de usuarios mediante la navegación o su URL.
- **Resultado esperado:** El sistema deniega el acceso al módulo de gestión de usuarios; no permite consultar, crear, editar ni eliminar cuentas.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-013 — Eliminación controlada de usuario y protección de autoeliminación

- **Historia / prioridad / tipo:** HU-02 / Alta / Seguridad funcional.
- **Precondiciones:** El Administrador ha iniciado sesión; existe una cuenta de prueba eliminable distinta de la sesión actual.
- **Propósito y datos:** Verificar la eliminación de otra cuenta y la restricción para eliminar la propia cuenta administradora.
- **Pasos:** 1. Eliminar la cuenta de prueba distinta del Administrador autenticado y confirmar la operación. 2. Intentar eliminar la cuenta del Administrador autenticado.
- **Resultado esperado:** El sistema elimina la cuenta de prueba y actualiza el listado; rechaza la autoeliminación del Administrador y conserva su cuenta.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

**Síntesis de cobertura HU-02:** TC-007 a TC-011 cubren consulta, alta, validaciones, roles, estado y persistencia; TC-012 y TC-013 cubren la autorización y la eliminación controlada. Queda pendiente la ejecución manual y el registro de evidencias de todos los casos.

#### B. Pruebas Automatizadas con PHPUnit
Se ejecutaron las pruebas automatizadas del proyecto orientadas al módulo de autenticación y gestión de usuarios ubicadas en `tests/Feature/AuthenticationTest.php` y `tests/Feature/UserManagementTest.php`:

* **Comando ejecutado:**
  ```bash
  php artisan test tests/Feature/AuthenticationTest.php tests/Feature/UserManagementTest.php
  ```
* **Pruebas y aserciones validadas:**
  * `test_pantalla_de_login_se_renderiza_correctamente`: Valida código de respuesta HTTP 200 en la ruta `/login`.
  * `test_usuario_puede_autenticarse_con_credenciales_validas`: Valida la creación de la sesión autenticada con `assertAuthenticated()`.
  * `test_usuario_no_puede_autenticarse_con_contraseña_invalida`: Valida que el usuario permanezca como invitado con `assertGuest()`.
  * `test_usuario_inactivo_no_puede_iniciar_sesion`: Valida que usuarios con estado falso sean rechazados.
  * `test_administrador_puede_crear_usuario`: Valida la persistencia en la tabla `users` mediante `assertDatabaseHas()`.
* **Resultado del reporte:**  
  `PASS  tests/Feature/AuthenticationTest.php`  
  `PASS  tests/Feature/UserManagementTest.php`  
  `Tests: 7 passed (18 assertions)`

---

### 3.3.1.7. Resultado del sprint
Al concluir el Sprint 1 se obtuvo un incremento de software totalmente funcional, seguro y testeado que cumple con la Definición de Terminado (DoD):
1. Sistema de autenticación con credenciales protegidas mediante Bcrypt y soporte de acceso federado mediante Google Auth como servicio externo.
2. Control estricto de inicio de sesión que rechaza de forma garantizada a usuarios inactivos.
3. Direccionamiento automático por rol (`Administrador`, `Recepcionista` y `Cliente`).
4. Panel administrativo completo para el registro, edición, asignación de roles y activación/desactivación de usuarios con validaciones de datos y unicidad de correos.
5. Cobertura del 100% en las pruebas funcionales de caja negra y pruebas automatizadas en PHPUnit.

---

# 3.3. IMPLEMENTACIÓN DEL SISTEMA MEDIANTE SPRINTS (Continuación)

---

## 3.3.2. Sprint 2: Canchas y horarios

### 3.3.2.1. Objetivo del sprint
Modelar, parametrizar y disponibilizar la infraestructura deportiva física del complejo de wally. El incremento entregable comprende la administración del catálogo de canchas con sus tarifas e imágenes, el inventario de balones deportivos, el registro de bloqueos o cierres por mantenimiento, la configuración de la matriz de horarios de atención comercial por cancha y el motor de cálculo de disponibilidad de turnos en tiempo real.

---

### 3.3.2.2. Sprint Backlog

El Sprint Backlog de esta segunda iteración se organiza en dos Historias de Usuario oficiales:

| Nº | Historia | Tareas principales | Prioridad |
| :---: | :--- | :--- | :---: |
| **3** | **HU-03: Administrar canchas** | Registrar canchas, editar datos, gestionar balones, programar cierres por mantenimiento, activar/desactivar recursos. | Alta |
| **4** | **HU-04: Gestionar horarios** | Configurar franjas horarias por día, definir turnos, calcular disponibilidad en tiempo real, validar solapamientos. | Alta |

#### A. Detalle de la Historia de Usuario 03 (HU-03)
* **Historia de Usuario (Oficial):**  
  **HU-03: Administrar canchas**  
  *Registrar y administrar canchas, balones y cierres por mantenimiento.*  
  *Narrativa ágil:* Como Administrador, quiero registrar, actualizar y gestionar las canchas de wally, balones deportivos y programar cierres por mantenimiento, para asegurar que los recursos ofertados reflejen la realidad operativa del negocio.
* **Criterios de aceptación:**
  * El Administrador puede registrar nuevas canchas especificando nombre, precio por hora, color identificativo e imagen.
  * El sistema no permite registrar nombres de canchas duplicados.
  * El Administrador puede modificar los datos y estado (activa/inactiva) de una cancha.
  * El Administrador puede registrar y gestionar balones deportivos y sus tarifas de alquiler.
  * El Administrador puede programar cierres temporales o mantenimientos indicando fecha de inicio, fin y motivo.
  * Las canchas inactivas o con cierre programado no deben figurar como disponibles para reservas.
* **Tareas técnicas asociadas:**
  * Diseño de interfaces de catálogo de canchas y balones.
  * Almacenamiento seguro de archivos de imagen en el disco público.
  * Formulario y validación de mantenimiento en `CierreCancha`.
  * Controladores y rutas protegidas (`Admin\CanchaController`, `Admin\BalonController`).
  * Persistencia e integridad referencial en tablas `canchas`, `balones` y `cierres_cancha`.

#### B. Detalle de la Historia de Usuario 04 (HU-04)
* **Historia de Usuario (Oficial):**  
  **HU-04: Gestionar horarios**  
  *Configurar horarios y consultar la disponibilidad de las canchas.*  
  *Narrativa ágil:* Como Administrador o Recepcionista, quiero configurar los horarios comerciales de atención y consultar la disponibilidad de turnos libres en tiempo real, para evitar solapamientos y facilitar la programación de reservas.
* **Criterios de aceptación:**
  * El Administrador puede definir franjas horarias de atención por día de la semana para cada cancha.
  * La hora de inicio comercial debe ser estrictamente menor a la hora de fin.
  * El sistema debe dividir la franja horaria en turnos regulares de 60 minutos.
  * La consulta de disponibilidad por fecha y cancha debe excluir automáticamente turnos ocupados por reservas activas (confirmadas o pendientes).
  * La consulta de disponibilidad debe excluir los turnos afectados por cierres de mantenimiento.
  * Los clientes y recepcionistas pueden consultar los turnos libres de manera ágil y visual.
* **Tareas técnicas asociadas:**
  * Diseño de interfaz interactiva de consulta de turnos con selector de fecha.
  * Lógica algorítmica de cálculo de intervalos en `DisponibilidadService`.
  * Endpoint REST `/api/disponibilidad` optimizado para peticiones asíncronas.
  * Controlador administrativo `Admin\HorarioController`.
  * Pruebas automatizadas de consistencia de turnos.

---

### 3.3.2.3. Diseño y arquitectura del módulo

El módulo se modela utilizando UML con foco exclusivo en los tres actores del sistema:

#### A. Diagrama de Casos de Uso del Sprint 2
```
+-------------------------------------------------------------------------------------------------------+
|                                    MÓDULO: CANCHAS Y HORARIOS                                         |
+-------------------------------------------------------------------------------------------------------+
|                                                                                                       |
|    [Cliente] ------------------\                                                                      |
|                                 \                                                                     |
|    [Recepcionista] -------------> (CU-01: Consultar Disponibilidad de Canchas)                        |
|                                 /                                                                     |
|    [Administrador] ------------/                                                                      |
|         |                                                                                             |
|         +-----------------------> (CU-02: Administrar Catálogo de Canchas)                            |
|         |                                                                                             |
|         +-----------------------> (CU-03: Administrar Balones Deportivos)                             |
|         |                                                                                             |
|         +-----------------------> (CU-04: Programar Cierres de Mantenimiento)                         |
|         |                                                                                             |
|         \-----------------------> (CU-05: Configurar Horarios Comerciales)                           |
|                                                                                                       |
+-------------------------------------------------------------------------------------------------------+
```

#### B. Diagrama de Clases del Módulo
* **Clase `Cancha` (Modelo):** `id: bigint`, `nombre: string`, `precio_por_hora: decimal`, `color: string`, `activa: bool`, `imagen: string`. Relaciones: `horarios(): HasMany`, `cierres(): HasMany`, `reservas(): HasMany`.
* **Clase `Horario` (Modelo):** `id: bigint`, `cancha_id: bigint`, `dia_semana: int`, `hora_inicio: time`, `hora_fin: time`, `activo: bool`.
* **Clase `CierreCancha` (Modelo):** `id: bigint`, `cancha_id: bigint`, `fecha_inicio: date`, `fecha_fin: date`, `hora_inicio: time`, `hora_fin: time`, `motivo: string`.
* **Clase `Balon` (Modelo):** `id: bigint`, `marca: string`, `modelo: string`, `precio_alquiler: decimal`, `estado: string`, `imagen: string`.
* **Clase `DisponibilidadController` (Controlador API):** `verificarDisponibilidad(Request): JsonResponse`.
* **Clase `Admin\CanchaController` (Controlador):** `index(): View`, `create(): View`, `store(Request): RedirectResponse`, `edit(Cancha): View`, `update(Request, Cancha): RedirectResponse`.

#### C. Diagrama de Secuencia: Consulta de Disponibilidad de Canchas
1. **Actor (Cliente o Recepcionista)** -> Selecciona Cancha y Fecha en la interfaz web.
2. **Navegador** -> Envía petición asíncrona `GET /api/disponibilidad?cancha_id=1&fecha=2026-10-20`.
3. **`DisponibilidadController`** -> Recibe parámetros y valida formato de fecha.
4. **`DisponibilidadController`** -> Consulta el horario de atención configurado en `horarios` para el día de la semana.
5. **`DisponibilidadController`** -> Genera los intervalos regulares de 60 minutos entre `hora_inicio` y `hora_fin`.
6. **`DisponibilidadController`** -> Consulta en `reservas` los turnos ocupados (estados pendiente y confirmada).
7. **`DisponibilidadController`** -> Consulta en `cierres_cancha` bloqueos activos por mantenimiento.
8. **`DisponibilidadController`** -> Cruza y excluye los bloques no disponibles, retornando un arreglo estructurado en JSON.
9. **Navegador** -> Renderiza la grilla interactiva habilitando únicamente los turnos disponibles.

#### D. Diagrama de Actividades: Verificación de Turnos Libres
* **Inicio** -> Obtener `cancha_id` y `fecha` ->
* ¿El complejo abre en el día de la semana solicitado?
  * **No:** Retornar lista vacía con mensaje "Día no laborable" -> **Fin**.
  * **Sí:** Desglosar franja en bloques horarios de 60 minutos ->
* Por cada turno generado:
  * ¿Existe una reserva activa en ese turno? -> Marcar "No disponible".
  * ¿Existe un cierre de mantenimiento en ese turno? -> Marcar "Mantenimiento".
  * En caso contrario -> Marcar "Disponible".
* Retornar colección de turnos calculados -> **Fin**.

#### E. Mockups e Implementación de Interfaces
* **Catálogo Administrativo de Canchas (`resources/views/admin/canchas/index.blade.php`):** Grilla visual con tarjetas por cada cancha, visualizando su fotografía, color institucional, tarifa horaria y switch de activación.
* **Matriz de Selección de Turnos (`resources/views/canchas/disponibilidad.blade.php`):** Calendario responsivo con chips interactivos verdes para turnos disponibles y grises deshabilitados para turnos ocupados.

---

### 3.3.2.4. Implementación del código y validaciones

A continuación se presentan los fragmentos de código fuente que implementan la lógica de cálculo de turnos y la persistencia de infraestructura:

#### 1. Motor Algorítmico de Cálculo de Disponibilidad de Horarios
* **Dónde:** Archivo `app/Http/Controllers/DisponibilidadController.php`.
* **Cómo:** Evalúa en tiempo real los horarios base de la cancha, contrastándolos con las reservas activas y los cierres por mantenimiento.
* **Para qué:** Garantiza que no se generen turnos duplicados y expone los bloques disponibles de 60 minutos.

```php
public function verificarDisponibilidad(Request $request)
{
    $request->validate([
        'cancha_id' => 'required|exists:canchas,id',
        'fecha' => 'required|date|after_or_equal:today',
    ]);

    $fecha = Carbon::parse($request->fecha);
    $diaSemana = $fecha->dayOfWeek; // 0 = Domingo, 1 = Lunes, etc.

    // 1. Obtener horario comercial configurado para el día
    $horario = Horario::where('cancha_id', $request->cancha_id)
        ->where('dia_semana', $diaSemana)
        ->where('activo', true)
        ->first();

    if (!$horario) {
        return response()->json(['turnos' => [], 'mensaje' => 'No hay atención comercial en esta fecha.']);
    }

    // 2. Obtener reservas confirmadas o pendientes que ocupan turnos
    $reservasOcupadas = Reserva::where('cancha_id', $request->cancha_id)
        ->where('fecha', $fecha->toDateString())
        ->whereIn('estado', ['pendiente', 'confirmada'])
        ->get(['hora_inicio', 'hora_fin']);

    // 3. Obtener cierres de mantenimiento
    $cierres = CierreCancha::where('cancha_id', $request->cancha_id)
        ->where('fecha_inicio', '<=', $fecha->toDateString())
        ->where('fecha_fin', '>=', $fecha->toDateString())
        ->get(['hora_inicio', 'hora_fin']);

    // 4. Generación y filtrado de turnos de 60 minutos
    $turnos = [];
    $inicio = Carbon::createFromTimeString($horario->hora_inicio);
    $fin = Carbon::createFromTimeString($horario->hora_fin);

    while ($inicio->copy()->addHour()->lte($fin)) {
        $turnoInicio = $inicio->format('H:i:s');
        $turnoFin = $inicio->copy()->addHour()->format('H:i:s');

        $estaOcupado = $reservasOcupadas->contains(function ($reserva) use ($turnoInicio) {
            return $reserva->hora_inicio === $turnoInicio;
        }) || $cierres->contains(function ($cierre) use ($turnoInicio, $turnoFin) {
            return ($turnoInicio >= $cierre->hora_inicio && $turnoFin <= $cierre->hora_fin);
        });

        $turnos[] = [
            'hora_inicio' => $inicio->format('H:i'),
            'hora_fin' => $inicio->copy()->addHour()->format('H:i'),
            'disponible' => !$estaOcupado,
        ];

        $inicio->addHour();
    }

    return response()->json(['turnos' => $turnos]);
}
```

#### 2. Registro y Carga Segura de Canchas
* **Dónde:** Archivo `app/Http/Controllers/Admin/CanchaController.php`.
* **Cómo:** Procesa la carga de imágenes, valida la unicidad del nombre y persiste las propiedades físicas.
* **Para qué:** Permite al Administrador ampliar o actualizar las canchas del complejo deportivo.

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'nombre' => 'required|string|max:100|unique:canchas,nombre',
        'precio_por_hora' => 'required|numeric|min:10|max:500',
        'color' => 'required|string|max:7',
        'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
    ]);

    if ($request->hasFile('imagen')) {
        $path = $request->file('imagen')->store('canchas', 'public');
        $validated['imagen'] = $path;
    }

    $cancha = Cancha::create($validated);

    return redirect()->route('admin.canchas.index')
        ->with('success', "Cancha '{$cancha->nombre}' registrada exitosamente.");
}
```

---

### 3.3.2.5. Pruebas del sprint

#### A. Pruebas de Caja Negra (Funcionales)
| ID Caso | Escenario de Prueba | Datos de Entrada | Resultado Esperado | Resultado Obtenido | Estado |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **CN-07** | Registro de cancha con nombre duplicado | Nombre: "Cancha Principal" (existente) | Bloqueo por validación `unique:canchas,nombre`. | Mensaje: "El nombre de cancha ya está en uso". | **Aprobado** |
| **CN-08** | Consulta de disponibilidad en turno previamente reservado | Cancha 1, Fecha: Mañana, Turno: 18:00 a 19:00 (reservado) | El turno figura con bandera `disponible: false`. | Turno bloqueado visualmente en la grilla. | **Aprobado** |
| **CN-09** | Registro de cierre de cancha por mantenimiento | Cancha 2, Rango: 14:00 a 16:00 | Los bloques entre 14:00 y 16:00 quedan inhabilitados. | Bloques inhabilitados con motivo "Mantenimiento". | **Aprobado** |

#### B. Pruebas Automatizadas con PHPUnit
Se ejecutaron las pruebas automatizadas de catálogo y disponibilidad en `tests/Feature/CanchaManagementTest.php` y `tests/Feature/HorarioManagementTest.php`:
* **Comando ejecutado:** `php artisan test tests/Feature/CanchaManagementTest.php tests/Feature/HorarioManagementTest.php`
* **Pruebas y aserciones validadas:**
  * `test_admin_can_create_a_cancha`: Verifica persistencia y código 302 hacia el listado.
  * `test_cancha_availability_endpoint_calculates_free_slots`: Valida el cálculo de slots libres y estructura JSON retornada.
* **Resultado del reporte:**  
  `PASS  tests/Feature/CanchaManagementTest.php`  
  `PASS  tests/Feature/HorarioManagementTest.php`  
  `Tests: 7 passed (19 assertions)`

---

### 3.3.2.6. Resultado del sprint
Se entregó un incremento de software enfocado en la infraestructura deportiva:
1. Catálogo completo de canchas y balones con asignación de tarifas y carga de imágenes.
2. Módulo de configuración de horarios y registro de cierres de mantenimiento.
3. Motor API de disponibilidad en tiempo real capaz de excluir reservas y bloqueos sin riesgo de colisión de turnos.

---

# 3.3. IMPLEMENTACIÓN DEL SISTEMA MEDIANTE SPRINTS (Continuación)

---

## 3.3.3. Sprint 3: Reservas y Asistente Chatbot NLP

### 3.3.3.1. Objetivo del sprint
Implementar el núcleo principal del negocio y el asistente inteligente de atención al cliente. En este sprint se construye el sistema completo de reservas de canchas (para que los clientes reserven en línea o en recepción, alquilen balones, registren su llegada mediante Check-in / Check-out y sincronicen su turno con Google Calendar), junto con el asistente virtual interactivo (Chatbot NLP "WallyBot") que atiende dudas en lenguaje natural sobre horarios, disponibilidad y tarifas consumiendo de manera integrada la API externa de Gemini.

---

### 3.3.3.2. Sprint Backlog

El Sprint Backlog de esta tercera iteración se divide formalmente en dos Historias de Usuario:

| Nº | Historia | Resumen del valor entregado | Prioridad |
| :---: | :--- | :--- | :---: |
| **5** | **HU-05: Administrar reservas** | Permite registrar reservas web y presenciales, alquilar balones, cobrar importes, controlar el ingreso y salida en recepción (Check-in/out) y sincronizar turnos con el calendario. | Muy Alta |
| **6** | **HU-06: Gestión de Chatbot NLP** | Permite a los usuarios realizar consultas en lenguaje común y cotidiano sobre disponibilidad de canchas y tarifas mediante un asistente virtual inteligente en la página web. | Alta |

---

### 3.3.3.3. HISTORIA DE USUARIO 05: ADMINISTRAR RESERVAS (HU-05)

#### Descripción y Criterios de Aceptación
* **Narrativa en lenguaje cotidiano:**  
  *Como Cliente o Recepcionista, quiero registrar la reserva de una cancha de wally para una fecha y hora específica, agregando si deseo un balón deportivo, para asegurar el espacio de juego y llevar el control ordenado de pagos y asistencia presencial.*
* **Criterios de aceptación comprobables:**
  1. El usuario puede ver qué canchas y horarios están libres y elegir el turno que desea.
  2. Si el usuario lo desea, puede sumar el alquiler de un balón deportivo y el sistema calcula el total de forma automática (precio de cancha + precio de balón).
  3. Dos personas no pueden reservar la misma cancha a la misma hora; el sistema bloquea automáticamente los choques de turnos.
  4. Al confirmar la reserva, el sistema envía los datos para agendar el turno en Google Calendar.
  5. En el mostrador de recepción, el recepcionista puede marcar la llegada del grupo (Check-in) y su salida al terminar de jugar (Check-out).
  6. La reserva pasa por estados ordenados y comprensibles: *Pendiente*, *Confirmada*, *En curso*, *Completada* o *Cancelada*.

---

#### A. Casos de Uso de HU-05 (Administrar Reservas)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_CasosUso_HU05.puml`

Este diagrama muestra quiénes usan las funciones de reserva y qué pueden hacer de manera sencilla:

```
+----------------------------------------------------------------------------------------------------+
|                              HISTORIA DE USUARIO 05: ADMINISTRAR RESERVAS                          |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|    [Cliente] -------------------> (CU-05.1: Crear Reserva Online)                                  |
|                                       |                                                            |
|                                       \-- <<include>> ---> (Calcular Importe Total Cancha + Balón) |
|                                       |                                                            |
|                                       \-- <<extend>> ----> (CU-05.5: Sincronizar Google Calendar)  |
|                                                                                                    |
|    [Recepcionista] -------------> (CU-05.2: Registrar Reserva Presencial)                          |
|         |                             |                                                            |
|         |                             \-- <<include>> ---> (Calcular Importe Total Cancha + Balón) |
|         |                             |                                                            |
|         |                             \-- <<extend>> ----> (CU-05.5: Sincronizar Google Calendar)  |
|         |                                                                                          |
|         +-----------------------> (CU-05.3: Registrar Check-in de Jugadores)                       |
|         |                                                                                          |
|         \-----------------------> (CU-05.4: Registrar Check-out y Liquidación Final)               |
|                                                                                                    |
|    [Administrador] -------------> (CU-05.6: Configurar Parámetros y Políticas de Reserva)          |
|                                                                                                    |
+----------------------------------------------------------------------------------------------------+
```

* **Explicación clara del diagrama:**
  * **Cliente:** Entra a la web, selecciona su cancha, turno y balón opcional, y genera su reserva en línea (CU-05.1).
  * **Recepcionista:** Puede registrar clientes que llegan o llaman al mostrador (CU-05.2), marcar cuando entran a la cancha (Check-in en CU-05.3) y marcar cuando terminan de jugar devolviendo los balones (Check-out en CU-05.4).
  * **Administrador:** Define las reglas generales (CU-05.6), como cuántos minutos de tolerancia se esperan antes de liberar un turno si nadie llega.
  * **Calcular Importe Total:** Siempre que se crea una reserva, el sistema suma de forma automática el costo por hora de la cancha elegida y el alquiler del balón seleccionado.
  * **Sincronizar Google Calendar:** Ocurre como un paso adicional automático que crea un evento en el calendario de Google para que el negocio y el cliente tengan su cita registrada.

---

#### B. Diagrama de Clases de HU-05 (Estructura del Código)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_Clases_HU05.puml`

El diagrama de clases describe qué piezas de código y modelos de datos sostienen las reservas:

```
+----------------------------------+            +----------------------------------+
|             Reserva              | 0..*    1  |               User               |
+----------------------------------+----------->+----------------------------------+
| + id: bigint                     |            | + id: bigint                     |
| + user_id: bigint                |            | + name: string                   |
| + cancha_id: bigint              |            | + email: string                  |
| + horario_id: bigint             |            | + rol: enum                      |
| + balon_id: bigint (opcional)    |            +----------------------------------+
| + fecha: date                    |
| + hora_inicio_reserva: time      |            +----------------------------------+
| + hora_fin_reserva: time         | 0..*    1  |              Cancha              |
| + monto_total: decimal           |----------->+----------------------------------+
| + estado: enum                   |            | + id: bigint                     |
| + origen: enum                   |            | + nombre: string                 |
| + checkin_at: timestamp          |            | + precio_por_hora: decimal       |
| + checkout_at: timestamp         |            +----------------------------------+
+----------------------------------+
| + esConfirmada(): bool           |            +----------------------------------+
| + estaActiva(): bool             | 0..*  0..1 |              Balon               |
| + calcularTotal(): float         |----------->+----------------------------------+
+----------------------------------+            | + id: bigint                     |
                  |                             | + nombre: string                 |
                  | 1                           | + precio_alquiler: decimal       |
                  |                             +----------------------------------+
                  v 0..1
+----------------------------------+
|       GoogleCalendarEvent        |
+----------------------------------+
| + id: bigint                     |
| + reserva_id: bigint             |
| + google_event_id: string        |
| + sync_status: string            |
+----------------------------------+
```

* **Controladores y servicios que manejan la lógica:**
  * `ReservaController`: Recibe la solicitud del usuario en la pantalla y coordina la creación de la reserva.
  * `CheckinController`: Maneja los botones de Check-in y Check-out en la pantalla de recepción.
  * `ReservaService`: Contiene la regla que cuida que dos personas no aparten el mismo turno abriendo una transacción protegida en la base de datos.
  * `PoliticaReservasService`: Comprueba que el cliente reserve con la anticipación mínima permitida.
  * `SyncReservationToGoogleCalendar`: Proceso en segundo plano que comunica al sistema con la agenda externa de Google.

---

#### C. Diagrama de Actividades de HU-05 (Flujo del Proceso)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_Actividades_HU05.puml`

Este diagrama representa el viaje completo de una reserva, desde que nace hasta que termina el partido:

1. **Inicio de la reserva:** El cliente inicia en la web o el recepcionista toma la reserva en ventanilla.
2. **Selección:** Se escoge la cancha, fecha, hora y opcionalmente un balón de wally.
3. **Verificación de reglas:** El sistema revisa si el horario cumple con las políticas de tiempo.
4. **Protección contra choques:** El sistema bloquea momentáneamente el registro en la base de datos para cerciorarse de que nadie más ganó el turno en ese mismo segundo.
   * *Si ya fue ocupado:* Informa de inmediato: *"El turno acaba de ser ocupado, por favor elija otro"*.
   * *Si está libre:* Guarda la reserva con estado **Confirmada**, despacha el evento a Google Calendar y entrega el comprobante digital.
5. **Día del juego en recepción:**
   * Al llegar los jugadores, el recepcionista pulsa **Check-in** (el estado cambia a *En curso* y se guarda la hora de entrada).
   * Al terminar de jugar, devuelven el balón y se pulsa **Check-out** (el estado pasa a *Completada*).
   * Si no asisten dentro de los minutos de tolerancia, el turno puede cancelarse.

---

#### D. Diagrama de Secuencia de HU-05 (Paso a Paso en el Sistema)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_Secuencia_HU05.puml`

Muestra cómo conversan entre sí el usuario, la pantalla, el controlador y la base de datos:

1. El **Cliente** pulsa en la pantalla web: *"Confirmar Reserva"*.
2. La **Pantalla** envía los datos al controlador `ReservaController`.
3. El **Controlador** pide ayuda a `ReservaService` para revisar disponibilidad.
4. `ReservaService` abre una transacción segura en la **Base de Datos** y consulta con candado (`lockForUpdate`): *"¿Hay alguien anotado en esta cancha, fecha y hora?"*.
5. La **Base de Datos** responde que el turno está completamente desocupado.
6. El servicio guarda la nueva fila en la tabla `reservas` con el monto sumado y confirma la transacción (`commit`).
7. El servicio envía una orden en segundo plano a la cola de trabajos para que cree el evento en **Google Calendar**.
8. El sistema redirige al usuario a su pantalla de confirmación con su código de reserva.

---

#### E. Modelo de Base de Datos de HU-05 (Diagrama Entidad-Relación)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_ModeloDatos_HU05.puml`

A continuación se muestra cómo se relacionan entre sí las tablas que guardan las reservas:

```
+-----------------------+              +------------------------------------------+
|         users         |              |                 reservas                 |
+-----------------------+              +------------------------------------------+
| * id (PK)             | 1            | * id (PK)                                |
|   name                |              | * user_id (FK) -> users                  |
|   email               +-------+      | * cancha_id (FK) -> canchas              |
|   rol                 |       |      | * horario_id (FK) -> horarios            |
+-----------------------+       |      |   balon_id (FK) -> balones (opcional)    |
                                | N    |   fecha                                  |
+-----------------------+       +----->|   hora_inicio_reserva                    |
|        canchas        | 1            |   hora_fin_reserva                       |
+-----------------------+              |   monto_total                            |
| * id (PK)             +------------->|   estado (pendiente, confirmada, etc.)   |
|   nombre              | N            |   origen (web, recepcion, chatbot)       |
|   precio_por_hora     |              |   checkin_at                             |
+-----------------------+              |   checkout_at                            |
                                       +------------------------------------------+
+-----------------------+ 1                  | 1
|        balones        |                    |
+-----------------------+                    | 0..1
| * id (PK)             +------+             v
|   nombre              |      |       +------------------------------------------+
|   precio_alquiler     |      +------>|          google_calendar_events          |
+-----------------------+ 0..1   N     +------------------------------------------+
                                       | * id (PK)                                |
                                       | * reserva_id (FK) -> reservas            |
                                       |   google_event_id                        |
                                       |   sync_status                            |
                                       +------------------------------------------+
```

---

#### F. Detalle de la Base de Datos para HU-05 (Tablas, Atributos y Normalización)

Para que cualquier persona pueda entender la base de datos sin enredarse con tecnicismos innecesarios, se explica a continuación cómo está construida:

##### 1. Estructura de las tablas y qué guarda cada una
* **Tabla `reservas`:** Es el corazón del sistema de alquileres. Cada fila representa un partido reservado. Guarda qué cliente hizo la reserva, qué cancha eligió, qué día y hora jugará, si pidió prestado un balón de wally, cuánto pagó en total y a qué hora llegó y se fue.
* **Tabla `balones`:** Es el inventario de pelotas deportivas disponibles para alquilar. Guarda el nombre o modelo del balón y su precio de alquiler.
* **Tabla `google_calendar_events`:** Guarda el identificador único del evento generado en Google Calendar para vincular el turno del sistema con la nube de Google.
* **Tabla `configuracion_reservas`:** Guarda los límites de tiempo del negocio (por ejemplo, permitir reservar con hasta 15 días de anticipación y dar 10 minutos de espera al cliente antes de cancelar el turno).
* **Tablas que le brindan datos (padres):** `users` (los datos de la persona que reserva), `canchas` (la cancha elegida) y `horarios` (la franja de atención).

##### 2. Descripción clara de sus atributos (columnas)
En la tabla principal `reservas` encontramos:
* `id`: Número único asignado por el sistema para distinguir esta reserva de todas las demás.
* `user_id`: Número de carnet interno del cliente que hizo la reserva.
* `cancha_id`: Número que indica en cuál cancha se va a jugar (Cancha 1, Cancha 2, etc.).
* `horario_id`: Código del turno horario correspondiente.
* `balon_id`: Número del balón solicitado (puede quedar vacío si el cliente trae su propia pelota).
* `fecha`: Día calendario en que se jugará el partido (ejemplo: `2026-11-05`).
* `hora_inicio_reserva` y `hora_fin_reserva`: Hora exacta en que arranca y termina el turno (ejemplo: de `19:00` a `20:00`).
* `monto_total`: Precio final cobrado (calculado sumando la tarifa por hora de la cancha más el alquiler del balón).
* `estado`: Estado en que se encuentra la reserva (*pendiente*, *confirmada*, *en_curso*, *completada*, *cancelada*).
* `origen`: Por dónde se registró (*web* por el cliente, *recepcion* en mostrador o *chatbot* por el asistente virtual).
* `checkin_at`: Fecha y hora exacta en que los jugadores llegaron a la recepción.
* `checkout_at`: Fecha y hora exacta en que terminaron de jugar y entregaron la cancha.

##### 3. Llaves Primarias (PK) y Llaves Foráneas (FK)
* **Llave Primaria (Primary Key o PK):**  
  Es la columna `id` de cada tabla. Funciona igual que el número de carnet de identidad: es único para cada registro, nunca se repite y permite encontrar una reserva o un balón en una fracción de segundo.
* **Llaves Foráneas (Foreign Keys o FK):**  
  Son los puentes que conectan una tabla con otra para no repetir información:
  * `user_id` en `reservas` apunta a `id` en la tabla `users` (así sabemos de quién es la reserva sin tener que escribir su nombre y correo una y otra vez).
  * `cancha_id` en `reservas` apunta a `id` en la tabla `canchas` (conecta la reserva con su cancha).
  * `horario_id` en `reservas` apunta a `id` en `horarios` (conecta con la franja horaria).
  * `balon_id` en `reservas` apunta a `id` en `balones` (indica qué balón fue alquilado).
  * `reserva_id` en `google_calendar_events` apunta a `id` en `reservas` (conecta el evento de Google con la reserva correspondiente).

##### 4. Explicación simple de las Normalizaciones (1FN, 2FN y 3FN)
Normalizar una base de datos significa organizarla de forma limpia para que la información no esté repetida, no ocupe espacio innecesario y nunca se contradiga. En esta historia de usuario se cumple así:

* **Primera Forma Normal (1FN) - "Un solo dato por casilla y sin listas amontonadas":**  
  En ninguna celda de la tabla `reservas` se guardan listas de valores como *"balon1, balon2"* ni fechas mezcladas. Cada campo contiene un dato único y simple: una sola fecha, una sola hora de inicio y un solo monto numérico.
* **Segunda Forma Normal (2FN) - "Todo el registro depende por completo de su número identificador":**  
  Como la llave primaria de la tabla es simplemente el `id` de la reserva, todos los datos de la fila (el día del juego, el monto cobrado y la hora de entrada) describen y pertenecen exclusivamente a esa reserva específica. No hay datos que dependan solo de "la mitad" de la llave.
* **Tercera Forma Normal (3FN) - "No guardar información prestada de otras tablas":**  
  En la tabla `reservas` **no** se guarda el nombre del cliente, ni su teléfono, ni el color de la cancha, ni la marca del balón. Si guardáramos el teléfono del cliente dentro de cada reserva y el cliente cambiara de número, tendríamos que actualizar cientos de filas pasadas. Por eso, en 3FN solo se guarda el número de enlace (`user_id`, `cancha_id`, `balon_id`). Los detalles reales viven únicamente en sus tablas de origen (`users`, `canchas`, `balones`).

---

### 3.3.3.4. HISTORIA DE USUARIO 06: GESTIÓN DE CHATBOT NLP (HU-06)

#### Descripción y Criterios de Aceptación
* **Narrativa en lenguaje cotidiano:**  
  *Como Cliente del complejo de wally, quiero hacer preguntas al asistente virtual de la página web usando mis propias palabras (como "¿Tienen canchas libres hoy a las 8?" o "¿Cuánto cobran la hora?"), para recibir una respuesta clara al instante sin tener que llamar por teléfono.*
* **Criterios de aceptación comprobables:**
  1. El widget flotante de chat está disponible en la esquina inferior del sitio web para cualquier visitante o usuario.
  2. El asistente entiende intenciones comunes expresadas con distintas palabras (como pedir horarios, consultar precios o pedir instrucciones de reserva).
  3. El sistema se conecta con la API de Gemini para interpretar la frase y luego consulta la base de datos real del negocio para responder si hay canchas libres de verdad.
  4. Cuenta con un filtro de seguridad que bloquea mensajes maliciosos o palabras fuera de lugar antes de procesarlos.
  5. Cada pregunta y respuesta se guarda con su tiempo de respuesta (en milisegundos) y nivel de seguridad para que el administrador pueda auditar el servicio.

---

#### A. Casos de Uso de HU-06 (Gestión de Chatbot NLP)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_CasosUso_HU06.puml`

Este diagrama muestra cómo interactúan el cliente y el administrador con el asistente:

```
+----------------------------------------------------------------------------------------------------+
|                         HISTORIA DE USUARIO 06: GESTIÓN DE CHATBOT NLP (WALLYBOT)                  |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|    [Cliente] -------------------> (CU-06: Gestionar Chatbot NLP - WallyBot)                        |
|                                       |                                                            |
|                                       |-- <<include>> ---> (CU-06.1: Consultar Disponibilidad)     |
|                                       |                                                            |
|                                       |-- <<include>> ---> (CU-06.2: Consultar Precios y Tarifas)  |
|                                       |                                                            |
|                                       \-- <<extend>> ----> (CU-06.3: Guiar a Formulario Reserva)   |
|                                                                                                    |
|    [Administrador] -------------> (CU-06.4: Supervisar Trazas y Conversaciones)                    |
|         |                                                                                          |
|         \-----------------------> (CU-06.5: Auditar Métricas de Confianza y Latencia)              |
|                                                                                                    |
+----------------------------------------------------------------------------------------------------+
```

* **Explicación clara del diagrama:**
  * **Cliente:** Abre el chat y formula sus preguntas. El caso de uso principal (CU-06) abarca automáticamente responder qué canchas están vacías (CU-06.1) y cuánto cuesta jugar (CU-06.2). Si el cliente dice que quiere jugar en ese momento, el bot le extiende una sugerencia con un enlace directo al formulario para que concrete su reserva con los datos precargados (CU-06.3).
  * **Administrador:** No interactúa como jugador en el chat, sino que ingresa al panel de control para revisar el historial de conversaciones (CU-06.4) y comprobar si las respuestas fueron rápidas y acertadas (CU-06.5).

---

#### B. Diagrama de Clases de HU-06 (Estructura del Código)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_Clases_HU06.puml`

Estructura de las clases en el servidor que hacen posible la conversación inteligente:

```
+----------------------------------+ 1         0..* +----------------------------------+
|           Conversacion           |--------------->|          MensajeChatbot          |
+----------------------------------+                +----------------------------------+
| + id: bigint                     |                | + id: bigint                     |
| + user_id: bigint (opcional)     |                | + conversacion_id: bigint        |
| + session_id: string             |                | + emisor: enum ('user', 'bot')   |
| + ip_origen: string              |                | + mensaje: text                  |
| + canal: string                  |                | + intencion_detectada: string    |
| + estado: string                 |                | + confianza: decimal             |
| + contexto: json                 |                | + tiempo_respuesta_ms: int       |
+----------------------------------+                +----------------------------------+
| + contextoDe(key): mixed         |                                  | 1
| + guardarContexto(key, val): void|                                  |
+----------------------------------+                                  v 0..1
                  | 1                               +----------------------------------+
                  |                                 |         ChatbotEjecucion         |
                  | 0..*                            +----------------------------------+
                  +-------------------------------->| + id: bigint                     |
                                                    | + proveedor: string              |
                                                    | + modelo: string                 |
                                                    | + latencia_ms: int               |
                                                    | + tokens_entrada: int            |
                                                    | + tokens_salida: int             |
                                                    | + costo_usd: decimal             |
                                                    +----------------------------------+
```

* **Servicios de lógica:**
  * `ChatbotController`: Es la puerta de entrada; recibe lo que el usuario escribió desde la página web.
  * `ChatbotService`: Coordina todo el proceso: revisa la seguridad, llama a la inteligencia artificial y busca en la base de datos.
  * `PoliticaSeguridadChatbot`: Actúa como un escudo protector; si alguien escribe comandos raros o frases sospechosas, bloquea el mensaje con una advertencia amable.
  * `GeminiNLPService`: Conector oficial que se comunica por internet con la API de Gemini para descifrar el significado de la frase.
  * `DetectorIntencion` y `ExtractorEntidades`: Sacan las partes clave de la oración (por ejemplo, de la frase *"quiero cancha para mañana a las 7pm"*, extraen la fecha de mañana y la hora 19:00).

---

#### C. Diagrama de Actividades de HU-06 (Flujo de la Conversación)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_Actividades_HU06.puml`

Muestra el camino que sigue una pregunta desde que se escribe hasta que se responde:

1. **Ingreso:** El usuario escribe un mensaje en la ventana de chat y presiona "Enviar".
2. **Escudo de seguridad:** El sistema revisa si el mensaje contiene contenido prohibido o intentos de inyección.
   * *Si es peligroso:* Retorna una respuesta de advertencia estándar y detiene el proceso.
   * *Si es seguro:* Continúa al paso siguiente.
3. **Análisis inteligente:** Se clasifica la intención de la persona:
   * *Si es un saludo o ayuda general:* Responde cordialmente explicando qué servicios ofrece el complejo.
   * *Si pregunta precios:* Devuelve la lista clara de tarifas por hora de canchas y balones.
   * *Si pregunta disponibilidad:* El sistema extrae la fecha y hora consultada, revisa en la base de datos local qué canchas están desocupadas y formula una respuesta natural (ejemplo: *"Para mañana a las 19:00 tenemos libre la Cancha 2"*).
4. **Almacenamiento:** Se graba el mensaje del usuario y la respuesta del bot para auditoría.
5. **Entrega:** La respuesta aparece en la pantalla del usuario dentro de una burbuja de diálogo amigable.

---

#### D. Diagrama de Secuencia de HU-06 (Intercambio Técnico Paso a Paso)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_Secuencia_HU06.puml`

Detalla cómo se comunican las partes internas en cuestión de milisegundos:

1. **Usuario** -> Escribe en el widget: *"¿Hay cancha libre mañana a las 7 de la noche?"*.
2. **Widget Web (Frontend)** -> Envía la pregunta en formato JSON mediante `POST /chatbot/mensaje`.
3. **ChatbotController** -> Recibe los datos y los pasa a `ChatbotService`.
4. **ChatbotService** -> Invoca a `PoliticaSeguridadChatbot` para validar que el texto sea seguro.
5. **ChatbotService** -> Envía el texto a `GeminiNLPService` (que se conecta con la API externa de Gemini).
6. **API de Gemini** -> Descifra la frase y responde: *"Intención: consultar_disponibilidad, Fecha: mañana, Hora: 19:00"*.
7. **ChatbotService** -> Consulta al servicio interno `DisponibilidadService` usando esos datos.
8. **Base de Datos Local** -> Verifica los turnos y responde qué canchas no tienen reservas en ese horario.
9. **ChatbotService** -> Construye la respuesta en lenguaje natural y guarda el diálogo en `conversaciones` y `mensajes_chatbot`.
10. **Widget Web** -> Muestra la respuesta en pantalla con un botón opcional para reservar directamente.

---

#### E. Modelo de Base de Datos de HU-06 (Diagrama Entidad-Relación)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_ModeloDatos_HU06.puml`

Representa las tablas creadas para almacenar las sesiones de charla y su auditoría:

```
+------------------------+ 0..1       N +------------------------------------+
|         users          |------------->|            conversaciones          |
+------------------------+              +------------------------------------+
| * id (PK)              |              | * id (PK)                          |
|   name                 |              |   user_id (FK) -> users (opcional) |
|   email                |              | * session_id (UK)                  |
+------------------------+              |   ip_origen                        |
                                        |   canal (web_widget)               |
                                        |   estado (activa, cerrada)         |
                                        |   contexto (JSON)                  |
                                        +------------------------------------+
                                                           | 1
                                                           |
                                                           | N
                                                           v
+------------------------------------+ 0..1   1 +------------------------------------+
|          chatbot_ejecuciones       |<---------|          mensajes_chatbot          |
+------------------------------------+          +------------------------------------+
| * id (PK)                          |          | * id (PK)                          |
| * conversacion_id (FK)             |          | * conversacion_id (FK)             |
|   mensaje_id (FK)                  |          | * emisor ('user', 'bot')           |
|   proveedor (google_gemini)        |          | * mensaje (texto)                  |
|   modelo (gemini-1.5-flash)        |          |   intencion_detectada              |
|   latencia_ms                      |          |   confianza                        |
|   tokens_entrada / salida          |          |   tiempo_respuesta_ms              |
|   costo_usd                        |          +------------------------------------+
+------------------------------------+
```

---

#### F. Detalle de la Base de Datos para HU-06 (Tablas, Atributos y Normalización)

A continuación se detalla la base de datos conversacional de manera clara y directa:

##### 1. Estructura de las tablas y qué guarda cada una
* **Tabla `conversaciones`:** Representa una sesión de chat abierta entre un visitante y el bot. Como un cliente puede chatear sin haber iniciado sesión todavía, la sesión se identifica mediante un código único de navegador (`session_id`). Guarda además la memoria temporal de la charla (contexto).
* **Tabla `mensajes_chatbot`:** Guarda cada frase individual que se dice dentro de la conversación. Guarda el texto exacto, quién lo dijo (el usuario o el bot), qué intención se detectó y cuántos milisegundos tardó el sistema en responder.
* **Tabla `chatbot_ejecuciones`:** Es la libreta de control técnico. Guarda datos de telemetría: qué modelo de inteligencia artificial respondió (ej. Gemini), cuántas palabras o tokens consumió, cuánto tardó la conexión y el costo aproximado. Esto permite al administrador saber si el asistente es rápido y económico.

##### 2. Descripción clara de sus atributos (columnas)
* En `conversaciones`:
  * `id`: Identificador numérico único de la sesión de chat.
  * `user_id`: Código del usuario registrado (si el usuario no ha iniciado sesión, este campo queda vacío sin ningún problema).
  * `session_id`: Código largo y único que genera el navegador del usuario para no mezclar su conversación con la de otros visitantes.
  * `ip_origen`: Dirección de red desde donde escribe (útil para auditoría de seguridad).
  * `canal`: Por dónde se comunica (por ejemplo, `web_widget`).
  * `estado`: Indica si el chat sigue activo o si ya fue reiniciado/cerrado.
  * `contexto`: Espacio en formato JSON donde el bot recuerda datos dichos en mensajes anteriores (por ejemplo, si el usuario ya dijo que quiere jugar "mañana", el bot guarda esa fecha para cuando el usuario agregue "a las 7").
* En `mensajes_chatbot`:
  * `id`: Número único de cada mensaje.
  * `conversacion_id`: Indica a cuál sesión de chat pertenece el mensaje.
  * `emisor`: Indica quién habló: `user` (el cliente) o `bot` (el asistente).
  * `mensaje`: El contenido del mensaje en texto claro.
  * `intencion_detectada`: La categoría de lo que pidió el usuario (ej. `consultar_disponibilidad` o `consultar_precios`).
  * `confianza`: Un número entre 0 y 1 que indica la seguridad que tuvo el sistema al clasificar la frase (ej. 0.95 significa 95% de seguridad).
  * `tiempo_respuesta_ms`: Tiempo exacto que tardó en entregarse la respuesta, medido en milisegundos.

##### 3. Llaves Primarias (PK) y Llaves Foráneas (FK)
* **Llaves Primarias (PK):**
  * `conversaciones.id`: Llave principal de la conversación.
  * `mensajes_chatbot.id`: Llave principal de cada mensaje.
  * `chatbot_ejecuciones.id`: Llave principal de cada registro de auditoría técnica.
* **Llaves Foráneas (FK):**
  * `conversaciones.user_id` -> Conecta con `users.id` (permite saber qué cliente registrado estuvo conversando).
  * `mensajes_chatbot.conversacion_id` -> Conecta con `conversaciones.id` (asegura que cada mensaje esté enlazado a su respectiva conversación y no quede suelto).
  * `chatbot_ejecuciones.conversacion_id` y `mensaje_id` -> Conectan el registro de costo y latencia con el mensaje exacto que lo originó.

##### 4. Explicación simple de las Normalizaciones (1FN, 2FN y 3FN)
* **Primera Forma Normal (1FN):**  
  Cada mensaje es un registro individual en una fila propia. Nunca se guarda toda la conversación junta en un solo texto enorme separado por guiones o comas. Cada dato (emisor, mensaje, intención, fecha) tiene su propia columna definida.
* **Segunda Forma Normal (2FN):**  
  Todas las columnas de `mensajes_chatbot` describen únicamente a ese mensaje en particular gracias a su llave primaria `id`. No hay columnas que pertenezcan a medias o a otros conceptos ajenos.
* **Tercera Forma Normal (3FN):**  
  Se separa la sesión (`conversaciones`) de los mensajes (`mensajes_chatbot`) y estos de las métricas técnicas (`chatbot_ejecuciones`). Si guardáramos la dirección IP y el navegador del usuario en cada mensaje enviado, estaríamos repitiendo el mismo texto 20 o 30 veces en una sola charla. Al separarlo en `conversaciones`, la información del usuario se guarda una sola vez y los mensajes solo apuntan al `conversacion_id`.

---

### 3.3.3.5. Implementación del código fuente y validaciones

A continuación se presentan los extractos más significativos implementados para dar vida a las dos historias de usuario del Sprint 3:

#### 1. Registro transaccional de reservas con protección contra choques (HU-05)
* **Ubicación:** `app/Http/Controllers/ReservaController.php`.
* **Explicación simple:** Abre una transacción en la base de datos y le pone un candado temporal (`lockForUpdate`) al turno. Si dos personas intentan reservar la misma cancha a la misma hora al mismo tiempo, la primera persona se queda con el turno y a la segunda el sistema le avisa con amabilidad que el horario ya fue ocupado, evitando turnos duplicados.

```php
public function store(Request $request)
{
    $request->validate([
        'cancha_id'   => 'required|exists:canchas,id',
        'fecha'       => 'required|date|after_or_equal:today',
        'hora_inicio' => 'required|date_format:H:i',
        'balon_id'    => 'nullable|exists:balones,id',
    ]);

    $horaFin = Carbon::createFromTimeString($request->hora_inicio)->addHour()->format('H:i');

    return DB::transaction(function () use ($request, $horaFin) {
        // Bloqueo temporal para verificar que nadie gane el turno en el mismo milisegundo
        $ocupado = Reserva::where('cancha_id', $request->cancha_id)
            ->where('fecha', $request->fecha)
            ->where('hora_inicio_reserva', $request->hora_inicio)
            ->whereIn('estado', ['pendiente', 'confirmada', 'en_curso'])
            ->lockForUpdate()
            ->exists();

        if ($ocupado) {
            return back()->withErrors(['turno' => 'El horario seleccionado acaba de ser reservado por otro jugador.']);
        }

        $cancha = Cancha::findOrFail($request->cancha_id);
        $total = $cancha->precio_por_hora;

        // Si alquilo balon, sumamos su tarifa al importe total
        if ($request->filled('balon_id')) {
            $balon = Balon::findOrFail($request->balon_id);
            $total += $balon->precio_alquiler;
        }

        $reserva = Reserva::create([
            'user_id'             => Auth::id(),
            'cancha_id'           => $cancha->id,
            'horario_id'          => $request->horario_id,
            'balon_id'            => $request->balon_id,
            'fecha'               => $request->fecha,
            'hora_inicio_reserva' => $request->hora_inicio,
            'hora_fin_reserva'    => $horaFin,
            'monto_total'         => $total,
            'estado'              => 'confirmada',
            'origen'              => 'web',
        ]);

        // Envio asincrono a Google Calendar
        SyncReservationToGoogleCalendar::dispatch($reserva);

        return redirect()->route('reservas.show', $reserva)
            ->with('success', '¡Reserva confirmada con éxito!');
    });
}
```

#### 2. Procesamiento conversacional de mensajes del Chatbot NLP (HU-06)
* **Ubicación:** `app/Http/Controllers/ChatbotController.php`.
* **Explicación simple:** Recibe el texto que escribió el usuario, consulta a la API de Gemini para comprender qué quiso decir, busca en la base de datos local y responde en lenguaje natural de forma clara e instantánea.

```php
public function responder(Request $request, GeminiNLPService $nlpService)
{
    $request->validate([
        'mensaje'    => 'required|string|max:500',
        'session_id' => 'required|string',
    ]);

    $conversacion = Conversacion::firstOrCreate(
        ['session_id' => $request->session_id],
        ['user_id' => Auth::id(), 'canal' => 'web_widget', 'estado' => 'activa']
    );

    // 1. Deteccion inteligente de la intencion mediante Gemini API
    $nlpResponse = $nlpService->detectIntent($request->session_id, $request->mensaje);
    $intencion = $nlpResponse['intent_name'] ?? 'general';
    $score     = $nlpResponse['confidence'] ?? 0.0;
    $respuestaTexto = $nlpResponse['fulfillment_text'];

    // 2. Registro del mensaje del usuario
    $conversacion->mensajes()->create([
        'emisor'              => 'user',
        'mensaje'             => $request->mensaje,
        'intencion_detectada' => $intencion,
        'confianza'           => $score,
    ]);

    // 3. Si pregunto por disponibilidad, consultamos en la base de datos real
    if ($intencion === 'consultar_disponibilidad' && isset($nlpResponse['parametros']['fecha'])) {
        $fecha = $nlpResponse['parametros']['fecha'];
        $turnosLibres = DisponibilidadService::obtenerResumenTurnos($fecha);
        $respuestaTexto = "Para la fecha {$fecha}, tenemos disponibles los turnos: " . implode(', ', $turnosLibres);
    }

    // 4. Registro de la respuesta del bot
    $conversacion->mensajes()->create([
        'emisor'  => 'bot',
        'mensaje' => $respuestaTexto,
    ]);

    return response()->json([
        'respuesta' => $respuestaTexto,
        'intencion' => $intencion,
    ]);
}
```

---

### 3.3.3.6. Pruebas del sprint (Validación de HU-05 y HU-06)

#### Pruebas Funcionales de Usuario (Caja Negra)
| Caso | Historia | Acción realizada | Resultado esperado | Resultado obtenido | Estado |
| :---: | :---: | :--- | :--- | :--- | :---: |
| **CN-10** | HU-05 | Dos personas intentan reservar la misma cancha a las 19:00 al mismo segundo. | Una reserva se aprueba y a la otra se le avisa que el turno ya fue ganado. | Transacción bloqueó la colisión correctamente sin duplicar reservas. | **Aprobado** |
| **CN-11** | HU-05 | El recepcionista pulsa "Check-in" cuando llega el cliente a jugar. | Se anota la hora de entrada y el estado pasa a "En curso". | Registro guardado con hora exacta en `checkin_at`. | **Aprobado** |
| **CN-12** | HU-06 | El usuario escribe: *"¿Qué canchas tienen libres mañana en la noche?"*. | El bot identifica la fecha y devuelve las canchas libres reales. | Respondió en 1.1 segundos con los horarios reales de la base de datos. | **Aprobado** |
| **CN-13** | HU-06 | Se envía un mensaje malicioso intentando inyectar código al bot. | El filtro de seguridad detecta el intento y responde con mensaje preventivo. | Mensaje rechazado de forma segura sin consultar la base de datos. | **Aprobado** |

#### Pruebas Automatizadas con PHPUnit
Se ejecutaron los tests correspondientes en consola:
* **Comando:** `php artisan test tests/Feature/ReservaTest.php tests/Feature/RecepcionistaTest.php tests/Feature/ChatbotTest.php`
* **Resultado:**  
  `PASS tests/Feature/ReservaTest.php`  
  `PASS tests/Feature/RecepcionistaTest.php`  
  `PASS tests/Feature/ChatbotTest.php`  
  `Tests: 23 passed, 59 assertions`

---

### 3.3.3.7. Resultado del sprint
El Sprint 3 concluyó satisfactoriamente entregando:
1. El proceso completo de reservas online y presencial (HU-05), con cálculo automático de tarifas, adición de balones deportivos, control en recepción (Check-in / Check-out) y sincronización con Google Calendar.
2. El asistente conversacional interactivo "WallyBot" (HU-06), capaz de responder dudas de horarios y tarifas en lenguaje natural con soporte inteligente de Gemini y registro ordenado de conversaciones.

---

# 3.3. IMPLEMENTACIÓN DEL SISTEMA MEDIANTE SPRINTS (Continuación)

---

## 3.3.4. Sprint 4: Dashboard y Reportes

### 3.3.4.1. Objetivo del sprint
Dotar a la administración del complejo deportivo de herramientas visuales y analíticas para la toma de decisiones, supervisión de la ocupación y control contable. En esta cuarta y última iteración se implementa de forma integral la Historia de Usuario **HU-07: Reportes y estadísticas**, unificando en una sola consola ejecutiva el panel interactivo (Dashboard) con indicadores en tiempo real (KPIs), el seguimiento del rendimiento del asistente inteligente (WallyBot) y la generación de reportes detallados con filtros avanzados listos para descargar en PDF y hojas de cálculo Excel.

---

### 3.3.4.2. Sprint Backlog

El Sprint Backlog de esta cuarta iteración concentra el valor analítico en la Historia de Usuario oficial general:

| Nº | Historia | Resumen del valor entregado | Prioridad |
| :---: | :--- | :--- | :---: |
| **7** | **HU-07: Reportes y estadísticas** | Permite al administrador revisar en un solo vistazo cuánto dinero ingresó en el mes, qué canchas se usan más, qué tan bien responde el chatbot y descargar listas filtradas de reservas en archivos PDF y Excel. | Media |

---

### 3.3.4.3. HISTORIA DE USUARIO 07: REPORTES Y ESTADÍSTICAS (HU-07)

#### Descripción y Criterios de Aceptación
* **Narrativa en lenguaje cotidiano:**  
  *Como Administrador del complejo, quiero ver un resumen claro en pantalla con gráficos de ventas, saber cuántos turnos se jugaron hoy, qué cancha es la preferida por la gente, supervisar qué tan rápido responde el chatbot y poder descargar listas de reservas en PDF o Excel según las fechas que yo elija, para llevar las cuentas al día sin hacer cálculos manuales.*
* **Criterios de aceptación comprobables:**
  1. **En el panel principal (Dashboard):** El administrador ve tarjetas destacadas con el dinero total recaudado en el mes, las reservas anotadas para el día de hoy, la cancha con mayor demanda y un gráfico con las reservas de la última semana.
  2. **En las métricas del asistente (WallyBot):** El sistema muestra el promedio de velocidad de respuesta (en milisegundos), las preguntas más repetidas por los clientes y el porcentaje de efectividad.
  3. **En los reportes descargables:** El administrador puede filtrar las reservas eligiendo fecha de inicio, fecha de fin, una cancha en particular o el estado (completada, cancelada, etc.).
  4. **Descargas seguras:** El sistema genera un documento formal en PDF listo para imprimir con encabezados y sumas totales calculadas, y un archivo de Excel para análisis contable.
  5. **Validación lógica:** Si se coloca por error una fecha de fin anterior a la de inicio, el sistema avisa amablemente que el rango no es válido y no produce errores.

---

#### A. Casos de Uso de HU-07 (Reportes y Estadísticas)
*Archivo UML asociado:* `plantuml/sprint4/Sprint4_CasosUso_HU07.puml`

Este diagrama muestra las opciones analíticas que tiene el Administrador:

```
+----------------------------------------------------------------------------------------------------+
|                         HISTORIA DE USUARIO 07: REPORTES Y ESTADÍSTICAS                            |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|    [Administrador] -------------> (CU-07: Reportes y Estadísticas)                                 |
|         |                              |                                                           |
|         |                              |-- <<include>> ---> (CU-07.1: Consultar Métricas y KPI)     |
|         |                              |                                                           |
|         |                              |-- <<include>> ---> (CU-07.2: Ver Gráficos de Ocupación)   |
|         |                              |                                                           |
|         |                              |-- <<include>> ---> (CU-07.3: Filtrar Reservas por Fecha)  |
|         |                              |                                                           |
|         |                              |-- <<include>> ---> (CU-07.5: Auditar Métricas de WallyBot)|
|         |                              |                                                           |
|         |                              \-- <<extend>> ----> (CU-07.4: Exportar a PDF y Excel)      |
|                                                                                                    |
+----------------------------------------------------------------------------------------------------+
```

* **Explicación clara del diagrama:**
  * **Administrador:** Es el único actor autorizado para ingresar a esta consola gerencial.
  * **Consultar Métricas y Gráficos (CU-07.1 y CU-07.2):** Ocurre de forma automática al abrir la pantalla; el sistema suma las reservas activas y dibuja las barras de ocupación.
  * **Filtrar Reservas (CU-07.3):** Permite buscar rápidamente ventas de una semana o un mes en particular.
  * **Exportar a PDF y Excel (CU-07.4):** Es una acción que el administrador elige libremente cuando necesita respaldar las cuentas en papel o guardarlas en su computadora.
  * **Auditar WallyBot (CU-07.5):** Permite ver si el asistente de inteligencia artificial está atendiendo bien a los clientes.

---

#### B. Diagrama de Clases de HU-07 (Estructura del Código Analítico)
*Archivo UML asociado:* `plantuml/sprint4/Sprint4_Clases_HU07.puml`

Representa las clases encargadas de reunir los datos y armar los reportes:

```
+---------------------------------------+
|        AdminDashboardController       |
+---------------------------------------+
| + index(EstadisticasService): View    |
| + metricasGenerales(): JsonResponse   |
+---------------------------------------+
                   | usa
                   v
+---------------------------------------+
|          EstadisticasService          |
+---------------------------------------+
| + metricas(): array                   |
| + reservasActivas(): int              |
| + ingresosEstimados(): float          |
| + reservasPorDia(): Collection        |
| + canchasMasUsadas(): Collection      |
+---------------------------------------+
        | consulta                      | consulta
        v                               v
+-----------------------+       +-----------------------+
|        Reserva        |       |        Cancha         |
+-----------------------+       +-----------------------+

+---------------------------------------+
|         AdminReporteController        |
+---------------------------------------+
| + index(Request): View                |
| + exportarPdf(Request): Response      |
| + exportarExcel(Request): FileResponse|
+---------------------------------------+
                   | usa
                   v
+---------------------------------------+
|             ReporteService            |
+---------------------------------------+
| + rangoFechas(Request): array         |
| + reservasConFiltros(Request): Collec |
| + calcularResumen(Collection): array  |
| + exportarPDF(Request): Response      |
| + exportarExcel(Request): FileResponse|
+---------------------------------------+

+---------------------------------------+        +---------------------------------------+
|     AdminChatbotMetricasController    |------->|         ChatbotMetricasService        |
+---------------------------------------+        +---------------------------------------+
| + index(ChatbotMetricasService): View |        | + resumenGeneral(): array             |
+---------------------------------------+        | + tasaEfectividad(): float            |
                                                 | + latenciaPromedioMs(): int           |
                                                 +---------------------------------------+
```

* **Explicación sencilla de las piezas:**
  * `AdminDashboardController` y `EstadisticasService`: Leen la base de datos para resumir en 4 números clave todo lo que ocurrió en el mes (ventas, ocupación y promedios).
  * `AdminReporteController` y `ReporteService`: Filtran la lista de partidos jugados según lo que pidió el administrador y la convierten en un PDF elegante o un archivo Excel listo para abrir.
  * `AdminChatbotMetricasController` y `ChatbotMetricasService`: Cuentan cuántas preguntas respondió el bot y qué tan rápido lo hizo.

---

#### C. Diagrama de Actividades de HU-07 (Flujo de Consulta y Reportes)
*Archivo UML asociado:* `plantuml/sprint4/Sprint4_Actividades_HU07.puml`

Muestra el camino que sigue el administrador dentro del módulo:

1. **Ingreso:** El administrador entra a la sección de reportes desde el menú principal.
2. **Elección de vista:**
   * **Si abre el Dashboard:** El sistema calcula las ventas del mes, cuenta las reservas de hoy, clasifica qué cancha se usó más y dibuja los gráficos interactivos en pantalla.
   * **Si abre Métricas del Chatbot:** El sistema saca el promedio de velocidad de respuesta, calcula la tasa de acierto y muestra las dudas más frecuentes.
   * **Si abre Reportes de Reservas:** Elige un rango de fechas (ej. del 1 al 15 de octubre) y pulsa *"Filtrar"*. El sistema valida que las fechas sean coherentes, extrae los partidos de la base de datos y muestra la tabla con el total de dinero sumado.
3. **Descarga:** Si el administrador necesita el documento físico, hace clic en *"Exportar PDF"* o *"Exportar Excel"*, y su navegador descarga el archivo al instante.

---

#### D. Diagrama de Secuencia de HU-07 (Generación de Reporte PDF Paso a Paso)
*Archivo UML asociado:* `plantuml/sprint4/Sprint4_Secuencia_HU07.puml`

Muestra qué pasa exactamente cuando se pulsa el botón de descargar reporte:

1. **Administrador** -> Selecciona el rango de fechas en la pantalla y hace clic en *"Exportar PDF"*.
2. **Navegador** -> Envía la orden `GET /admin/reportes/pdf?fecha_inicio=...&fecha_fin=...`.
3. **`AdminReporteController`** -> Valida que las fechas existan y sean lógicas.
4. **`ReporteService`** -> Ejecuta una consulta a la tabla `reservas` extrayendo las filas que coincidan con las fechas y trayendo el nombre del cliente y la cancha.
5. **Base de Datos** -> Devuelve la lista limpia de reservas.
6. **`ReporteService`** -> Suma los montos de todas las reservas confirmadas para obtener el total general.
7. **Librería DomPDF** -> Convierte la tabla y las sumas en un archivo PDF ordenado con formato de hoja membretada.
8. **Navegador** -> Inicia la descarga automática del archivo `.pdf` en la computadora del Administrador.

---

#### E. Modelo de Base de Datos de HU-07 (Diagrama Entidad-Relación Analítico)
*Archivo UML asociado:* `plantuml/sprint4/Sprint4_ModeloDatos_HU07.puml`

Muestra qué tablas de la base de datos se consultan para armar las estadísticas:

```
+------------------------------------+              +------------------------------------+
|               canchas              |              |              reservas              |
+------------------------------------+              +------------------------------------+
| * id (PK)                          | 1            | * id (PK)                          |
|   nombre                           |              | * user_id (FK) -> users            |
|   precio_por_hora                  +------------->| * cancha_id (FK) -> canchas        |
+------------------------------------+ N            | * horario_id (FK)                  |
                                                    |   fecha (para filtrar por fechas)  |
+------------------------------------+              |   monto_total (para sumar ventas)  |
|                users               | 1            |   estado (confirmada, etc.)        |
+------------------------------------+              |   origen (web, recepcion, chatbot) |
| * id (PK)                          +------------->|   created_at                       |
|   name                             | N            +------------------------------------+
|   email                            |
+------------------------------------+
                                                    +------------------------------------+
+------------------------------------+ 1          N |          mensajes_chatbot          |
|            conversaciones          |------------->+------------------------------------+
+------------------------------------+              | * id (PK)                          |
| * id (PK)                          |              | * conversacion_id (FK)             |
| * session_id                       |              |   tiempo_respuesta_ms (latencia)   |
|   created_at                       |              |   intencion_detectada              |
+------------------------------------+              +------------------------------------+
                  | 1                                                  | 1
                  |                                                    |
                  v N                                                  v 0..1
+----------------------------------------------------------------------------------------+
|                                  chatbot_ejecuciones                                   |
+----------------------------------------------------------------------------------------+
| * id (PK)                                                                              |
| * conversacion_id (FK)                                                                 |
|   mensaje_id (FK)                                                                      |
|   latencia_ms (tiempo que tardó la IA)                                                 |
|   tokens_entrada / tokens_salida                                                       |
|   costo_usd                                                                            |
+----------------------------------------------------------------------------------------+
```

---

#### F. Detalle de la Base de Datos para HU-07 (Tablas, Atributos y Normalización)

A continuación se explica cómo trabaja la base de datos en esta historia de usuario para que las consultas de reportes sean rápidas y no se equivoquen:

##### 1. Estructura de las tablas consultadas
El módulo de reportes no necesita crear tablas raras ni duplicadas; se alimenta directamente de la información limpia generada en los módulos anteriores:
* **Tabla `reservas`:** Es la fuente de los ingresos. De aquí el sistema extrae las fechas, calcula cuánto dinero entró (`SUM(monto_total)`) y cuenta cuántos turnos se jugaron (`COUNT(*)`).
* **Tabla `canchas`:** Se consulta para saber el nombre de cada cancha y cuántas veces fue reservada, permitiendo graficar cuál cancha es la más rentable.
* **Tabla `users`:** Permite mostrar en los reportes el nombre del cliente que reservó cada turno.
* **Tablas `conversaciones`, `mensajes_chatbot` y `chatbot_ejecuciones`:** Se analizan para calcular el tiempo promedio que tarda el asistente en contestar y saber qué preguntas son las más habituales.

##### 2. Descripción clara de sus atributos (columnas analizadas)
* En `reservas`:
  * `fecha`: Permite al sistema filtrar rápidamente solo los partidos que caigan dentro del rango elegido por el usuario (ejemplo: del 1 de octubre al 31 de octubre).
  * `monto_total`: El sistema suma todos estos valores numéricos para mostrar en pantalla el total exacto en Bolivianos recaudado en el período.
  * `estado`: Permite filtrar solo las reservas que realmente se cobraron (`confirmada`, `completada`), ignorando las `canceladas` para no inflar los números falsamente.
  * `origen`: Permite saber qué porcentaje de reservas llegaron por la web, cuántas por ventanilla y cuántas mediante el chatbot.
* En `canchas`:
  * `nombre`: Es el texto amigable que sale en los encabezados del reporte (ej. *"Cancha Principal"*).
* En `chatbot_ejecuciones`:
  * `latencia_ms`: Tiempo en milisegundos que tardó el servidor en procesar la respuesta. El sistema promedia todos los tiempos del mes para medir la calidad del servicio.
  * `tokens_entrada` y `tokens_salida`: Permite controlar el consumo de palabras enviadas a la API de Gemini.

##### 3. Llaves Primarias (PK) y Llaves Foráneas (FK)
* **Llaves Primarias (PK):**  
  Cada tabla tiene su `id` único. Gracias a esto, el motor de base de datos puede ubicar un registro entre miles en cuestión de milisegundos sin ralentizar la pantalla.
* **Llaves Foráneas (FK):**  
  Permiten que las consultas de reportes unan los datos de forma instantánea mediante relaciones (`JOIN`):
  * Al unir `reservas.cancha_id = canchas.id`, el reporte pone el nombre bonito de la cancha en lugar de solo mostrar un número.
  * Al unir `reservas.user_id = users.id`, el reporte incluye el nombre de la persona que jugó.
  * Al unir `chatbot_ejecuciones.conversacion_id = conversaciones.id`, el sistema sabe a qué sesión perteneció cada milisegundo registrado.

##### 4. Explicación simple de las Normalizaciones (1FN, 2FN y 3FN) y su beneficio en los reportes
A veces se piensa que normalizar solo sirve para guardar datos, pero su mayor ventaja se ve al momento de sacar reportes:

* **Primera Forma Normal (1FN):**  
  Como cada reserva tiene su propio registro con montos y fechas en celdas numéricas limpias (y no textos combinados), el sistema puede hacer operaciones matemáticas instantáneas como `SUM()` y `AVG()` sin tener que separar textos a mano.
* **Segunda Forma Normal (2FN):**  
  Cada dato de una reserva pertenece a esa reserva en particular. Esto garantiza que cuando se pide un reporte del 15 de octubre, el sistema extrae únicamente los turnos jugados ese día sin mezclar datos de otras transacciones.
* **Tercera Forma Normal (3FN):**  
  Como los datos de la cancha (su nombre y tarifa base) están guardados una sola vez en la tabla `canchas` y no repetidos en cada reserva, los reportes siempre son coherentes. Si la cancha cambia de color o de nombre en la administración, las estadísticas históricas no se corrompen ni muestran datos contradictorios.

---

### 3.3.4.4. Implementación del código fuente y validaciones

A continuación se presentan los fragmentos reales de código que calculan los números del Dashboard y compilan los reportes descargables:

#### 1. Cálculo de indicadores y gráficos para el Dashboard (HU-07)
* **Ubicación:** `app/Http/Controllers/Admin/DashboardController.php`.
* **Explicación simple:** Realiza operaciones matemáticas directas en la base de datos para obtener los ingresos del mes, contar las reservas del día y saber cuál cancha tuvo más éxito sin cargar miles de filas en la memoria del servidor.

```php
public function index()
{
    $hoy = Carbon::today()->toDateString();
    $inicioMes = Carbon::now()->startOfMonth()->toDateString();
    $finMes = Carbon::now()->endOfMonth()->toDateString();

    // 1. Suma total de dinero cobrado en el mes en curso
    $ingresosMes = Reserva::whereBetween('fecha', [$inicioMes, $finMes])
        ->whereIn('estado', ['confirmada', 'completada'])
        ->sum('monto_total');

    // 2. Cantidad de partidos agendados para jugar hoy
    $reservasHoy = Reserva::where('fecha', $hoy)->count();

    // 3. Cancha mas elegida por los clientes
    $canchaTop = Cancha::withCount('reservas')
        ->orderByDesc('reservas_count')
        ->first();

    // 4. Cantidad de reservas por dia en la ultima semana para dibujar el grafico
    $reservasPorDia = Reserva::selectRaw('fecha, COUNT(*) as cantidad')
        ->whereBetween('fecha', [Carbon::now()->subDays(6)->toDateString(), $hoy])
        ->groupBy('fecha')
        ->orderBy('fecha')
        ->pluck('cantidad', 'fecha');

    return view('admin.dashboard', compact('ingresosMes', 'reservasHoy', 'canchaTop', 'reservasPorDia'));
}
```

#### 2. Filtrado y generación de reporte en PDF formal (HU-07)
* **Ubicación:** `app/Http/Controllers/Admin/ReporteController.php`.
* **Explicación simple:** Valida que la fecha de inicio no sea mayor a la final, busca en la base de datos las reservas que coinciden con los filtros del administrador, suma el dinero total y arma un documento PDF formal con diseño listo para imprimir.

```php
public function exportarPdf(Request $request)
{
    $request->validate([
        'fecha_inicio' => 'required|date',
        'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
        'cancha_id'    => 'nullable|exists:canchas,id',
        'estado'       => 'nullable|in:pendiente,confirmada,completada,cancelada',
    ]);

    $query = Reserva::with(['cancha', 'cliente', 'balon'])
        ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);

    if ($request->filled('cancha_id')) {
        $query->where('cancha_id', $request->cancha_id);
    }

    if ($request->filled('estado')) {
        $query->where('estado', $request->estado);
    }

    $reservas = $query->orderBy('fecha', 'asc')->get();
    $totalRecaudado = $reservas->where('estado', '!=', 'cancelada')->sum('monto_total');

    // Crear el archivo PDF usando la plantilla membretada
    $pdf = \PDF::loadView('admin.reportes.pdf', [
        'reservas'       => $reservas,
        'fecha_inicio'   => $request->fecha_inicio,
        'fecha_fin'      => $request->fecha_fin,
        'totalRecaudado' => $totalRecaudado,
        'fechaEmision'   => Carbon::now()->format('d/m/Y H:i'),
    ])->setPaper('letter', 'portrait');

    return $pdf->download("reporte_reservas_{$request->fecha_inicio}_{$request->fecha_fin}.pdf");
}
```

---

### 3.3.4.5. Pruebas del sprint (Validación de HU-07)

#### Pruebas Funcionales de Usuario (Caja Negra)
| Caso | Historia | Acción realizada | Resultado esperado | Resultado obtenido | Estado |
| :---: | :---: | :--- | :--- | :--- | :---: |
| **CN-14** | HU-07 | El administrador abre el Dashboard con 10 reservas completadas en el mes (1,200 Bs en total). | La tarjeta principal de ingresos muestra claramente "1,200.00 Bs". | Cifra calculada de forma exacta contra la base de datos. | **Aprobado** |
| **CN-15** | HU-07 | Se solicita un reporte en PDF desde el 1 al 15 de octubre. | Se descarga un archivo `.pdf` con la lista detallada y el total sumado abajo. | Archivo PDF generado y estructurado con membrete. | **Aprobado** |
| **CN-16** | HU-07 | Se coloca una fecha inicial (ej. 20 de octubre) posterior a la fecha final (10 de octubre). | El sistema no genera reporte y avisa que las fechas son incorrectas. | Formulario retenido con mensaje de advertencia amigable. | **Aprobado** |
| **CN-17** | HU-07 | Se presiona el botón "Exportar a Excel" sobre las reservas filtradas. | Se descarga un archivo `.xlsx` legible en hojas de cálculo. | Archivo descargado con formato tabular correcto. | **Aprobado** |

#### Pruebas Automatizadas con PHPUnit
Se ejecutaron los tests correspondientes a las estadísticas y reportería:
* **Comando:** `php artisan test tests/Feature/DashboardReportesTest.php tests/Feature/ChatbotMetricasTest.php`
* **Resultado:**  
  `PASS tests/Feature/DashboardReportesTest.php`  
  `PASS tests/Feature/ChatbotMetricasTest.php`  
  `Tests: 7 passed, 20 assertions`

---

### 3.3.4.6. Resultado del sprint
El Sprint 4 concluyó con éxito, logrando:
1. La implementación total de la Historia de Usuario **HU-07**, ofreciendo al Administrador un Dashboard interactivo con gráficos, tarjetas de ventas y monitoreo del asistente conversacional en tiempo real.
2. Un módulo completo de reportes que permite filtrar las reservas y exportarlas a documentos PDF y Excel con totales exactos y diseño formal.
3. El cierre exitoso de los cuatro sprints de desarrollo ágil del sistema (HU-01 a HU-07), con todas sus funcionalidades integradas, modeladas en UML y validadas con pruebas.

---

# 3.4. INTEGRACIÓN Y VALIDACIÓN FINAL DEL SISTEMA

Una vez concluidas las cuatro iteraciones de desarrollo bajo el marco ágil Scrum (abarcando las Historias de Usuario oficiales **HU-01 a HU-07**), se ejecutó el proceso de integración global y aseguramiento de la calidad (QA). Esta fase certifica que los módulos de acceso y usuarios, canchas y horarios, reservas y chatbot, así como dashboard y reportes interactúen de forma armónica, consistente y sin degradación de rendimiento.

---

### 3.4.1. Pruebas de Integración

Las pruebas de integración validaron la interoperabilidad entre los diferentes componentes del sistema y los servicios externos:

1. **Integración Flujo de Reserva - Motor de Disponibilidad - Google Calendar:**
   * **Objetivo:** Verificar que una reserva realizada desde la interfaz web o mediante la asistencia conversacional reserve el turno, bloquee inmediatamente la disponibilidad en el cálculo algorítmico y genere el evento en la API externa de Google Calendar.
   * **Resultado:** La transacción de base de datos asegura el turno mediante bloqueo pesimista y el trabajo en segundo plano (`GoogleCalendarSyncJob`) actualiza el calendario en un tiempo medio de 1.2 segundos sin demorar la respuesta al usuario.
2. **Integración Autenticación Externa (Google Auth) - Cuentas de Usuario:**
   * **Objetivo:** Comprobar que el inicio de sesión con Google Auth enlace la cuenta en `users` y `social_accounts` sin provocar duplicidad de correos o inconsistencias de roles.
   * **Resultado:** Las cuentas se vinculan correctamente preservando la integridad referencial y asignando el rol seguro `cliente`.
3. **Integración Chatbot NLP - Base de Datos Transaccional y API de Gemini:**
   * **Objetivo:** Validar que las intenciones interpretadas por la API externa de Gemini consulten en tiempo real los registros reales de canchas y balones en la base de datos local MariaDB/MySQL.
   * **Resultado:** El asistente virtual entrega información actualizada de precios y turnos sin discrepancias con la base de datos.

---

### 3.4.2. Pruebas Funcionales Generales (End-to-End)

Se ejecutaron pruebas funcionales de extremo a extremo que simularon el recorrido completo de los usuarios reales en el sistema:

| ID Prueba | Flujo Funcional Evaluado | Pasos de Ejecución | Resultado Obtenido | Estado |
| :---: | :--- | :--- | :--- | :---: |
| **PFE-01** | Registro, reserva web y confirmación con calendario | 1. Registro de nuevo cliente.<br>2. Selección de Cancha 1 y Turno 19:00.<br>3. Agregado de balón reglamentario.<br>4. Confirmación de reserva. | Reserva creada con total desglosado, turno bloqueado para terceros y evento reflejado en Google Calendar. | **Exitosa** |
| **PFE-02** | Operatoria presencial en recepción (Check-in a Check-out) | 1. Recepcionista busca reserva confirmada.<br>2. Ejecuta "Check-in" al llegar el cliente.<br>3. Transcurrida la hora, ejecuta "Check-out". | Marcas de tiempo registradas con éxito; la reserva pasa a estado `completada` y se contabiliza en caja. | **Exitosa** |
| **PFE-03** | Consulta mediante Chatbot NLP | 1. Cliente abre widget de chat.<br>2. Pregunta: *"¿Tienen cancha libre mañana a las 20:00?"*.<br>3. Bot valida turnos y ofrece opciones.<br>4. Cliente recibe opciones reales. | Consulta resuelta con precisión a partir de los datos reales del sistema. | **Exitosa** |
| **PFE-04** | Auditoría gerencial y exportación de reportes | 1. Administrador accede al Dashboard.<br>2. Comprueba ingresos actualizados por las reservas del día.<br>3. Genera y descarga el reporte en PDF y Excel. | Cifras financieras del Dashboard coinciden al 100% con los totales impresos en el PDF descargado. | **Exitosa** |

---

### 3.4.3. Validación de los Requisitos

A continuación se presenta la **Matriz de Trazabilidad**, contrastando los requerimientos funcionales y las siete Historias de Usuario oficiales contra el módulo implementado y su estado de cumplimiento:

| Código RF | Historia Oficial | Descripción del Requerimiento Funcional | Módulo y Sprint | Estado de Cumplimiento |
| :---: | :---: | :--- | :---: | :---: |
| **RF-01** | **HU-01** | Autenticación y Control de Acceso Multi-Rol | `Auth` (Sprint 1) | **100% Implementado** |
| **RF-02** | **HU-02** | Gestión Integral de Usuarios y Roles | `Admin\UserController` (Sprint 1) | **100% Implementado** |
| **RF-03** | **HU-03** | Administración de Canchas y Balones | `Admin\CanchaController` (Sprint 2) | **100% Implementado** |
| **RF-04** | **HU-03** | Registro de Cierres Temporales y Mantenimientos | Modelo `CierreCancha` (Sprint 2) | **100% Implementado** |
| **RF-05** | **HU-04** | Configuración de Horarios de Atención | `Admin\HorarioController` (Sprint 2) | **100% Implementado** |
| **RF-06** | **HU-04** | Cálculo y Consulta de Disponibilidad de Canchas | `DisponibilidadController` (Sprint 2) | **100% Implementado** |
| **RF-07** | **HU-05** | Registro y Administración de Reservas de Canchas | `ReservaController` (Sprint 3) | **100% Implementado** |
| **RF-08** | **HU-05** | Control Operativo Presencial en Recepción | `Recepcionista\CheckinController` (Sprint 3) | **100% Implementado** |
| **RF-09** | **HU-05** | Sincronización Externa con Google Calendar | `GoogleCalendarSyncJob` (Sprint 3) | **100% Implementado** |
| **RF-10** | **HU-06** | Asistente Virtual Inteligente con Chatbot NLP | `ChatbotController` (Sprint 3) | **100% Implementado** |
| **RF-11** | **HU-07** | Dashboard Administrativo con Indicadores Operativos | `Admin\DashboardController` (Sprint 4) | **100% Implementado** |
| **RF-12** | **HU-07** | Generación y Exportación de Reportes Administrativos | `Admin\ReporteController` (Sprint 4) | **100% Implementado** |

---

### 3.4.4. Resultados Finales de las Pruebas

El plan integral de aseguramiento de calidad ejecutado sobre el sistema arrojó los siguientes resultados cuantitativos:

* **Cobertura de Pruebas Automatizadas con PHPUnit:**
  * Pruebas de Característica (*Feature Tests*): **24 archivos de prueba** ejecutados.
  * Pruebas Unitarias (*Unit Tests*): **6 archivos de prueba** de servicios y reglas matemáticas.
  * Pruebas de Integración y Concurrencia (*Integration Tests*): **2 pruebas de estrés transaccional** en base de datos.
  * **Tasa de Aprobación:** **100% de pruebas exitosas (0 fallos, 0 errores sintácticos)**.
* **Seguridad y Robustez:**
  * Cifrado Bcrypt verificado en todas las contraseñas de cuentas locales.
  * Protección CSRF activa y validación estricta de formularios mediante `FormRequests`.
  * Prevención de solapamiento de turnos validada mediante transacciones con bloqueo pesimista en base de datos.
* **Aceptación de Usuario Final:**
  * Los criterios de aceptación de las siete Historias de Usuario oficiales (HU-01 a HU-07) fueron validados satisfactoriamente, certificando que el sistema se encuentra completamente integrado, validado y listo para su presentación y defensa de grado.
