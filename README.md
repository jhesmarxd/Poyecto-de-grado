# Wally Reservas

Sistema web para administrar canchas de Wally, disponibilidad, reservas, atención en recepción y consultas mediante chatbot.

El proyecto está construido con Laravel 12 y organiza la experiencia en tres perfiles autenticados: cliente, recepcionista y administrador. Las secciones públicas permanecen disponibles para visitantes.

## Estado del proyecto

La revisión funcional se completó en cuatro fases:

1. Autenticación, roles, permisos y usuarios activos.
2. Navegación simplificada y agrupada por perfil.
3. Agenda operativa, reserva presencial, check-in y check-out.
4. Historial paginado, concurrencia, limpieza y documentación.

## Funcionalidades

### Visitantes y clientes

- Catálogo público de canchas y características.
- Consulta de disponibilidad real por cancha y fecha.
- Registro, inicio de sesión y recuperación de contraseña.
- Creación de reservas con balón propio, prestado o premium.
- Historial personal y cancelación de reservas futuras.
- Chatbot escrito y por voz con tarjetas de canchas y balones, disponibilidad real y creación confirmada de reservas continuas.

### Recepción

- Agenda diaria única con la siguiente acción válida para cada reserva.
- Confirmación de reservas pendientes.
- Registro separado de hora de entrada y hora de salida.
- Creación de reservas presenciales para clientes activos.
- Carga dinámica de franjas disponibles según cancha y fecha.
- Búsqueda de clientes e historial operativo paginado.

El ciclo operativo es:

```text
Pendiente → Confirmada → Entrada registrada → Salida registrada → Completada
```

### Administración

- Dashboard con métricas, ocupación e ingresos estimados.
- Gestión de reservas y calendario.
- Gestión de canchas, horarios y balones.
- Configuración semanal masiva por periodo, con franjas de 30 a 120 minutos y cierres excepcionales por fecha.
- Carga de imágenes reales de canchas y balones desde el panel.
- Gestión de clientes, recepcionistas y administradores.
- Reportes filtrables y exportación CSV.
- Historial de conversaciones del chatbot.
- Configuración visual del sistema.
- Confirmaciones y avisos centrales para crear, editar, cancelar o eliminar registros, acompañados por las animaciones del sistema.

## Protección de datos y concurrencia

- Las rutas están protegidas por autenticación, middleware de rol y políticas.
- Los usuarios inactivos no pueden iniciar sesión ni ser seleccionados para una reserva presencial.
- La creación de reservas utiliza transacciones y bloqueos de filas para serializar el acceso a cancha, horario y balón.
- La base de datos mantiene una restricción única para impedir reservas activas duplicadas.
- Las reservas de varias horas bloquean por solapamiento tanto la cancha como el balón compartido.
- Los conflictos simultáneos se devuelven como respuesta HTTP `409` en lugar de exponer errores SQL.

## Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2+, Laravel 12, Eloquent, Blade |
| Base de datos | MySQL/MariaDB; SQLite para pruebas |
| Frontend | Bootstrap 5, Sass, JavaScript |
| Componentes | Chart.js, FullCalendar, DataTables, SweetAlert2 |
| Assets | Vite 7 |
| Pruebas | PHPUnit 11 |

## Requisitos

- PHP 8.2 o superior.
- Composer.
- Node.js 20 o superior y npm.
- MySQL o MariaDB; XAMPP puede proporcionar ambos servicios.

## Instalación

```bash
composer install
npm install
```

Copia `.env.example` como `.env`, configura la conexión `DB_*` y ejecuta:

```bash
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

Si necesitas datos demostrativos, ejecútalos únicamente sobre una base vacía:

```bash
php artisan db:seed --class=DemoDatabaseSeeder
```

La aplicación estará disponible normalmente en `http://127.0.0.1:8000`.

Para desarrollo con recarga automática:

```bash
npm run dev
```

## Usuarios iniciales

Se crean mediante `DemoDatabaseSeeder` únicamente para desarrollo. El seeder se detiene si detecta usuarios, canchas o reservas existentes.

| Perfil | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@wally.com` | `Admin123!` |
| Recepcionista | `recepcion@wally.com` | `Recep123!` |
| Cliente de ejemplo | `juan.garcia@email.com` | `Cliente123!` |

Las credenciales son únicamente para desarrollo. Deben cambiarse antes de publicar el sistema.

## Rutas principales

| Sección | Ruta |
|---|---|
| Inicio público | `/` |
| Canchas | `/canchas` |
| Disponibilidad | `/disponibilidad` |
| Reservas del cliente | `/mis-reservas` |
| Agenda de recepción | `/recepcionista/reservas/hoy` |
| Historial de recepción | `/recepcionista/reservas` |
| Administración | `/admin` |
| Reportes | `/admin/reportes` |
| Autorización técnica de Google Calendar | `/admin/integraciones/google-calendar/conectar` |

## Chatbot

El motor local funciona sin servicios externos, sin costo y sin consumir tokens. Para usar exclusivamente este modo, deja vacías estas variables en `.env`:

```dotenv
CHATBOT_PROVIDER=
GEMINI_API_KEY=
CHATBOT_API_KEY=
CHATBOT_MODEL=
```

El bot conserva el contexto de cancha, fecha, intención y rango horario entre mensajes. Acepta formatos equivalentes como `7 pm`, `7:00 p. m.`, `19:00` y `19:00 pm`, que se normalizan a `19:00`. Una solicitud como `de 4 a 6 pm` genera una sola reserva continua, siempre que todas las franjas intermedias estén habilitadas y libres.

El widget puede moverse y redimensionarse en escritorio. Los visitantes pueden abrirlo y escribir, pero antes de consultar datos reciben accesos directos para iniciar sesión o registrarse. Los clientes autenticados ven tarjetas seleccionables con la imagen administrada de cada cancha y balón, su estado de disponibilidad y botones para confirmar o descartar la reserva.

Gemini se activa con `CHATBOT_PROVIDER=gemini`, `GEMINI_API_KEY` y `CHATBOT_MODEL`. `CHATBOT_API_KEY` se conserva temporalmente por compatibilidad. Cada mensaje libre realiza como máximo una llamada NLP estructurada; confirmaciones y respuestas de un borrador activo se resuelven localmente.

Gemini únicamente propone intención y entidades. El backend valida el JSON, resuelve las referencias en la base de datos y genera todas las respuestas sobre precios, disponibilidad y reservas. Si Gemini falla, el motor local continúa atendiendo la conversación. Los límites predeterminados son 10 mensajes por minuto para visitantes y 30 para usuarios autenticados, configurables mediante `CHATBOT_RATE_LIMIT_GUEST` y `CHATBOT_RATE_LIMIT_AUTHENTICATED`.

Antes de llamar al proveedor, una política local bloquea solicitudes de credenciales, secretos, variables internas, SQL, datos privados de terceros, escalamiento de privilegios y manipulación del prompt. El contenido bloqueado se almacena redactado y nunca se envía a Gemini.

Para crear una reserva, el bot reúne cancha, fecha, horario y balón, muestra el total real y exige la frase explícita `sí, confirmar` antes de usar el servicio de reservas. El borrador vence después de 10 minutos. Si cambia la disponibilidad antes de confirmar, la operación se rechaza y se muestran alternativas actualizadas.

## Métricas del chatbot

El panel `/admin/chatbot/metricas` resume conversaciones con actividad, mensajes recibidos, usuarios identificados, visitantes, intenciones, consultas bloqueadas, consultas no resueltas y reservas creadas realmente por el chatbot. Puede filtrarse por 7, 30, 90 días o todo el período.

Las conversiones se vinculan explícitamente con la conversación cuando se crea la reserva; no se deducen del texto ni incluyen reservas hechas desde otros formularios. Este seguimiento comienza con las reservas conversacionales creadas después de incorporar esta versión. El panel utiliza conteos agregados y no duplica el contenido de los mensajes.

La observabilidad registra intentos y uso del proveedor externo, fallbacks locales, errores externos y latencia promedio. No se almacenan respuestas crudas del proveedor ni se muestran métricas de tokens porque esos datos no se registran de manera verificable.

## Temporizador de reservas

La pantalla **Mis reservas** muestra una tarjeta para la reserva confirmada vigente o futura más próxima. Laravel entrega la hora de inicio, la hora de finalización y una referencia del reloj del servidor; el navegador actualiza el contador localmente cada segundo sin polling ni consultas repetidas a la base de datos.

El componente cambia entre reserva próxima, menos de 1 hora, menos de 30 minutos, menos de 10 minutos, en curso y finalizada. Al recargar o reabrir la pantalla se vuelve a calcular desde los datos reales de la reserva y la zona horaria configurada en `APP_TIMEZONE`.

## Notificaciones de reservas

Los clientes reciben notificaciones internas persistentes cuando una reserva es confirmada, cancelada, iniciada o finalizada. El scheduler genera además recordatorios únicos cuando faltan menos de 2 horas y menos de 30 minutos. Cada combinación de reserva y tipo se registra una sola vez, incluso si el comando vuelve a ejecutarse.

Esta versión agrega únicamente la tabla estándar `notifications`. Antes de habilitar el módulo en una base existente, revisa las migraciones pendientes y aplica la migración aditiva:

```bash
php artisan migrate:status
php artisan migrate
```

Para desarrollo local puede mantenerse activo el scheduler con `php artisan schedule:work`. En producción basta configurar una única ejecución por minuto de `php artisan schedule:run`; no se necesita polling, WebSockets ni un proceso independiente por reserva.

## Google OAuth y Calendar

El inicio de sesión con Google y la sincronización empresarial de reservas usan flujos OAuth separados. Copia las variables de `.env.example` a tu `.env` y registra exactamente estas rutas como URI de redirección en Google Cloud:

```text
{APP_URL}/auth/google/callback
{APP_URL}/admin/integraciones/google-calendar/callback
```

Mantén `GOOGLE_CALENDAR_ENABLED=false` durante la configuración y las pruebas locales. No existe un módulo visual de Calendar: con una sesión de administrador, abre una vez la ruta técnica `/admin/integraciones/google-calendar/conectar` para autorizar la cuenta empresarial. Cuando la cuenta esté conectada y el worker de colas esté funcionando, cambia la variable a `true` y limpia la configuración:

```bash
php artisan config:clear
php artisan queue:work
```

Las reservas pendientes no generan eventos. Al confirmarse se crea un evento estable en el calendario empresarial y se envía una invitación al correo registrado del cliente; el cliente puede consultarlo desde **Mis reservas** y abrirlo en Google Calendar. Los cambios posteriores actualizan ese mismo evento; las canceladas y reprogramadas quedan marcadas con su estado visible. La base de datos continúa siendo la única fuente de verdad.

Cada evento incluye cliente, cancha, horario, duración, estado y recordatorios configurables mediante `GOOGLE_CALENDAR_REMINDERS`. Google envía las actualizaciones de la invitación al cliente mediante `sendUpdates=all`.

Los tokens OAuth de Calendar se guardan cifrados. Los secretos reales deben existir únicamente en `.env`; nunca en `.env.example`, vistas, JavaScript ni el repositorio.

## Pruebas y calidad

Ejecuta la suite completa con:

```bash
php artisan test
```

Formatea el código PHP con:

```bash
vendor/bin/pint
```

Compila los recursos de producción con:

```bash
npm run build
```

Si PHP muestra `Module "openssl" is already loaded`, revisa los archivos `php.ini` usados por CLI y XAMPP: la extensión está habilitada más de una vez. Esta advertencia pertenece al entorno, no a Laravel.

## Estructura relevante

```text
app/Http/Controllers/   Controladores por perfil
app/Http/Requests/      Validación de formularios
app/Models/             Modelos y estados de negocio
app/Policies/           Autorización por recurso
app/Services/           Reservas, reportes y chatbot
resources/views/        Vistas Blade y layouts
routes/                 Rutas públicas, cliente, recepción y administración
tests/                  Pruebas unitarias y de integración
```
