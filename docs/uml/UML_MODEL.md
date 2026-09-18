# UML_MODEL — Modelo UML (fuente estructurada para diagramas)

> Sprint 0. Fuente estructurada para construir los diagramas (sección 25 del prompt). Los diagramas finales deben derivarse de la implementación real (sección 42).

---

## 1. Actores

| ID | Actor | Descripción |
|---|---|---|
| ACT-01 | Administrador | Control total del sistema |
| ACT-02 | Recepcionista | Operaciones diarias (reservas, clientes, pagos) — ⏳ |
| ACT-03 | Gerente | Consultas administrativas (dashboard, reportes) — ⏳ |
| ACT-04 | Cliente | Reservas y consultas propias |
| ACT-05 | Visitante | Consultas públicas y chatbot |
| ACT-06 | Sistema/Agentes | Actores no humanos del chatbot |

## 2. Casos de uso

| CU | Nombre | Actor | Precondición | Flujo principal (resumen) | RF | Estado |
|---|---|---|---|---|---|---|
| CU-01 | Iniciar sesión | Todos | Usuario registrado y activo | Ingresar credenciales → validar → sesión | RF-002 | ✅ |
| CU-02 | Gestionar usuarios | Administrador | Sesión admin | CRUD usuarios, activar/desactivar, no autoeliminación | RF-004, RF-005 | ✅ |
| CU-03 | Gestionar canchas | Administrador | Sesión admin | CRUD canchas + estados | RF-008, RF-009 | ✅ |
| CU-04 | Gestionar horarios | Administrador | Sesión admin | CRUD horarios con anti-superposición | RF-010, RF-011 | ✅ |
| CU-05 | Consultar disponibilidad | Cliente/Visitante | — | Elegir cancha y fecha → franjas libres | RF-013 | ✅ |
| CU-06 | Registrar reserva | Cliente | Autenticado | Elegir cancha/fecha/franja → validar anti-duplicidad → guardar | RF-014, RF-015 | ✅ |
| CU-07 | Cancelar reserva | Cliente/Admin | Autenticado + reglas RN-008 | Cancelar → liberar franja | RF-017 | ✅ |
| CU-08 | Gestionar reservas (admin) | Administrador | Sesión admin | Listado, filtros, estados, calendario | RF-019, RF-020 | ✅ |
| CU-09 | Consultar dashboard | Gerente/Admin | Sesión autorizada | KPIs y gráficos | RF-021, RF-022 | ✅ |
| CU-10 | Generar reportes | Gerente/Admin | Sesión autorizada | Filtros → tabla → exportar CSV | RF-023, RF-024 | ✅ |
| CU-11 | Registrar pago | Recepcionista/Admin | Sesión autorizada + reserva existente | Registrar monto/comprobante/estado | RF-026, RF-027 | ⏳ |
| CU-12 | Consultar mediante chatbot | Todos | — | Escribir mensaje → intención → respuesta con datos reales | RF-028..RF-036 | ✅ |
| CU-13 | Gestionar reserva mediante chatbot | Cliente | Autenticado + reglas | Reserva guiada vía chatbot con confirmación | RF-031 | ⏳ (parcial: guía) |
| CU-14 | Consultar disponibilidad mediante chatbot | Todos | — | Consulta NLP de disponibilidad real | RF-013, RF-031 | ✅ |

### Excepciones transversales
- Sin autenticación en operación protegida → 401/403.
- Reserva duplicada → 409 (RN-001).
- Cancha en mantenimiento → rechazo (RN-004).
- Consulta fuera de alcance del chatbot → respuesta de límite (RN-IA-04).

## 3. Actividades (resumen estructurado)

### DA-01 Autenticación
`Inicio → ingresar credenciales → [¿válidas?] → Sí: sesión → panel · No: error → reintento`

### DA-02 Creación de reserva
`Inicio → elegir cancha → elegir fecha → elegir franja → [¿libre?] → Sí: confirmar monto → guardar (índice único) → éxito · No: alternativas → fin`
**Decisión clave:** la franja puede perderse entre consulta y guardado → el índice único de BD garantiza integridad.

### DA-03 Cancelación de reserva
`Inicio → seleccionar reserva → [¿cancelable? (RN-008)] → Sí: cancelar → liberar franja → éxito · No: mensaje de regla`

### DA-04 Consulta de disponibilidad
`Inicio → cancha+fecha → calcular horarios del día → excluir reservados → mostrar franjas libres`

### DA-05 Registro de pago (⏳)
`Inicio → seleccionar reserva → registrar monto/método/comprobante → [¿valida?] → guardar estado → fin`

### DA-06 Atención mediante chatbot (multiagente)
`Inicio → mensaje → Orquestador → [¿intención?] → agente especializado → skill → servicio → BD → respuesta → fin · [fuera de alcance] → límite`

## 4. Secuencias (resumen estructurado)

### DS-01 Consulta de disponibilidad
`Cliente → Chatbot → Orquestador → AG-03 → skill consultar_disponibilidad → Cancha::horariosDisponiblesPara() → MySQL → respuesta al Cliente`

### DS-02 Reserva por sitio web
`Cliente → ReservaController::store → validación (RN-001/RN-004) → Reserva::create (índice único) → MySQL → respuesta`

### DS-03 Consulta de reservas vía chatbot
`Cliente(autenticado) → Chatbot → Orquestador → AG-09 (validar acceso) → AG-02 → skill consultar_reservas → Reserva (filtro user_id) → MySQL → respuesta`

### DS-04 Reporte
`Gerente → ReporteController → EstadisticasService → MySQL → tabla + CSV`

## 5. Clases (derivadas del código)

### 5.1 Clases de dominio

| Clase | Atributos (resumen) | Métodos (resumen) | Relaciones |
|---|---|---|---|
| `User` | name, email, password, telefono, rol, estado | `esAdministrador()`, `estaActivo()`, `reservas()`, `conversaciones()` | 1—* Reserva; 1—* Conversacion |
| `Cancha` | nombre, descripcion, ubicacion, capacidad, precio_por_hora, estado | `estaDisponible()`, `horariosDisponiblesPara()`, scope `disponibles()` | 1—* Horario; 1—* Reserva |
| `Horario` | dia_semana, hora_inicio, hora_fin, disponible | `nombreDia()`, `cancha()`, `reservas()` | *—1 Cancha; 1—* Reserva |
| `Reserva` | fecha, monto_total, estado, observaciones | `esCancelablePorCliente()`, scopes `activas()`, `deCanchaEnFecha()` | *—1 User; *—1 Cancha; *—1 Horario |
| `Conversacion` | contexto (JSON) | `contextoDe()`, `actualizarContexto()`, `mensajes()` | *—1 User (0..1); 1—* MensajeChatbot |
| `MensajeChatbot` | emisor, contenido, intencion | `conversacion()` | *—1 Conversacion |
| `Ajuste` | clave, valor | `colores()`, `guardarColores()` | — |

### 5.2 Clases de servicio/aplicación

| Clase | Responsabilidad |
|---|---|
| `ChatbotService` | Orquesta detección, entidades, contexto y respuesta (futuro Orquestador) |
| `DetectorIntencion` | Intenciones por reglas |
| `ExtractorEntidades` | Entidades cancha/fecha/hora |
| `ProveedorExterno` | IA intercambiable (openai/gemini) con fallback |
| `EstadisticasService` | KPIs, gráficos, ocupación, ingresos |
| `SinSuperposicionHorario` | Regla de validación de horarios |
| `EnsureUserIsAdmin` | Middleware de autorización |

### 5.3 Agentes (Sprint 6) — clases planificadas
`Orquestador`, `AgenteReservas`, `AgenteDisponibilidad`, `AgenteCanchas`, `AgenteClientes`, `AgentePagos`, `AgenteReportes`, `AgenteSoporte`, `AgenteSeguridad`, `Skill` (interfaz). Cada agente depende de services existentes (no de la BD directamente).

> **Regla UML:** cada vez que se modifique una funcionalidad, actualizar en cadena: requisito → historia → sprint → código → BD → casos de uso → actividades → secuencias → clases → pruebas → trazabilidad (sección 42).
