# ARCHITECTURE — Arquitectura del Sistema

> Sprint 0. Arquitectura inicial por capas, verificada contra la implementación real (Laravel 12).
> Decisiones asociadas: `docs/decisions/ARCHITECTURE_DECISIONS.md` (ADR-001 a ADR-006).

---

## 1. Principios

1. **Separación de responsabilidades** por capas (presentación / aplicación / dominio / persistencia / IA).
2. **La base de datos es la fuente de verdad**; la IA solo interpreta y consulta.
3. **La lógica de negocio vive en Laravel** (servicios, reglas, modelos).
4. **IA intercambiable**: el proveedor externo es un componente reemplazable con fallback local.
5. **Extensible sin ampliar alcance**: estructura preparada para otros deportes y pagos en línea, sin implementarlos.

---

## 2. Vista de capas

### 2.1 Capa de presentación
- Blade + Bootstrap 5 + JavaScript (Vite).
- Páginas públicas: inicio, catálogo de canchas, disponibilidad.
- Panel administrativo: dashboard, usuarios, canchas, horarios, reservas, reportes, conversaciones del chatbot, ajustes.
- Widget de chat flotante (público y admin).
- Librerías: DataTables, SweetAlert2, jQuery, FullCalendar, Chart.js.

### 2.2 Capa de aplicación
- `app/Http/Controllers` (públicos y `Admin/`).
- `app/Http/Middleware` (`EnsureUserIsAdmin`).
- `app/Services` (`ReservaService`, `ChatbotService`, `GoogleCalendarService`, `EstadisticasService`).
- `app/Rules` (`SinSuperposicionHorario`).
- Validación de requests con Form Requests / validación inline.

### 2.3 Capa de dominio
- Modelos Eloquent: `User`, `Cancha`, `Horario`, `Reserva`, `Conversacion`, `MensajeChatbot`, `Ajuste`.
- Reglas de negocio (RN-001..RN-017) implementadas en modelos y servicios.
- Lógica del chatbot: detección de intención, extracción de entidades, contexto.
- **Futuro (Sprint 6):** Orquestador + Agentes + Skills como servicios de dominio.

### 2.4 Capa de persistencia
- MySQL/MariaDB (XAMPP), SQLite en pruebas.
- Migraciones, relaciones, claves foráneas, índices únicos (anti-superposición de horarios y anti-duplicidad de reservas).

### 2.5 Capa de IA
```
ChatbotService
├── DetectorIntencion + ExtractorEntidades (fallback local)
└── ProveedorExterno::analizar() (una llamada JSON estructurada)
       └── AnalisisNlp (DTO validado)
```

### 2.6 Sincronización externa

`Reserva` mantiene el estado canónico. `google_calendar_events` conserva la relación estable con el evento externo y el calendario donde fue creado. Las reservas pendientes no generan eventos; confirmadas, canceladas y completadas se sincronizan de forma idempotente mediante jobs de cola. Calendar nunca decide el estado de la reserva.

---

## 3. Mapa de módulos

| Módulo | Capa principal | Estado |
|---|---|---|
| Autenticación | Aplicación | ✅ |
| Usuarios | Aplicación | ✅ |
| Roles y permisos | Aplicación/Dominio | ⚠️ parcial |
| Clientes | Aplicación | ⏳ |
| Canchas | Dominio/Persistencia | ✅ |
| Horarios | Dominio/Persistencia | ✅ |
| Disponibilidad | Dominio | ✅ |
| Reservas | Dominio/Persistencia | ✅ |
| Pagos (registro) | Aplicación | ⏳ |
| Reportes | Aplicación | ✅ |
| Dashboard | Presentación/Aplicación | ✅ |
| Chatbot NLP | IA/Aplicación | ✅ |
| Sistema multiagente | IA/Dominio | ⏳ |
| Configuración | Aplicación | ✅ |
| Auditoría | Aplicación | ⏳ |

---

## 4. Flujo controlado de la IA (regla fundamental)

```
Usuario → Chatbot → Orquestador → Agente especializado → Skill
       → Servicio de aplicación → Validación → Base de datos
       → Resultado → Agente → Orquestador → Chatbot → Usuario
```

El flujo real es: `ChatbotController → ChatbotService → (ProveedorExterno::analizar | motor local) → AnalisisNlp → resolución y reglas en Laravel/BD → respuesta local`. El proveedor externo no recibe resultados operativos ni escribe en la base de datos.

---

## 5. Vista de despliegue (objetivo)

```
[Cliente / Navegador]
        │ HTTPS
        ▼
[XAMPP: Apache]  →  Laravel 12 (PHP 8.2)
        │
        ├── MySQL/MariaDB (persistencia)
        ├── [Opcional] API de IA (Gemini/OpenAI) vía .env
        └── [Opcional] Google Calendar vía OAuth + worker de colas
```

---

## 6. Escalabilidad sin ampliar alcance

| Necesidad futura | Preparación de arquitectura | Implementado hoy |
|---|---|---|
| Otros deportes | Modelo de datos parametrizable (el catálogo ya es genérico por `canchas`) | No (delimitación) |
| Pagos en línea | Módulo de pagos separado, lista para un gateway | No (solo registro administrativo) |
| Otros proveedores de IA | Abstracción `ProveedorExterno` | Solo openai/gemini |
| Aplicación móvil | API/JSON ya disponible en el chatbot | No (delimitación) |
