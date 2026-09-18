# Implementación gradual de Gemini y Google Calendar

Fecha de corte: 2026-09-11.

## Estado real

- Fase 0: completada para Gemini y Calendar. MySQL, migraciones, cola y OAuth fueron comprobados; el cliente OAuth del login Google aún debe rotarse por separado.
- Fase 1: implementación, pruebas automatizadas y consulta real con Gemini completadas.
- Fase 2: implementación, pruebas automatizadas y ciclo real completo con Google Calendar completados.
- Fase 3: no iniciada intencionalmente. No se añadió una tabla de franjas ni columnas de hora a `reservas`.

El proyecto no contenía un repositorio Git ni otro mecanismo de versiones. Por ello no fue posible producir el punto de restauración previo solicitado. No se inicializó Git ni se copió el proyecto automáticamente para evitar incluir `.env`, datos o archivos ajenos en un respaldo tardío.

## Fase 0 — Seguridad y preparación

- El arranque de Artisan y PHPUnit ya no consulta MySQL desde `AppServiceProvider`; las vistas usan una paleta segura mientras la BD no está disponible.
- `GEMINI_API_KEY` es la credencial principal y `CHATBOT_API_KEY` queda como compatibilidad temporal.
- Se eliminaron referencias de ejecución a `GOOGLE_API_KEY`; Calendar usa OAuth.
- `.env.example` no contiene credenciales ni datos de contacto ficticios.
- El endpoint del chatbot tiene un limitador independiente por usuario autenticado o IP visitante.
- Las tablas `jobs` y `failed_jobs`, MySQL y todas las migraciones fueron verificadas en el entorno real.

Archivos:

- `.env` (ajuste local de proveedor/modelo/límites, sin documentar valores secretos)
- `.env.example`
- `app/Models/Ajuste.php`
- `app/Providers/AppServiceProvider.php`
- `config/chatbot.php`
- `config/services.php`
- `routes/publico.php`
- `tests/Feature/VisualThemeTest.php`

## Fase 1 — Gemini NLP seguro

- `ProveedorExterno::analizar()` realiza como máximo una llamada `generateContent` por mensaje libre, con `x-goog-api-key`, timeout, JSON Schema y sin reintentos.
- `AnalisisNlp` valida intención, reserva, referencias, fecha, horas y duración antes de que el resultado pueda usarse.
- Gemini no recibe precios, disponibilidad, clientes ni reservas existentes; el backend resuelve referencias y genera la respuesta operativa desde la BD.
- Confirmaciones o negativas de un borrador activo omiten el proveedor externo.
- El fallback local produce el mismo DTO básico.
- Modificación, cancelación, hora final y duración sólo se reconocen. No se ejecutan antes de la fase 3.
- Se retiraron tarifas predeterminadas y datos de contacto predeterminados del chatbot.
- La primera llamada real reveló una incompatibilidad del esquema REST de `generateContent` con `additionalProperties` y tipos como arreglo. Se separó el esquema Gemini usando `nullable`; la consulta posterior devolvió correctamente intención y entidades validadas.

Archivos:

- `app/Services/Chatbot/AnalisisNlp.php`
- `app/Services/Chatbot/DetectorIntencion.php`
- `app/Services/Chatbot/ExtractorEntidades.php`
- `app/Services/Chatbot/ProveedorExterno.php`
- `app/Services/ChatbotService.php`
- `config/chatbot.php`
- `tests/Unit/AnalisisNlpTest.php`
- `tests/Unit/ChatbotMotorTest.php`
- `tests/Feature/ChatbotTest.php`

## Fase 2 — Calendar idempotente

- `google_calendar_events` sigue siendo la única relación; no se agregó `google_calendar_event_id` a `reservas`.
- Pendiente no genera vínculo ni evento. Confirmada crea o actualiza. Cancelada y completada conservan el mismo evento con prefijo visible. Sólo una eliminación física administrativa elimina el evento externo.
- El `calendar_id` guardado en el vínculo no se sustituye si cambia después el calendario predeterminado.
- Se agregó unicidad para `(calendar_id, google_event_id)` y exclusión mutua del job por reserva.
- Los estados locales incluyen `pending`, `synced`, `error`, `cancelled` y `deleted`.
- La sincronización manual reconcilia confirmadas futuras, vínculos pendientes/con error y eventos huérfanos pendientes de eliminación.
- El panel administrativo permite verificar explícitamente el calendario y sus permisos.
- La renovación de token y los errores de Google están cubiertos con HTTP simulado; un error externo no altera la reserva local.
- OAuth y los permisos sobre `primary` fueron verificados contra Google. La reserva controlada #13 pasó por pendiente, confirmada, actualización y cancelación conservando el mismo vínculo y evento externo.

Archivos:

- `app/Contracts/GoogleCalendarGateway.php`
- `app/Http/Controllers/Admin/GoogleCalendarController.php`
- `app/Jobs/DeleteGoogleCalendarEvent.php`
- `app/Jobs/SyncReservationToGoogleCalendar.php`
- `app/Models/GoogleCalendarEvent.php`
- `app/Services/GoogleCalendarService.php`
- `app/Services/ReservaService.php`
- `database/migrations/2026_09_11_000001_harden_google_calendar_event_identity.php`
- `resources/views/admin/integraciones/google-calendar.blade.php`
- `routes/admin.php`
- `tests/Feature/GoogleIntegrationTest.php`

## Documentación y artefactos

- `README.md`
- `docs/ai/AI_ARCHITECTURE.md`
- `docs/architecture/ARCHITECTURE.md`
- `docs/decisions/ARCHITECTURE_DECISIONS.md`
- `public/build/manifest.json` y `public/build/assets/*` fueron reconstruidos por Vite.

## Verificaciones ejecutadas

- `php artisan test --colors=never`: 175 pruebas aprobadas, 522 aserciones.
- Pruebas focalizadas de NLP, chatbot, Calendar y tema visual: 55 aprobadas, 216 aserciones.
- `vendor/bin/pint`: aprobado.
- `composer validate --strict`: aprobado.
- `npm run build`: aprobado; sólo se observaron advertencias deprecadas de Sass y URLs de imágenes resueltas en tiempo de ejecución.
- `php artisan list --raw` y listado de rutas: aprobados sin MySQL.
- Escaneo del árbol, excluyendo `.env`, `vendor` y `node_modules`: no encontró claves con formato sensible; sólo una cadena de ejemplo en un comentario de `.env.example`.
- Puerto `127.0.0.1:3306`: conexión aprobada después de iniciar XAMPP.
- `php artisan migrate:status`: todas las migraciones aparecen ejecutadas.
- `php artisan migrate --force`: las dos migraciones pendientes se aplicaron correctamente en MySQL.
- Consulta real Gemini: `hacer_reserva` y todas las entidades del rango fueron recibidas mediante el DTO validado.
- Prueba real Calendar: pendiente sin evento; confirmación con creación; observación actualizada sobre el mismo ID; cancelación conservada con estado `cancelled`; cola y `failed_jobs` terminaron vacíos.

PHP emite además la advertencia ambiental `Module "openssl" is already loaded`; no afecta el resultado de las pruebas, pero debe corregirse en la configuración de PHP/XAMPP.

## Estado previo a la fase 3

Gemini y Calendar superaron sus pruebas reales. El evento de evidencia de la reserva #13 se conserva en `primary` marcado como cancelado y `GOOGLE_CALENDAR_ENABLED=true`.

La fase 3 ya puede comenzar técnicamente, pero continúa sin cambios hasta cerrar la rotación y prueba independiente del inicio de sesión Google solicitada por el usuario.
