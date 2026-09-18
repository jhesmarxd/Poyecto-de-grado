# AI_ARCHITECTURE — Arquitectura del Componente de Inteligencia Artificial

> Sprint 0. Describe la capa de IA actual (verificada en código) y la arquitectura multiagente objetivo (Sprint 6), según las secciones 5, 7, 8, 31 y 32 del prompt maestro.

---

## 1. Principios

1. **La IA es un componente funcional**, no decorativo: interpreta consultas y responde con datos reales del sistema.
2. **La BD es la fuente de verdad**: la IA nunca inventa disponibilidad, precios, reservas, usuarios, pagos ni estados.
3. **La IA nunca modifica la BD directamente**: toda operación sigue el flujo controlado (sección 7 del prompt).
4. **Proveedor intercambiable**: la aplicación no se acopla a un único proveedor.

---

## 2. Capa de abstracción actual

```
ChatbotService
   ├── Motor local (DetectorIntencion + ExtractorEntidades)
   └── ProveedorExterno::analizar() (Gemini | OpenAI, opcional)
          └── AnalisisNlp (DTO validado)
```

- `config/chatbot.php` centraliza proveedor, credenciales, modelo, timeout, contacto y límites de solicitudes.
- Cada mensaje libre puede efectuar como máximo una petición externa. Las respuestas a un borrador activo son locales.
- Gemini sólo devuelve intención y entidades mediante JSON estructurado; nunca recibe ni decide precios, disponibilidad, clientes o estados operativos.
- Si el proveedor no está configurado, devuelve JSON inválido o falla, se usa inmediatamente el mismo contrato producido por el motor local.

## 3. Implementación actual (Sprint 5, verificada)

| Componente | Archivo | Función |
|---|---|---|
| Intención | `app/Services/Chatbot/DetectorIntencion.php` | Reglas locales, incluidas modificación y cancelación reconocidas para la fase 3 |
| Entidades | `app/Services/Chatbot/ExtractorEntidades.php` | Referencia de cancha/balón, reserva, fecha, hora inicial/final y duración |
| Contrato | `app/Services/Chatbot/AnalisisNlp.php` | DTO estricto que normaliza y rechaza valores inválidos |
| Proveedor externo | `app/Services/Chatbot/ProveedorExterno.php` | Una llamada estructurada a Gemini u OpenAI, sin reintentos ni datos operativos |
| Orquestación | `app/Services/ChatbotService.php` | Resuelve entidades contra la BD y genera localmente todas las respuestas operativas |
| Contexto | `app/Models/Conversacion.php` | Memoria conversacional controlada (`contexto` JSON) |
| Persistencia | `app/Models/MensajeChatbot.php` | Guarda cada intercambio con su intención |

## 4. Arquitectura multiagente objetivo (Sprint 6)

### 4.1 Flujo

```
Usuario → Chatbot → ORQUESTADOR (AG-01)
   ├── analiza intención general
   ├── divide consultas complejas en subtareas
   ├── delega al agente especializado (AG-02..AG-08) vía sus skills
   ├── AG-09 SEGURIDAD valida autenticación/autorización/alcance (capa transversal)
   └── consolida y devuelve la respuesta
```

### 4.2 Reglas de comunicación

- Los agentes **no acceden directamente a la BD**; usan **skills** que invocan **servicios de aplicación** con validación.
- El **Agente de Seguridad** actúa como capa de control transversal: valida autenticación, autorización, operaciones sensibles y detecta solicitudes fuera de alcance.
- La IA no ejecuta acciones administrativas sin autorización (RN-IA-01).

### 4.3 Mapeo base → agente (refactor)

| Agente (Sprint 6) | Base actual |
|---|---|
| AG-01 Orquestador | `ChatbotService::responder()` |
| AG-02 Reservas | `ReservaController`, `Reserva` |
| AG-03 Disponibilidad | `Cancha::horariosDisponiblesPara()` |
| AG-04 Canchas | `Cancha`, `CanchaController` |
| AG-05 Clientes | `User`, `Reserva` (historial del usuario autenticado) |
| AG-06 Pagos | (depende de RF-026/RF-027, Sprint 4) |
| AG-07 Reportes | `EstadisticasService` |
| AG-08 Soporte/Información | intenciones `saludo`, `ayuda`, `contacto` |
| AG-09 Seguridad | middleware `admin`, permisos por rol, `RN-IA-01..05` |

## 5. NLP implementado

| Concepto | Implementación |
|---|---|
| Intención | `DetectorIntencion` (reglas + normalización de acentos) o `AnalisisNlp` validado |
| Entidades | reserva, cancha, balón, fecha, hora inicial/final y duración |
| Contexto | `Conversacion::actualizarContexto()` — solo guarda lo mencionado |
| Memoria controlada | El contexto se conserva entre mensajes de la misma conversación |
| Ambigüedad | Hora sin sufijo (ej. "a las 8") → 20:00 en contexto deportivo; pide solo lo faltante |
| Fechas | hoy, mañana, pasado mañana, días de la semana, `15/08`, `15 de agosto`, ISO |

## 6. Restricciones y pruebas de IA

- Prohibido: inventar datos, modificar BD, revelar información privada de otros usuarios, ejecutar operaciones sin autorización.
- Pruebas de IA requeridas (sección 34): intención correcta/incorrecta, entidad faltante/incorrecta, contexto, consultas ambiguas, fuera de alcance, alucinaciones, permisos, operaciones no autorizadas.

La configuración recomendada actual es Gemini mediante `GEMINI_API_KEY`; `CHATBOT_API_KEY` se acepta temporalmente como compatibilidad. Sin clave válida, el chatbot permanece operativo en modo local.
