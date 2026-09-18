# SKILLS — Catálogo de Skills de los Agentes

> Sprint 0. Cada skill se documenta con: nombre, propósito, parámetros, validaciones, permisos requeridos, entradas, salidas, errores posibles, agente responsable, caso de uso, requerimiento e historia de usuario (sección 6 del prompt maestro).
> Estado: ⏳ planificadas para Sprint 6; la mayoría tiene base funcional ya implementada en servicios/modelos.

---

## 1. Formato de especificación de skill

| Campo | Descripción |
|---|---|
| Nombre | Identificador `snake_case` de la skill |
| Propósito | Qué capacidad concreta ejecuta |
| Parámetros | Entrada requerida/opcional |
| Validaciones | Reglas aplicadas antes de ejecutar |
| Permisos | Rol mínimo / condiciones |
| Entrada / Salida | Datos |
| Errores | Posibles fallos |
| Agente | AG-XX responsable |
| Caso de uso / Requerimiento / HU | Trazabilidad |

---

## 2. Skills del AGENTE DE RESERVAS (AG-02)

### SK-01 `consultar_reservas`
| Campo | Valor |
|---|---|
| Propósito | Listar reservas del usuario (o filtradas según rol) |
| Parámetros | `user_id` (opcional según rol), `estado`, `desde`, `hasta` |
| Validaciones | Usuario autenticado; cliente solo ve sus reservas (RN-010) |
| Permisos | Cliente: propias. Admin/Recepcionista: todas |
| Salida | Lista de reservas con cancha, fecha, hora, estado, monto |
| Errores | Sin autenticación; sin reservas |
| Agente | AG-02 |
| CU / RF / HU | CU-12 / RF-031 / HU-16 |

### SK-02 `crear_reserva`
| Campo | Valor |
|---|---|
| Propósito | Crear una reserva validando disponibilidad y reglas |
| Parámetros | `cancha_id`, `fecha`, `horario_id`, `observaciones` |
| Validaciones | RN-001 (anti-duplicidad), RN-004 (no mantenimiento), cancha disponible, usuario autenticado |
| Permisos | Cliente (solo propia) / Admin / Recepcionista |
| Salida | Reserva creada con monto calculado |
| Errores | Conflicto de horario (409), cancha no disponible, fecha pasada |
| Agente | AG-02 |
| CU / RF / HU | CU-06 / RF-014, RF-015 / HU-09 |

### SK-03 `cancelar_reserva`
| Campo | Valor |
|---|---|
| Propósito | Cancelar una reserva y liberar la franja (RN-007) |
| Parámetros | `reserva_id` |
| Validaciones | RN-008 (cliente: solo futuras pendientes/confirmadas); dueño o rol autorizado |
| Permisos | Cliente (propia), Admin, Recepcionista |
| Salida | Reserva cancelada |
| Errores | No cancelable (fecha pasada / estado no permite), no autorizado |
| Agente | AG-02 |
| CU / RF / HU | CU-07 / RF-017 / HU-10 |

### SK-04 `verificar_disponibilidad`
| Campo | Valor |
|---|---|
| Propósito | Validar que una cancha/fecha/franja esté libre antes de reservar |
| Parámetros | `cancha_id`, `fecha`, `horario_id` |
| Validaciones | RN-001, RN-004, RN-005 |
| Permisos | Lectura pública (para consulta) |
| Salida | Booleano + franjas alternativas |
| Errores | Cancha inexistente |
| Agente | AG-02 / AG-03 |
| CU / RF / HU | CU-05 / RF-013 / HU-08 |

### SK-05 `obtener_historial_reservas`
| Campo | Valor |
|---|---|
| Propósito | Historial completo del cliente (incluye canceladas/completadas) |
| Parámetros | `user_id` |
| Validaciones | Solo el dueño o rol autorizado (RN-010) |
| Permisos | Cliente (propio), Admin |
| Salida | Lista histórica con estados |
| Errores | No autorizado |
| Agente | AG-02 / AG-05 |
| CU / RF / HU | CU-12 / RF-016 / HU-16 |

## 3. Skills del AGENTE DE DISPONIBILIDAD (AG-03)

### SK-06 `consultar_disponibilidad`
| Campo | Valor |
|---|---|
| Propósito | Devolver franjas libres de una cancha para una fecha |
| Parámetros | `cancha_id`, `fecha` |
| Validaciones | Cancha en estado disponible (RN-005) |
| Permisos | Lectura pública |
| Salida | Lista de franjas libres (excluye reservadas) |
| Errores | Cancha no disponible / sin horarios registrados |
| Agente | AG-03 |
| CU / RF / HU | CU-05 / RF-013 / HU-08 |

### SK-07 `consultar_alternativas`
| Campo | Valor |
|---|---|
| Propósito | Ofrecer otras fechas/horarios/canchas cuando no hay disponibilidad |
| Parámetros | `cancha_id` (opcional), `fecha` (opcional) |
| Validaciones | — |
| Permisos | Lectura pública |
| Salida | Canchas/franjas alternativas |
| Errores | Sin alternativas |
| Agente | AG-03 |
| CU / RF / HU | CU-05 / RF-013 / HU-08 |

## 4. Skills del AGENTE DE CANCHAS (AG-04)

### SK-08 `listar_canchas`
| Campo | Valor |
|---|---|
| Propósito | Listar canchas del sistema (público: solo disponibles) |
| Parámetros | `estado` (opcional; por rol) |
| Validaciones | RN-005 para público |
| Permisos | Lectura pública / Admin todas |
| Salida | Canchas con nombre, ubicación, capacidad, precio, estado |
| Errores | — |
| Agente | AG-04 |
| CU / RF / HU | CU-03 / RF-008, RF-009 / HU-07 |

### SK-09 `consultar_estado_cancha`
| Campo | Valor |
|---|---|
| Propósito | Distinguir disponible / reservada / mantenimiento / fuera de horario |
| Parámetros | `cancha_id`, `fecha` (opcional) |
| Validaciones | — |
| Permisos | Lectura pública |
| Salida | Estado actual de la cancha |
| Errores | Cancha inexistente |
| Agente | AG-04 |
| CU / RF / HU | CU-03 / RF-009 / HU-07 |

## 5. Skills del AGENTE DE CLIENTES (AG-05)

### SK-10 `consultar_perfil`
| Campo | Valor |
|---|---|
| Propósito | Información permitida del cliente autenticado |
| Parámetros | — (usa sesión) |
| Validaciones | Autenticado; solo datos propios (RN-010, RN-IA-04) |
| Permisos | Cliente (propio) |
| Salida | Nombre, teléfono, email, estado |
| Errores | No autenticado |
| Agente | AG-05 |
| CU / RF / HU | CU-12 / RF-006 / HU-16 |

## 6. Skills del AGENTE DE PAGOS (AG-06) — depende de RF-026

### SK-11 `consultar_pagos`
| Campo | Valor |
|---|---|
| Propósito | Consultar registros de pagos y su estado administrativo (sin pagos en línea) |
| Parámetros | `reserva_id` / `user_id` / rango de fechas |
| Validaciones | Cliente: solo sus pagos; Admin/Recepcionista: según rol |
| Permisos | Cliente (propios), Admin, Recepcionista |
| Salida | Pagos con monto, fecha, método, comprobante, estado |
| Errores | No autorizado; sin pagos |
| Agente | AG-06 |
| CU / RF / HU | CU-11 / RF-026, RF-027 / HU-14 |

## 7. Skills del AGENTE DE REPORTES (AG-07)

### SK-12 `obtener_reservas_por_periodo`
| Campo | Valor |
|---|---|
| Propósito | Reservas en un rango de fechas (con filtros de estado/cancha) |
| Parámetros | `desde`, `hasta`, `estado`, `cancha_id` |
| Validaciones | Rol autorizado (Gerente/Admin) |
| Permisos | Gerente, Admin |
| Salida | Lista y totales |
| Errores | Sin autorización |
| Agente | AG-07 |
| CU / RF / HU | CU-10 / RF-023 / HU-13 |

### SK-13 `obtener_ingresos` / SK-14 `obtener_ocupacion`
| Campo | Valor |
|---|---|
| Propósito | Ingresos estimados por periodo / ocupación por día/cancha |
| Parámetros | `desde`, `hasta`, `cancha_id` |
| Validaciones | Rol autorizado |
| Permisos | Gerente, Admin |
| Salida | Totales y porcentajes |
| Errores | Sin autorización |
| Agente | AG-07 |
| CU / RF / HU | CU-10 / RF-021, RF-022 / HU-12, HU-13 |

### SK-15 `generar_resumen`
| Campo | Valor |
|---|---|
| Propósito | Resumen administrativo consolidado |
| Parámetros | `periodo` |
| Validaciones | Rol autorizado |
| Permisos | Gerente, Admin |
| Salida | Texto/resumen de indicadores |
| Errores | Sin autorización |
| Agente | AG-07 |
| CU / RF / HU | CU-10 / RF-021 / HU-12 |

## 8. Skills del AGENTE DE SOPORTE (AG-08)

### SK-16 `responder_faq` / SK-17 `informacion_contacto`
| Campo | Valor |
|---|---|
| Propósito | Responder FAQ (horarios de atención, servicios, políticas registradas) y datos de contacto |
| Parámetros | `pregunta` |
| Validaciones | No inventar información (RN-IA-04); usar solo datos registrados (`config/chatbot.php`) |
| Permisos | Lectura pública |
| Salida | Respuesta de soporte |
| Errores | Pregunta fuera de alcance → respuesta de límite |
| Agente | AG-08 |
| CU / RF / HU | CU-12 / RF-028 / HU-17 |

## 9. Skills del AGENTE DE SEGURIDAD (AG-09)

### SK-18 `validar_acceso`
| Campo | Valor |
|---|---|
| Propósito | Validar autenticación y autorización antes de cualquier skill sensible |
| Parámetros | `usuario`, `operacion`, `recurso` |
| Validaciones | RN-010, RN-012, roles |
| Permisos | Transversal |
| Salida | Autorizado / denegado |
| Errores | 401 / 403 |
| Agente | AG-09 |
| CU / RF / HU | CU-01 / RF-003 / HU-02 |

### SK-19 `detectar_fuera_alcance`
| Campo | Valor |
|---|---|
| Propósito | Detectar solicitudes peligrosas o fuera del alcance del sistema |
| Parámetros | `consulta` |
| Validaciones | Catálogo de intenciones conocidas; delimitaciones (§37) |
| Permisos | Transversal |
| Salida | Bloqueo + respuesta de límite (sin inventar) |
| Errores | — |
| Agente | AG-09 |
| CU / RF / HU | CU-12 / RF-036 / HU-16 |

---

## 10. Matriz skill → agente → sprint

| Skill | Agente | Sprint | Base existente |
|---|---|---|---|
| SK-01..SK-05 | AG-02 | 6 | `ReservaController`, `Reserva` |
| SK-06, SK-07 | AG-03 | 6 | `Cancha::horariosDisponiblesPara()` |
| SK-08, SK-09 | AG-04 | 6 | `Cancha`, `CanchaController` |
| SK-10 | AG-05 | 6 | `User` |
| SK-11 | AG-06 | 4/6 | (pendiente módulo pagos) |
| SK-12..SK-15 | AG-07 | 6 | `EstadisticasService` |
| SK-16, SK-17 | AG-08 | 6 | intenciones saludo/ayuda/contacto |
| SK-18, SK-19 | AG-09 | 6 | middleware `admin`, permisos |
