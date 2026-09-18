# AGENTS — Sistema Multiagente

> Sprint 0. Catálogo de agentes especializados (sección 5 del prompt maestro).
> Estado: **⏳ planificado para Sprint 6** — la lógica base existe hoy en `ChatbotService` y los servicios de aplicación; el refactor insertará Orquestador + Agentes en el flujo.

---

## 1. Principios

- Cada agente tiene **skills claramente definidas** e interfaz documentada (ver `docs/skills/SKILLS.md`).
- Los agentes se comunican mediante **herramientas/servicios controlados**; ninguno tiene acceso indiscriminado a la BD.
- El Orquestador **coordina**, no ejecuta todas las operaciones.
- El Agente de Seguridad es una **capa transversal** de control.
- No se crean agentes innecesarios (sección 20 del prompt).

---

## 2. Catálogo de agentes

| ID | Agente | Responsabilidad | Skills principales | Permisos |
|---|---|---|---|---|
| AG-01 | Orquestador | Recibir la consulta, analizar la intención general, decidir qué agente interviene, dividir consultas complejas, coordinar, controlar contexto, evitar contradicciones, consolidar respuesta. | `analizar_intencion`, `delegar`, `consolidar_respuesta` | Ninguno sobre datos; solo coordinación |
| AG-02 | Reservas | Consultar/crear/modificar/cancelar reservas; validar posibilidad; evitar conflictos de horario. | `crear_reserva`, `cancelar_reserva`, `modificar_reserva`, `consultar_reservas`, `verificar_disponibilidad`, `obtener_historial_reservas` | Cliente: solo sus reservas; Admin/Recepcionista: según rol |
| AG-03 | Disponibilidad | Consultar canchas y horarios disponibles, detectar conflictos, ofrecer alternativas. | `consultar_disponibilidad`, `consultar_alternativas` | Lectura pública |
| AG-04 | Canchas | Información de canchas: características, estado, horarios, precios, mantenimiento. Distingue disponible/reservada/mantenimiento/fuera de horario. | `listar_canchas`, `consultar_cancha`, `consultar_estado_cancha`, `consultar_horarios` | Lectura pública |
| AG-05 | Clientes | Consultar información permitida del cliente autenticado, historial de reservas. Nunca entrega información privada de otro usuario. | `consultar_perfil`, `obtener_historial_reservas` | Solo usuario autenticado y dueño de los datos |
| AG-06 | Pagos | Consultar registros de pagos, estado administrativo, comprobantes registrados (sin pagos en línea). | `consultar_pagos`, `consultar_estado_pago` | Cliente: sus pagos; Admin/Recepcionista: según rol |
| AG-07 | Reportes | Indicadores, estadísticas, análisis de reservas/ocupación/ingresos, resúmenes administrativos (datos autorizados). | `obtener_reservas_por_periodo`, `obtener_ingresos`, `obtener_ocupacion`, `generar_resumen` | Gerente/Admin |
| AG-08 | Soporte/Información | FAQ, horarios de atención, servicios, políticas registradas. No inventa información. | `responder_faq`, `informacion_contacto` | Lectura pública |
| AG-09 | Seguridad | Capa de control: validar autenticación/autorización, identificar operaciones sensibles, impedir acceso no autorizado, detectar solicitudes peligrosas/fuera de alcance, impedir acciones administrativas sin autorización. | `validar_acceso`, `detectar_fuera_alcance`, `bloquear_operacion` | Transversal |

---

## 3. Interacciones típicas (secuencias textuales)

### 3.1 Consulta de disponibilidad
```
Cliente → Chatbot → Orquestador → AG-03 Disponibilidad
   → skill consultar_disponibilidad → Cancha::horariosDisponiblesPara() → MySQL
   → AG-03 → Orquestador → Chatbot → Cliente
```

### 3.2 Consulta de reservas del usuario
```
Cliente (autenticado) → Chatbot → Orquestador → AG-09 Seguridad (¿autenticado? ¿dueño?)
   → AG-02 Reservas → skill consultar_reservas (filtra por user_id) → Reserva → MySQL
   → AG-02 → Orquestador → Chatbot → Cliente
```

### 3.3 Consulta fuera de alcance
```
Usuario → Chatbot → Orquestador → AG-09 Seguridad (fuera de alcance) → respuesta de límite (sin inventar)
```

---

## 4. Ejemplos de consultas por agente

| Agente | Ejemplo |
|---|---|
| AG-02 | "Quiero reservar una cancha mañana a las 19:00." / "¿Qué reservas tengo?" / "Cancela mi reserva de mañana." |
| AG-03 | "¿Qué canchas están libres hoy?" / "¿Qué horarios hay mañana?" |
| AG-04 | "¿Cuánto cuesta la cancha VIP?" / "¿La cancha del sur está en mantenimiento?" |
| AG-05 | "¿Cuál es el estado de mi cuenta?" |
| AG-06 | "¿Está registrado mi pago de la reserva del sábado?" |
| AG-07 | "¿Cuántas reservas hubo este mes?" (solo Gerente/Admin) |
| AG-08 | "¿Cuál es el teléfono y la dirección?" |

---

## 5. Criterios de aceptación del Sprint 6 (resumen)

- [ ] El Orquestador delega correctamente las 8 familias de consultas.
- [ ] Cada agente expone skills documentadas (ver SKILLS.md).
- [ ] El Agente de Seguridad bloquea consultas no autorizadas (probado con tests).
- [ ] Ningún agente escribe en BD; solo vía servicios validados.
- [ ] El chatbot sigue funcionando con el motor local si el proveedor externo falla.
