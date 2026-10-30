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
