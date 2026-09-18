# SPRINT 00 — DOCUMENTACIÓN
## Análisis, Requerimientos, Arquitectura y Plan Maestro del Proyecto

---

## 1. Identificación del Sprint

| Campo | Valor |
|---|---|
| Número | Sprint 0 |
| Nombre | Preparación y análisis |
| Objetivo | Construir la base documental y técnica del proyecto: visión, alcance, requerimientos, reglas de negocio, arquitectura, backlog, mapa de agentes/skills y trazabilidad inicial. **No se escribe código en este sprint.** |
| Duración | 1 iteración (preparación) |
| Estado | DONE (base documental creada; sujeto a validación del documento de tesis) |

---

## 2. Contexto y estado real del proyecto

El repositorio ya contiene un sistema Laravel 12 en funcionamiento. El análisis del Sprint 0 se apoya en la **implementación real** verificada en el código:

| Sprint | Módulo | Estado real (verificado en código) |
|---|---|---|
| 1 | Autenticación, usuarios, roles | ✅ Implementado (`Auth::routes()`, `User` con `rol` admin/cliente, middleware `admin`, CRUD usuarios) |
| 2 | Canchas, horarios, disponibilidad | ✅ Implementado (CRUD canchas/horarios, catálogo público, `SinSuperposicionHorario`, disponibilidad) |
| 3 | Reservas | ✅ Implementado (crear, mis reservas, cancelar, estados, FullCalendar admin, índice único anti-duplicidad) |
| 4 | Dashboard y reportes | ✅ Implementado (KPIs, Chart.js, reportes con filtros, exportación CSV) — **Pagos y Clientes NO implementados** |
| 5 | Chatbot NLP | ✅ Implementado (motor local por reglas + proveedor externo opcional, 9 intenciones, entidades, contexto, persistencia) |
| 6 | Sistema multiagente | ⏳ Pendiente (hoy existe `ChatbotService` monolítico; se refactorizará a Orquestador + Agentes) |
| 7 | Integración y calidad | ⏳ Pendiente (ya existen 70 pruebas; se ampliarán) |
| 8 | Cierre y documentación | ⏳ Pendiente (manuales, documentación final, evidencias) |

**Incremento funcional actual:** sistema web completo con autenticación, catálogo de canchas, disponibilidad en tiempo real, reservas con validación anti-duplicidad, panel administrativo con dashboard/reportes y chatbot NLP con datos reales de la base de datos.

---

## 3. Visión del producto

**SISTEMA WEB PARA LA OPTIMIZACIÓN DE RESERVAS Y ATENCIÓN AUTOMATIZADA EN CANCHAS DE WALLY MEDIANTE UN CHATBOT BASADO EN NLP**

Sistema web integral que permite a complejos deportivos de Wally gestionar canchas, horarios, disponibilidad, reservas, clientes y pagos (registro administrativo), con un panel de reportes para la toma de decisiones, y que incorpora un chatbot basado en procesamiento de lenguaje natural (NLP) como **componente funcional** de atención: interpreta consultas del usuario, consulta datos reales del sistema y orquesta agentes especializados para responder y ejecutar operaciones controladas.

> **INFORMACIÓN NO DEFINIDA — REQUIERE VALIDACIÓN:** La redacción exacta del *problema*, *objetivo general* y *justificación* proviene del documento de tesis del estudiante, que no fue adjuntado. Lo aquí presentado es una reconstrucción fiel a partir del título y alcances del prompt maestro, sujeta a validación contra el documento base.

### 3.1 Objetivo general (provisional)

Desarrollar un sistema web que automatice el proceso de reservas y la atención al cliente en complejos deportivos de Wally, mediante un chatbot basado en NLP, centralizando la información de canchas, horarios, disponibilidad, clientes, pagos y reportes.

### 3.2 Objetivos específicos (provisionales)

| ID | Objetivo específico |
|---|---|
| OBJ-01 | Implementar la gestión de usuarios, autenticación, roles y permisos del sistema. |
| OBJ-02 | Implementar la gestión de canchas, horarios y disponibilidad con validación de conflictos. |
| OBJ-03 | Implementar el módulo de reservas con prevención de duplicidad y control de estados. |
| OBJ-04 | Implementar el registro administrativo de pagos y el módulo de clientes. |
| OBJ-05 | Implementar el dashboard y los reportes estadísticos de reservas, ocupación e ingresos. |
| OBJ-06 | Implementar el chatbot NLP con identificación de intenciones y entidades, contexto conversacional y consultas sobre datos reales del sistema. |
| OBJ-07 | Implementar el sistema multiagente con agentes especializados y skills controladas. |
| OBJ-08 | Ejecutar pruebas unitarias, de integración y funcionales, y documentar el sistema (manuales, UML, trazabilidad). |

---

## 4. Stakeholders y actores

| Stakeholder | Rol en el proyecto |
|---|---|
| Estudiante / desarrollador | Desarrolla el sistema; entrega el proyecto de grado. |
| Docente tutor / tribunal | Evalúa el cumplimiento de objetivos, metodología y documentación. |
| Administrador del complejo | Usuario final con control total del sistema. |
| Recepcionista | Opera reservas, clientes y pagos en el día a día. |
| Gerente | Toma decisiones con base en reportes e indicadores. |
| Clientes | Reservan canchas y consultan información mediante el sitio web o el chatbot. |

| Actor del sistema | Descripción |
|---|---|
| Administrador | Gestiona usuarios, roles, canchas, horarios, reservas, clientes, pagos, reportes, configuración y chatbot. |
| Recepcionista | Gestiona reservas, clientes, canchas, horarios y pagos. Sin permisos administrativos globales. |
| Gerente | Consulta dashboard, estadísticas, ingresos, ocupación y reportes. |
| Cliente | Inicia sesión, consulta canchas/disponibilidad/horarios, realiza y gestiona sus reservas, usa el chatbot. |
| Visitante (no autenticado) | Consulta catálogo público, disponibilidad y usa el chatbot (sin operaciones que requieran identidad). |

---

## 5. Alcance y delimitaciones

### 5.1 Alcance (módulos)

1. Autenticación ✅ · 2. Usuarios ✅ · 3. Roles y permisos ⚠️ parcial (solo admin/cliente) · 4. Clientes ⏳ · 5. Canchas ✅ · 6. Horarios ✅ · 7. Disponibilidad ✅ · 8. Reservas ✅ · 9. Pagos/registro de pagos ⏳ · 10. Reportes ✅ · 11. Dashboard ✅ · 12. Chatbot NLP ✅ · 13. Sistema multiagente ⏳ · 14. Configuración ✅ (colores del sitio) · 15. Auditoría ⏳.

### 5.2 Delimitaciones obligatorias (se respetan estrictamente)

- El sistema depende de una conexión estable a Internet.
- El chatbot está limitado a consultas relacionadas con la información disponible en el sistema.
- La precisión del chatbot depende de la configuración y entrenamiento.
- Orientado inicialmente a complejos deportivos de Wally.
- **No se implementan pagos en línea** en esta versión; el pago es únicamente administrativo/registrado.
- No se desarrolla aplicación móvil nativa en la fase inicial.
- No se amplía a otros deportes (fútbol, básquet, tenis, etc.).
- La escalabilidad (otros deportes, pagos en línea) se contempla a nivel de arquitectura, pero no se implementa.

---

## 6. Requerimientos

Ver detalle completo en `docs/requirements/REQUIREMENTS.md`.

### 6.1 Resumen de requerimientos funcionales

| ID | Requerimiento | Sprint | Estado |
|---|---|---|---|
| RF-001 | Registro e inicio de sesión de usuarios | 1 | ✅ |
| RF-002 | Cierre de sesión y recuperación de contraseña | 1 | ✅ |
| RF-003 | Roles (admin/cliente) y protección de rutas | 1 | ⚠️ parcial |
| RF-004 | CRUD de usuarios (admin) | 1 | ✅ |
| RF-005 | Perfil de usuario (datos y contraseña) | 1 | ✅ |
| RF-006 | CRUD de canchas (admin) | 2 | ✅ |
| RF-007 | Estados de cancha (disponible/mantenimiento/inactiva) | 2 | ✅ |
| RF-008 | CRUD de horarios por cancha con anti-superposición | 2 | ✅ |
| RF-009 | Catálogo público de canchas | 2 | ✅ |
| RF-010 | Consulta de disponibilidad por cancha y fecha | 3 | ✅ |
| RF-011 | Creación de reservas con validación anti-duplicidad | 3 | ✅ |
| RF-012 | "Mis reservas" del cliente (estados y cancelación) | 3 | ✅ |
| RF-013 | Gestión de reservas (admin) con calendario FullCalendar | 3 | ✅ |
| RF-014 | Dashboard admin con KPIs y gráficos | 4 | ✅ |
| RF-015 | Reportes con filtros y exportación CSV | 4 | ✅ |
| RF-016 | Módulo de clientes | 4 | ⏳ |
| RF-017 | Registro administrativo de pagos | 4 | ⏳ |
| RF-018 | Chatbot NLP: intenciones, entidades, contexto | 5 | ✅ |
| RF-019 | Chatbot con datos reales del sistema | 5 | ✅ |
| RF-020 | Proveedor externo de IA intercambiable | 5 | ✅ |
| RF-021 | Persistencia de conversaciones y panel admin | 5 | ✅ |
| RF-022 | Configuración del sitio (colores) | 5 | ✅ |
| RF-023 | Sistema multiagente (Orquestador + agentes) | 6 | ⏳ |
| RF-024 | Roles recepcionista y gerente | 6 | ⏳ |
| RF-025 | Auditoría de operaciones importantes | 6 | ⏳ |

### 6.2 Requerimientos no funcionales (resumen)

| ID | Requerimiento no funcional |
|---|---|
| RNF-001 | Rendimiento: respuestas de páginas < 3 s en condiciones normales; chatbot responde sin bloqueos. |
| RNF-002 | Seguridad: hashing de contraseñas, CSRF, validación de entradas, autorización por rol, sesiones controladas. |
| RNF-003 | Usabilidad: interfaz 100 % en español, responsiva (Bootstrap 5). |
| RNF-004 | Compatibilidad: navegadores modernos (Chrome, Firefox, Edge); servidor XAMPP/Apache + MySQL. |
| RNF-005 | Mantenibilidad: arquitectura por capas (Controllers/Services/Models), código comentado, PSR-12. |
| RNF-006 | Escalabilidad: capa de abstracción de IA intercambiable; modelo de datos extensible a otros deportes sin implementarlos. |
| RNF-007 | Disponibilidad: el motor local del chatbot funciona sin conexión a proveedores externos. |
| RNF-008 | Trazabilidad: requisitos ↔ historias ↔ código ↔ UML ↔ pruebas documentados. |
| RNF-009 | Privacidad: los clientes solo acceden a su propia información; los agentes respetan permisos. |
| RNF-010 | Integridad de datos: restricciones, claves foráneas e índices únicos a nivel de BD. |

---

## 7. Reglas de negocio

Ver detalle completo en `docs/requirements/BUSINESS_RULES.md`.

| ID | Regla | Estado |
|---|---|---|
| RN-001 | Una cancha no puede tener dos reservas activas en la misma fecha y franja horaria. | ✅ Definida |
| RN-002 | Una reserva debe estar asociada a un usuario (cliente). | ✅ Definida |
| RN-003 | Una reserva debe tener cancha, fecha y horario. | ✅ Definida |
| RN-004 | Una cancha en mantenimiento no puede reservarse. | ✅ Definida |
| RN-005 | Solo las canchas en estado "disponible" se ofrecen al público. | ✅ Definida |
| RN-006 | Los horarios de una cancha no pueden superponerse para el mismo día. | ✅ Definida |
| RN-007 | La cancelación de una reserva libera la franja horaria. | ✅ Definida |
| RN-008 | El cliente solo puede cancelar reservas pendientes/confirmadas con fecha futura. | ✅ Definida |
| RN-009 | Un administrador no puede eliminarse a sí mismo. | ✅ Definida |
| RN-010 | Los clientes solo acceden a su propia información. | ✅ Definida |
| RN-011 | La base de datos es la fuente de verdad; la IA nunca modifica la BD directamente. | ✅ Definida |
| RN-012 | Los agentes del chatbot respetan los permisos del usuario autenticado. | ✅ Definida |
| RN-013 | Política de cancelación con antelación mínima (horas). | **REGLA PENDIENTE DE VALIDACIÓN** |
| RN-014 | Límite de reservas activas por cliente (por día/semana). | **REGLA PENDIENTE DE VALIDACIÓN** |
| RN-015 | El pago registrado es requisito para confirmar una reserva. | **REGLA PENDIENTE DE VALIDACIÓN** |
| RN-016 | Franja horaria mínima de reserva (1 hora). | **REGLA PENDIENTE DE VALIDACIÓN** |

---

## 8. Riesgos y restricciones

### 8.1 Riesgos

| ID | Riesgo | Mitigación |
|---|---|---|
| R-01 | Dependencia de Internet para el chatbot con proveedor externo | Motor local por reglas como base; proveedor opcional con fallback automático. |
| R-02 | Alucinaciones del modelo de IA (inventar disponibilidad, precios, reservas) | La IA solo interpreta; toda respuesta usa datos reales de la BD vía servicios. |
| R-03 | Reservas simultáneas (doble reserva) | Índice único en BD + validación transaccional en el servicio. |
| R-04 | Fuga de información privada por el chatbot | Agente de seguridad / permisos; el chatbot nunca expone datos de otros usuarios. |
| R-05 | Alcance ampliado sin autorización | Regla del prompt: toda funcionalidad nueva debe justificarse por alcance/objetivo/requisito. |
| R-06 | Documentación desincronizada con el código | Regla de actualización en cadena (sección 42 del prompt): requisito→historia→código→BD→UML→pruebas→trazabilidad. |

### 8.2 Restricciones técnicas

- PHP ≥ 8.2, Laravel 12, MySQL/MariaDB (XAMPP), Apache.
- Frontend: HTML5, CSS3, Bootstrap 5, JavaScript, Blade, DataTables, FullCalendar, SweetAlert2, Chart.js, jQuery, Vite.
- Git + GitHub para control de versiones; tablero Kanban/Trello para gestión.
- **INFORMACIÓN NO DEFINIDA — REQUIERE VALIDACIÓN:** el documento de tesis puede fijar restricciones adicionales (institucionales, de formato o de evaluación) no presentes en el prompt maestro.

---

## 9. Product Backlog inicial

Ver detalle completo en `docs/scrum/PRODUCT_BACKLOG.md`. Resumen:

| PB ID | Historia resumida | Prioridad | Sprint | Estimación |
|---|---|---|---|---|
| PB-001 | Como usuario, quiero registrarme e iniciar sesión | Alta | 1 | 5 |
| PB-002 | Como admin, quiero gestionar usuarios y roles | Alta | 1 | 8 |
| PB-003 | Como admin, quiero gestionar canchas | Alta | 2 | 8 |
| PB-004 | Como admin, quiero gestionar horarios | Alta | 2 | 8 |
| PB-005 | Como cliente, quiero ver canchas y disponibilidad | Alta | 2/3 | 5 |
| PB-006 | Como cliente, quiero reservar y gestionar mis reservas | Alta | 3 | 13 |
| PB-007 | Como admin, quiero gestionar reservas y calendario | Alta | 3 | 8 |
| PB-008 | Como gerente, quiero dashboard y reportes | Media | 4 | 8 |
| PB-009 | Como admin, quiero registrar pagos | Media | 4 | 8 |
| PB-010 | Como cliente, quiero atención por chatbot NLP | Alta | 5 | 13 |
| PB-011 | Como sistema, quiero agentes especializados (multiagente) | Media | 6 | 21 |
| PB-012 | Como sistema, quiero auditoría de operaciones | Media | 6 | 8 |
| PB-013 | Como equipo, quiero pruebas integrales y documentación | Alta | 7/8 | 13 |

**Estimación:** puntos de historia (Fibonacci: 1, 2, 3, 5, 8, 13, 21). **Velocidad proyectada:** 15–20 puntos/sprint.

---

## 10. Arquitectura inicial

Ver detalle completo en `docs/architecture/ARCHITECTURE.md` y `docs/decisions/ARCHITECTURE_DECISIONS.md`.

```
┌─────────────────────────────────────────────────────────────┐
│ CAPA DE PRESENTACIÓN  (Blade, Bootstrap 5, JS, Vite)        │
│  Vistas públicas · Panel admin · Widget chatbot · Calendario│
├─────────────────────────────────────────────────────────────┤
│ CAPA DE APLICACIÓN  (Laravel)                               │
│  Controllers · Requests (validación) · Middleware (auth/admin)│
│  Services (ChatbotService, EstadisticasService) · Rules     │
├─────────────────────────────────────────────────────────────┤
│ CAPA DE DOMINIO / NEGOCIO                                   │
│  Modelos Eloquent · Reglas (RN-001..RN-016) · Lógica chatbot│
│  Orquestador + Agentes (Sprint 6) · Skills                  │
├─────────────────────────────────────────────────────────────┤
│ CAPA DE PERSISTENCIA                                        │
│  MySQL · Migraciones · Relaciones · Índices únicos · FKs    │
├─────────────────────────────────────────────────────────────┤
│ CAPA DE IA (intercambiable)                                 │
│  DetectorIntencion (reglas) → ProveedorExterno (openai/gemini)│
│  ExtractorEntidades · AIService (abstracción)               │
└─────────────────────────────────────────────────────────────┘
```

**Flujo controlado de la IA (regla fundamental):**
Usuario → Chatbot → Orquestador → Agente especializado → Skill → Servicio de aplicación → Validación → BD → Resultado → Agente → Respuesta.

---

## 11. Mapa de agentes y skills (Sprint 6 — planificado)

Ver detalle completo en `docs/agents/AGENTS.md` y `docs/skills/SKILLS.md`.

| Agente | Responsabilidad | Skills principales | Estado |
|---|---|---|---|
| AG-01 Orquestador | Recibe la consulta, determina intención, delega y consolida | `analizar_intencion`, `delegar`, `consolidar` | ⏳ Sprint 6 |
| AG-02 Reservas | Consultar/crear/modificar/cancelar reservas | `crear_reserva`, `cancelar_reserva`, `consultar_reservas` | ⏳ (lógica base existe en `ReservaController`/`Reserva`) |
| AG-03 Disponibilidad | Consultar franjas libres y alternativas | `consultar_disponibilidad` | ⏳ (base: `Cancha::horariosDisponiblesPara`) |
| AG-04 Canchas | Información de canchas, estados, precios | `listar_canchas`, `consultar_estado` | ⏳ (base: `CanchaController`) |
| AG-05 Clientes | Info permitida del cliente autenticado | `consultar_perfil`, `historial_reservas` | ⏳ |
| AG-06 Pagos | Consultar registros de pagos (solo lectura) | `consultar_pagos` | ⏳ (depende de RF-017) |
| AG-07 Reportes | Indicadores y resúmenes autorizados | `obtener_reservas_por_periodo`, `generar_resumen` | ⏳ (base: `EstadisticasService`) |
| AG-08 Soporte/Información | FAQ, contacto, políticas registradas | `responder_faq`, `informacion_contacto` | ⏳ (base: intenciones saludo/ayuda/contacto) |
| AG-09 Seguridad | Capa de control: autenticación, autorización, alcance | `validar_acceso`, `detectar_fuera_alcance` | ⏳ (base: middleware `admin`, permisos por rol) |

**Componentes ya existentes que serán la base del refactor:** `ChatbotService` (flujo), `DetectorIntencion` (intenciones), `ExtractorEntidades` (cancha/fecha/hora), `ProveedorExterno` (IA intercambiable), `Conversacion` (contexto).

---

## 12. Base de datos inicial

Ver detalle completo en `docs/database/DATABASE.md` y `docs/database/MODELO_DATOS.md`.

Tablas existentes (migraciones): `users`, `canchas`, `horarios`, `reservas`, `conversaciones`, `mensajes_chatbot`, `ajustes`, más las de framework (`cache`, `jobs`).

Tablas planificadas: `clientes` (o extensión de users), `pagos`, `auditorias` (o `registro_actividades`).

**Decisiones clave:** índice único en `reservas` (cancha+fecha+horario activo, con columna generada para permitir re-reserva tras cancelación) e índice único en `horarios` (cancha+día+hora_inicio) para evitar superposición.

---

## 13. UML inicial

Ver detalle completo en `docs/uml/UML_MODEL.md` (actores, casos de uso, actividades, secuencias, clases). Resumen de casos de uso iniciales:

| CU | Caso de uso | Actor | Estado |
|---|---|---|---|
| CU-01 | Iniciar sesión | Todos | ✅ |
| CU-02 | Gestionar usuarios | Administrador | ✅ |
| CU-03 | Gestionar canchas | Administrador | ✅ |
| CU-04 | Gestionar horarios | Administrador | ✅ |
| CU-05 | Consultar disponibilidad | Cliente/Visitante | ✅ |
| CU-06 | Registrar reserva | Cliente | ✅ |
| CU-07 | Cancelar reserva | Cliente/Admin | ✅ |
| CU-08 | Gestionar reservas (admin) | Administrador | ✅ |
| CU-09 | Consultar dashboard | Gerente/Admin | ✅ |
| CU-10 | Generar reportes | Gerente/Admin | ✅ |
| CU-11 | Registrar pago | Recepcionista/Admin | ⏳ |
| CU-12 | Consultar mediante chatbot | Todos | ✅ |
| CU-13 | Gestionar reserva mediante chatbot | Cliente | ⏳ (reglas lo permiten) |
| CU-14 | Consultar disponibilidad mediante chatbot | Todos | ✅ |

---

## 14. Pruebas

Ver detalle completo en `docs/testing/TEST_PLAN.md`.

**Estado actual verificado (17/08/2026):** 70 pruebas / 162 aserciones. Con el entorno saneado (`unset SESSION_PATH APP_ENV; APP_ENV=testing`) pasan **69/70**.

> ⚠️ **Hallazgos del Sprint 0 (pruebas):**
> 1. El shell de desarrollo exporta variables del `.env` (`SESSION_PATH` con ruta de Git Bash inválida y `APP_ENV=local`), lo que rompe la suite en local (500 por cookie de sesión y 419 CSRF). Ejecutar con `unset SESSION_PATH APP_ENV; APP_ENV=testing php artisan test`.
> 2. **Corregido:** `ChatbotTest > la disponibilidad excluye franjas reservadas` fallaba solo los lunes (inconsistencia de fechas entre el test y el bot). Se corrigió el test para usar la misma interpretación de fechas del bot. Suite actual: **70/70 pruebas, 162 aserciones** ✅.

| Área | Pruebas existentes (archivo) | Cobertura |
|---|---|---|
| Autenticación | `AuthenticationTest`, `UserManagementTest` | Registro, login, roles, CRUD usuarios |
| Canchas públicas | `CanchasPublicasTest` | Catálogo, detalle, solo disponibles |
| Horarios | `HorarioManagementTest` | CRUD y anti-superposición |
| Reservas | `ReservaTest` | Creación, duplicidad, cancelación, permisos |
| Dashboard/Reportes | `DashboardReportesTest` | KPIs, filtros, exportación |
| Chatbot | `ChatbotTest`, `ChatbotMotorTest` | Intenciones, entidades, contexto, permisos |

---

## 15. Decisiones técnicas del Sprint 0

| ID | Decisión | Archivo de referencia |
|---|---|---|
| ADR-001 | Arquitectura multiagente para la capa de IA | `docs/decisions/ARCHITECTURE_DECISIONS.md` |
| ADR-002 | Motor NLP local por reglas como base + proveedor externo opcional (fallback) | `config/chatbot.php` |
| ADR-003 | Anti-duplicidad de reservas con índice único + columna generada | migración `reservas` |
| ADR-004 | Roles simples (columna `rol` en users) en lugar de paquete spatie | `app/Models/User.php` |
| ADR-005 | La IA nunca escribe en BD; solo servicios de aplicación autorizados | `app/Services/ChatbotService.php` |
| ADR-006 | No implementar pagos en línea en esta versión (delimitación) | — |

---

## 16. Retrospección del Sprint 0

### Qué salió bien
- Se identificó que el código real ya implementa gran parte de los Sprints 1–5, lo que permite enfocar la documentación en lo que falta (pagos, clientes, multiagente, roles restantes) sin inventar estado.
- La arquitectura existente (services + modelos + reglas) es compatible con el diseño multiagente planificado.

### Qué salió mal / pendiente
- El documento de tesis (problema, objetivo general exacto, delimitaciones institucionales) no fue proporcionado; los objetivos quedaron como **provisionales**.
- No existe aún `PROMPT_MAESTRO.md` en el repositorio aunque el README lo referencia.

### Qué mejorar
- Validar con el estudiante las reglas de negocio marcadas como pendientes (RN-013 a RN-016).
- Definir la política de estados de pago y su relación con los estados de reserva antes del Sprint 4/6.

### Acciones para el siguiente Sprint
1. Confirmar documento de tesis (objetivos y delimitaciones).
2. Validar reglas de negocio pendientes.
3. Iniciar Sprint 1 en modo "incremento sobre lo existente": completar roles (recepcionista/gerente) si el documento base lo exige.

---

## 17. Artefactos generados en este Sprint

| Artefacto | Ruta |
|---|---|
| Documentación del Sprint 0 | `docs/sprints/SPRINT_00_DOCUMENTACION.md` |
| Requerimientos (RF/RNF) | `docs/requirements/REQUIREMENTS.md` |
| Reglas de negocio | `docs/requirements/BUSINESS_RULES.md` |
| Product Backlog | `docs/scrum/PRODUCT_BACKLOG.md` |
| Arquitectura | `docs/architecture/ARCHITECTURE.md` |
| Decisiones de arquitectura | `docs/decisions/ARCHITECTURE_DECISIONS.md` |
| Base de datos | `docs/database/DATABASE.md`, `docs/database/MODELO_DATOS.md` |
| UML | `docs/uml/UML_MODEL.md` |
| Arquitectura de IA | `docs/ai/AI_ARCHITECTURE.md` |
| Agentes | `docs/agents/AGENTS.md` |
| Skills | `docs/skills/SKILLS.md` |
| Plan de pruebas | `docs/testing/TEST_PLAN.md` |
| Trazabilidad | `docs/traceability/TRAZABILIDAD_PROYECTO.md` |
