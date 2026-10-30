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
