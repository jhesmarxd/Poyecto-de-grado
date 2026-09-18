# DATABASE — Base de Datos del Sistema

> Sprint 0. Modelo verificado contra las migraciones reales. Detalle campo a campo en `docs/database/MODELO_DATOS.md`.

---

## 1. Motor y configuración

- **Producción:** MySQL/MariaDB 8.x (XAMPP) — `utf8mb4` / `utf8mb4_unicode_ci`.
- **Pruebas:** SQLite en memoria (`phpunit.xml`).
- **ORM:** Laravel Eloquent; migraciones versionadas.

---

## 2. Entidades existentes

| Tabla | Propósito | Relaciones |
|---|---|---|
| `users` | Usuarios del sistema (admin/cliente; futuro recepcionista/gerente) | 1:N `reservas`, 1:N `conversaciones` |
| `canchas` | Canchas de Wally con características y precio | 1:N `horarios`, 1:N `reservas` |
| `horarios` | Franjas horarias por cancha y día de la semana | N:1 `canchas`, 1:N `reservas` |
| `reservas` | Reservas de canchas por usuario | N:1 `users`, N:1 `canchas`, N:1 `horarios` |
| `conversaciones` | Conversaciones del chatbot (usuario opcional) | N:1 `users`, 1:N `mensajes_chatbot` |
| `mensajes_chatbot` | Mensajes de cada conversación con intención detectada | N:1 `conversaciones` |
| `ajustes` | Configuración clave/valor (colores del sitio) | — |
| `cache`, `jobs` | Infraestructura de framework | — |

## 3. Entidades planificadas

| Tabla | Propósito | Sprint |
|---|---|---|
| `pagos` | Registro administrativo de pagos asociados a reservas | 4 |
| `clientes` (o extensión de `users`) | Ficha del cliente si el documento de tesis la exige separada | 4 |
| `auditorias` / `registro_actividades` | Auditoría de operaciones importantes | 6 |

---

## 4. Reglas de integridad clave

### 4.1 Anti-duplicidad de reservas (RN-001)
Índice único sobre una **columna generada** en `reservas` que combina `cancha_id`, `fecha` y `horario_id` solo cuando la reserva está activa (no cancelada). Efecto:
- Impide dos reservas activas en la misma cancha/fecha/franja incluso con concurrencia.
- Permite re-reservar la franja tras cancelar (la cancelada no participa del índice).

### 4.2 Anti-superposición de horarios (RN-006)
Índice único `(cancha_id, dia_semana, hora_inicio)` en `horarios` + regla de validación `SinSuperposicionHorario` (comprueba cruce de rangos `hora_inicio`–`hora_fin`).

### 4.3 Claves foráneas y borrado
- `reservas.user_id → users.id`, `reservas.cancha_id → canchas.id`, `reservas.horario_id → horarios.id`.
- `horarios.cancha_id → canchas.id`.
- `conversaciones.user_id → users.id` (nullable para visitantes).
- `mensajes_chatbot.conversacion_id → conversaciones.id`.

---

## 5. Estados del dominio

| Entidad | Estados |
|---|---|
| `canchas.estado` | `disponible`, `mantenimiento`, `inactiva` |
| `reservas.estado` | `pendiente`, `confirmada`, `cancelada`, `completada` |
| `users.rol` | `admin`, `cliente` (futuro: `recepcionista`, `gerente`) |
| `users.estado` | booleano (activo/inactivo) |
| `horarios.disponible` | booleano |

---

## 6. Índices recomendados

| Tabla | Índice | Motivo |
|---|---|---|
| `reservas` | `(fecha)`, `(estado)`, `(cancha_id, fecha)` | Filtros de disponibilidad, reportes y dashboard |
| `horarios` | `(cancha_id, dia_semana)` | Consulta de franjas por día |
| `conversaciones` | `(user_id, updated_at)` | Recuperar última conversación del usuario |
| `mensajes_chatbot` | `(conversacion_id)` | Historial por conversación |

---

## 7. Normalización

- Modelo en 3FN: no hay dependencias transitivas; los valores repetidos (estados, días) se modelan como constantes de dominio en los modelos, no como tablas, salvo que el documento de tesis exija catálogos editables (candidato a tablas `estados`/`dias`).
