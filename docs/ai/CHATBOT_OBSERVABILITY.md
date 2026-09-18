# Observabilidad del chatbot — Fase 1

Esta fase añade una ejecución auditable por cada mensaje de usuario procesado.
La traza se relaciona con `mensajes_chatbot` y no modifica la respuesta ni las
reglas operativas del chatbot.

## Motores registrados

`engine_used` usa uno de estos valores:

| Valor | Significado |
|---|---|
| `external` | Se recibió y utilizó un análisis estructurado válido del proveedor externo. |
| `local` | La intención y entidades efectivas provinieron del motor local. Incluye proveedor deshabilitado o fallback por fallo externo. |
| `draft` | Había un borrador de reserva activo y, por diseño, no se intentó la llamada externa. |
| `blocked` | La política de seguridad bloqueó el mensaje antes de consultar al proveedor. |
| `none` | No se utilizó ningún motor. Reservado para estados futuros o procesamiento interrumpido antes del análisis. |

## Semántica externa

- `external_attempted`: se inició una petición HTTP al proveedor.
- `external_used`: el proveedor devolvió un análisis válido que fue consumido por el chatbot.
- `fallback_local`: el resultado funcional se obtuvo con el motor local.
- `external_status`: resultado normalizado de la integración, por ejemplo
  `success`, `timeout`, `connection_error`, `http_400`, `http_401`,
  `http_429`, `http_5xx`, `empty_response`, `invalid_json` o
  `invalid_structured_analysis`.

Un borrador o un bloqueo tienen `external_attempted=false`, pero no son fallback:
usan respectivamente `draft` o `blocked`.

## Privacidad

`structured_parameters` aplica una lista cerrada de entidades de reservas. No
se guardan respuestas crudas, prompts completos, headers, cookies, claves,
tokens, contraseñas ni variables de entorno. Los mensajes de error persistidos
son descripciones normalizadas y nunca el cuerpo devuelto por el proveedor.

La observabilidad es *best effort*. Un fallo al persistir la traza se registra
con identificadores y clase de excepción, sin contenido sensible, y no impide
que el chatbot responda mediante el motor correspondiente.
