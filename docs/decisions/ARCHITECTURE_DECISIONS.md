# ARCHITECTURE_DECISIONS — Bitácora de Decisiones (ADR)

> Formato: ID · Fecha · Decisión · Problema · Alternativas · Selección · Justificación · Consecuencias (sección 40 del prompt maestro).

---

## ADR-001 — Arquitectura multiagente para el componente de IA

| Campo | Valor |
|---|---|
| Fecha | Sprint 0 |
| Problema | El chatbot necesita manejar consultas de reservas, disponibilidad, canchas, clientes, pagos, reportes y soporte sin que el modelo tenga acceso indiscriminado a la BD. |
| Alternativas | (a) Chatbot monolítico con reglas; (b) Sistema multiagente con agentes especializados y skills; (c) Agente único con herramientas. |
| Selección | (b) Sistema multiagente (Orquestador + 9 agentes especializados). |
| Justificación | Permite separar responsabilidades, controlar permisos por agente, evitar contradicciones y hacer auditable cada operación (sección 5 del prompt maestro). |
| Consecuencias | Se refactoriza `ChatbotService` (Sprint 6); cada agente expone skills con interfaz documentada; mayor estructura, menor acoplamiento. |

## ADR-002 — Motor NLP local por reglas + proveedor externo opcional con fallback

| Campo | Valor |
|---|---|
| Fecha | Sprint 5 (verificado en Sprint 0) |
| Problema | El sistema depende de Internet; si la API de IA falla, el chatbot no debe quedar inoperante. |
| Alternativas | (a) Solo API externa; (b) Solo reglas locales; (c) Reglas locales + proveedor externo opcional con fallback. |
| Selección | (c) `ProveedorExterno::analizar()` propone intención y entidades en una sola respuesta JSON validada; si no está configurado o falla → motor local con el mismo contrato. |
| Justificación | Cumple la delimitación de dependencia de Internet y mantiene la atención operativa sin conexión. |
| Consecuencias | `config/chatbot.php` centraliza la configuración; existe timeout controlado, no hay reintentos y cada mensaje libre consume como máximo una petición externa. Las respuestas operativas se generan en Laravel. |

## ADR-008 — Calendar como sincronización idempotente, no como fuente de verdad

| Campo | Valor |
|---|---|
| Fecha | Fase 2 — 2026-09-11 |
| Problema | Una reserva debe conservar su identidad externa al cambiar, cancelarse o completarse, incluso si posteriormente cambia el calendario predeterminado. |
| Selección | Mantener la relación en `google_calendar_events`, almacenar allí `calendar_id` + `google_event_id` y ejecutar upsert mediante un job sin solapamiento por reserva. |
| Justificación | La BD conserva el estado canónico y Calendar es una proyección externa reconciliable. |
| Consecuencias | Pendientes no crean eventos; canceladas y completadas conservan el mismo evento rotulado; sólo la eliminación física administrativa borra el evento. |

## ADR-009 — Mantener `horario_id` hasta requerir múltiples franjas

| Campo | Valor |
|---|---|
| Fecha | Fases 1–2 — 2026-09-11 |
| Problema | Gemini y Calendar necesitan horas, pero una reserva actual corresponde a una sola franja configurada. |
| Selección | Derivar inicio y fin de `horarios` sin cambiar `reservas`; aplazar una tabla hija de franjas hasta la fase 3. |
| Justificación | `horario_id` representa correctamente las reservas actuales y evita una migración prematura. |
| Consecuencias | Los rangos y la duración se reconocen en NLP, pero no se ejecutan hasta migrar y validar franjas contiguas en la fase 3. |

## ADR-003 — Anti-duplicidad de reservas con índice único + columna generada

| Campo | Valor |
|---|---|
| Fecha | Sprint 3 (verificado en Sprint 0) |
| Problema | Dos clientes podrían reservar la misma cancha/fecha/franja de forma simultánea. |
| Alternativas | (a) Solo validación en controlador; (b) Transacción con lock; (c) Índice único en BD + validación en controlador. |
| Selección | (c) Índice único con columna generada (permite re-reserva tras cancelación) + validación 409 en el servicio. |
| Justificación | La BD garantiza la integridad incluso con concurrencia; la validación mejora la experiencia de usuario. |
| Consecuencias | La franja se libera al cancelar (la columna generada refleja solo reservas activas). |

## ADR-004 — Roles simples (columna `rol` en `users`) en lugar de paquete de permisos

| Campo | Valor |
|---|---|
| Fecha | Sprint 1 (verificado en Sprint 0) |
| Problema | Se requiere control de acceso por roles (admin/cliente; futuro recepcionista/gerente). |
| Alternativas | (a) Paquete spatie/laravel-permission; (b) Columna `rol` + middleware; (c) Tablas role/permission propias. |
| Selección | (b) Columna `rol` + middleware `EnsureUserIsAdmin` (y futuros middleware por rol). |
| Justificación | Suficiente para la granularidad actual (2 roles); evita dependencias externas; extensible añadiendo valores al enum. |
| Consecuencias | Si el documento de tesis exige permisos granulares, se migrará a tablas `roles`/`permissions` (ADR-007 candidato). |

## ADR-005 — La IA nunca escribe en la base de datos

| Campo | Valor |
|---|---|
| Fecha | Sprint 0 (diseño) |
| Problema | Evitar que el modelo alucine o modifique datos (inventar reservas, precios, estados). |
| Alternativas | (a) Acceso directo del modelo a BD; (b) Skills que llaman servicios autorizados. |
| Selección | (b) Toda operación pasa por Servicio → Validación → BD (flujo sección 7). |
| Justificación | La BD es la fuente de verdad; la IA complementa, no reemplaza la lógica empresarial. |
| Consecuencias | Las reservas vía chatbot se guían al sitio web para confirmación (flujo actual) o se ejecutan vía skill autorizada con permisos (Sprint 6). |

## ADR-006 — Sin pagos en línea en la primera versión

| Campo | Valor |
|---|---|
| Fecha | Sprint 0 (delimitación del proyecto) |
| Problema | El alcance no contempla pasarelas de pago. |
| Alternativas | (a) Pago en línea con gateway; (b) Registro administrativo de pagos. |
| Selección | (b) Registro administrativo dentro del sistema (RF-026/RF-027). |
| Justificación | Delimitación explícita del prompt maestro (§37); la arquitectura queda lista para integrar un gateway después. |
| Consecuencias | El módulo de pagos (Sprint 4) solo persiste montos, fechas, comprobantes y estados. |

## ADR-007 (candidato) — Tablas `roles` y `permissions` si el documento de tesis exige permisos granulares

| Campo | Valor |
|---|---|
| Fecha | Sprint 0 — **PENDIENTE DE VALIDACIÓN** |
| Problema | Los roles recepcionista/gerente (RF-007) pueden requerir granularidad fina. |
| Decisión propuesta | Migrar de columna `rol` a tablas `roles`/`permissions` con pivote `role_user` y `permission_role`. |
| Justificación | Solo si el documento base lo exige; mientras tanto, middleware por rol es suficiente. |
