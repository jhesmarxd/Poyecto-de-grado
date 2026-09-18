# MODELO_DATOS — Modelo de Datos Detallado

> Sprint 0 — elaborado antes de crear tablas nuevas (sección 30 del prompt maestro).
> Verificado contra las migraciones reales del repositorio.

---

## 1. users

| Campo | Tipo | Restricción |
|---|---|---|
| id | BIGINT UNSIGNED | PK, autoincrement |
| name | VARCHAR(255) | NOT NULL |
| email | VARCHAR(255) | NOT NULL, UNIQUE |
| email_verified_at | TIMESTAMP | NULL |
| password | VARCHAR(255) | NOT NULL, hasheado (Bcrypt) |
| telefono | VARCHAR(20) | NULL |
| rol | VARCHAR(20) | NOT NULL, default `cliente` |
| estado | BOOLEAN | NOT NULL, default true |
| remember_token | VARCHAR(100) | NULL |
| created_at / updated_at | TIMESTAMP | NULL |

**Reglas:** `rol ∈ {admin, cliente}` (futuro `recepcionista`, `gerente`); `estado` controla el acceso activo; un usuario no puede eliminarse a sí mismo (RN-009).

## 2. canchas

| Campo | Tipo | Restricción |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| nombre | VARCHAR(100) | NOT NULL, UNIQUE |
| descripcion | TEXT | NULL |
| ubicacion | VARCHAR(150) | NULL |
| capacidad | TINYINT UNSIGNED | NOT NULL, default 4 |
| precio_por_hora | DECIMAL(10,2) | NOT NULL |
| estado | VARCHAR(20) | NOT NULL, default `disponible` |
| timestamps | — | — |

**Reglas:** `estado ∈ {disponible, mantenimiento, inactiva}`; solo `disponible` se ofrece al público (RN-005).

## 3. horarios

| Campo | Tipo | Restricción |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| cancha_id | BIGINT UNSIGNED | FK → canchas, NOT NULL |
| dia_semana | TINYINT UNSIGNED | NOT NULL (1=Lun … 7=Dom) |
| hora_inicio | TIME | NOT NULL |
| hora_fin | TIME | NOT NULL |
| disponible | BOOLEAN | NOT NULL, default true |
| timestamps | — | — |

**Índices/restricciones:** UNIQUE `(cancha_id, dia_semana, hora_inicio)`; regla anti-superposición (RN-006); `hora_fin > hora_inicio`.

## 4. reservas

| Campo | Tipo | Restricción |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| user_id | BIGINT UNSIGNED | FK → users, NOT NULL |
| cancha_id | BIGINT UNSIGNED | FK → canchas, NOT NULL |
| horario_id | BIGINT UNSIGNED | FK → horarios, NOT NULL |
| fecha | DATE | NOT NULL |
| monto_total | DECIMAL(10,2) | NOT NULL |
| estado | VARCHAR(20) | NOT NULL, default `pendiente` |
| observaciones | TEXT | NULL |
| timestamps | — | — |

**Índices/restricciones:** UNIQUE generado `(cancha_id, fecha, horario_id)` solo para reservas activas (RN-001); `estado ∈ {pendiente, confirmada, cancelada, completada}`; la cancelación libera la franja (RN-007).

## 5. conversaciones

| Campo | Tipo | Restricción |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| user_id | BIGINT UNSIGNED | FK → users, NULL (visitante) |
| contexto | JSON | NOT NULL, default [] |
| timestamps | — | — |

**Reglas:** el contexto guarda `cancha_id`, `fecha` y `hora` ya mencionados (memoria conversacional controlada).

## 6. mensajes_chatbot

| Campo | Tipo | Restricción |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| conversacion_id | BIGINT UNSIGNED | FK → conversaciones, NOT NULL |
| emisor | VARCHAR(10) | NOT NULL (`usuario` | `bot`) |
| contenido | TEXT | NOT NULL |
| intencion | VARCHAR(50) | NULL |
| timestamps | — | — |

## 7. ajustes

| Campo | Tipo | Restricción |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| clave | VARCHAR(100) | NOT NULL, UNIQUE |
| valor | VARCHAR(255) | NULL |
| — | — | Sin timestamps |

**Claves actuales:** `color_primario`, `color_acento`, `color_oscuro` (con caché `ajustes.colores`).

---

## 8. Tablas planificadas (para validar antes de migrar)

### 8.1 pagos (Sprint 4) — **REGLA PENDIENTE DE VALIDACIÓN** (campos según documento de tesis)

| Campo propuesto | Tipo | Nota |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| reserva_id | BIGINT UNSIGNED | FK → reservas |
| monto | DECIMAL(10,2) | NOT NULL |
| fecha_pago | DATE | NOT NULL |
| metodo | VARCHAR(30) | efectivo / transferencia / QR |
| comprobante | VARCHAR(255) | NULL (archivo) |
| estado | VARCHAR(20) | `registrado` / `anulado` |
| registrado_por | BIGINT UNSIGNED | FK → users |

### 8.2 auditorias (Sprint 6)

| Campo propuesto | Tipo |
|---|---|
| id | BIGINT UNSIGNED PK |
| user_id | FK → users (nullable) |
| accion | VARCHAR(50) |
| entidad / entidad_id | VARCHAR(50) / BIGINT |
| detalle | JSON |
| ip | VARCHAR(45) |
| created_at | TIMESTAMP |

---

## 9. Dependencias

```
users ─┬─< reservas >─ canchas
       │        │
       │        └─< horarios
       └─< conversaciones ─< mensajes_chatbot
canchas ─< horarios
reservas (futuro) ─< pagos
users (futuro) ─< auditorias
```

> **INFORMACIÓN NO DEFINIDA — REQUIERE VALIDACIÓN:** los campos exactos de `pagos`, `clientes` y `auditorias` dependen del documento de tesis y de las reglas de negocio RN-013..RN-017.
