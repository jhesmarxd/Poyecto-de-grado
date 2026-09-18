# 🗂️ Estructura de Navegación — Wally Reservas

> Documento que describe el flujo de navegación del sistema por cada rol de usuario, mostrando las rutas, secciones del sidebar y acciones disponibles.

---

## 1. Roles del Sistema

| # | Rol | Constante | Descripción | Acceso al Admin |
|---|-----|-----------|-------------|-----------------|
| 1 | **Administrador** | `ROL_ADMIN = 'admin'` | Control total del sistema: canchas, usuarios, reportes, ajustes, horarios, reservas, chatbot | ✅ Panel completo |
| 2 | **Recepcionista** | `recepcionista` (string en BD) | Gestión operativa del día a día: crear reservas presenciales, consultar disponibilidad, gestionar check-in/check-out | ⚠️ Panel parcial |
| 3 | **Cliente** | `ROL_CLIENTE = 'cliente'` | Consulta catálogo, reserva canchas en línea, gestiona sus propias reservas | ❌ Solo panel personal |

---

## 2. Layouts de Navegación

| Layout | Archivo | Seleccionado por | Elementos |
|--------|---------|------------------|-----------|
| **Público** | `layouts/public.blade.php` | Visitantes no autenticados | Topbar horizontal: Inicio · Canchas · Disponibilidad · Botones Login/Registro |
| **Autenticado (Sidebar)** | `layouts/app.blade.php` | Usuarios autenticados (todos los roles) | Sidebar izquierdo + Topbar con breadcrumbs + Avatar/dropdown de cuenta |

---

## 3. Flujo de Navegación por Rol

### 3.1 👤 ROL: CLIENTE

El cliente accede al sistema a través del layout **público** y, al autenticarse, cambia al layout con **sidebar**. Solo ve secciones de consulta y gestión personal.

#### Sidebar del Cliente

| Sección | Elemento del Menú | Ruta (name) | URL | Acciones |
|---------|-------------------|-------------|-----|----------|
| **Principal** | 🏠 Inicio | `inicio` | `/` | Ver página de bienvenida |
| | 🏟️ Canchas | `canchas.index` | `/canchas` | Ver catálogo de canchas disponibles |
| | 📅 Disponibilidad | `disponibilidad.index` | `/disponibilidad` | Consultar horarios libres por fecha |
| **Mis reservas** | ➕ Reservar cancha | `reservas.crear` | `/reservas/crear` | Seleccionar cancha, horario, balón y confirmar |
| | ✅ Mis reservas | `reservas.mis` | `/mis-reservas` | Ver listado de sus reservas (activas, pasadas, canceladas) |
| | ⚙️ Mi perfil | `perfil.edit` | `/perfil` | Editar nombre, email, teléfono, contraseña |

#### Flujo de Reserva del Cliente

```
Inicio → Canchas (ver catálogo)
  └─► Canchas/{id} (ver detalle de cancha)
        └─► Reservar cancha → Seleccionar fecha/horario
              └─► Seleccionar balón (opcional)
                    └─► Confirmar reserva → Mis reservas

Inicio → Disponibilidad (ver calendario)
  └─► Seleccionar fecha/hora → Reservar cancha

Chatbot (widget flotante) → Consulta rápida
```

#### Tabla de Acciones del Cliente

| Acción | Ruta HTTP | Ruta Name | Descripción | Requiere Auth |
|--------|-----------|-----------|-------------|---------------|
| Ver catálogo | `GET` | `canchas.index` | Lista de canchas con imágenes y precios | ❌ |
| Ver detalle cancha | `GET` | `canchas.show` | Información completa de una cancha | ❌ |
| Ver disponibilidad | `GET` | `disponibilidad.index` | Calendario de horarios libres/ocupados | ❌ |
| Consultar chatbot | `POST` | `chatbot.mensaje` | Enviar mensaje al chatbot | ❌ |
| Crear reserva | `GET` | `reservas.crear` | Formulario de nueva reserva | ✅ |
| Guardar reserva | `POST` | `reservas.store` | Registrar reserva en BD | ✅ |
| Ver mis reservas | `GET` | `reservas.mis` | Listado personal de reservas | ✅ |
| Cancelar reserva | `POST` | `reservas.cancelar` | Anular una reserva propia | ✅ |
| Ver perfil | `GET` | `perfil.edit` | Formulario de edición de perfil | ✅ |
| Actualizar perfil | `PUT` | `perfil.update` | Guardar cambios en perfil | ✅ |

---

### 3.2 📋 ROL: RECEPCIONISTA

La recepcionista opera desde el sidebar con acceso a gestión de reservas presenciales, consulta de disponibilidad y atención al cliente. **No** tiene acceso a configuración del sistema, reportes avanzados ni gestión de usuarios.

#### Sidebar de la Recepcionista

| Sección | Elemento del Menú | Ruta (name) | URL | Acciones |
|---------|-------------------|-------------|-----|----------|
| **Principal** | 🏠 Inicio | `inicio` | `/` | Ver página de bienvenida |
| | 🏟️ Canchas | `canchas.index` | `/canchas` | Consultar catálogo de canchas |
| | 📅 Disponibilidad | `disponibilidad.index` | `/disponibilidad` | Verificar disponibilidad en tiempo real |
| **Gestión de reservas** | ➕ Nueva reserva (presencial) | `recepcionista.reservas.create` | `/recepcionista/reservas/crear` | Crear reserva para un cliente que llega a la recepción |
| | 📋 Reservas del día | `recepcionista.reservas.hoy` | `/recepcionista/reservas/hoy` | Ver todas las reservas de la jornada actual |
| | 📋 Todas las reservas | `recepcionista.reservas.index` | `/recepcionista/reservas` | Listado completo con filtros por fecha/estado |
| | 🔍 Buscar cliente | `recepcionista.clientes.buscar` | `/recepcionista/clientes/buscar` | Buscar cliente por nombre, email o teléfono |
| **Atención** | ✅ Check-in | `recepcionista.checkin` | `/recepcionista/checkin` | Registrar llegada del cliente a la cancha |
| | 🏁 Check-out | `recepcionista.checkout` | `/recepcionista/checkout` | Registrar salida y calcular tiempo final |
| | 💬 Chatbot | `recepcionista.chatbot` | `/recepcionista/chatbot` | Responder consultas del chatbot en tiempo real |
| **Personal** | ⚙️ Mi perfil | `perfil.edit` | `/perfil` | Editar datos personales |

#### Flujo de Navegación de la Recepcionista

```
Inicio → Disponibilidad → Verificar cancha libre
  └─► Nueva reserva presencial → Buscar/crear cliente
        └─► Seleccionar cancha + horario + balón
              └─► Confirmar → Reservas del día

Inicio → Reservas del día → Seleccionar reserva
  └─► Check-in (cliente llega) → Registrar hora
        └─► Check-out (cliente sale) → Calcular monto

Inicio → Todas las reservas → Filtros
  └─► Ver detalle → Cambiar estado / Cancelar

Inicio → Chatbot → Responder mensajes de clientes
```

#### Tabla de Acciones de la Recepcionista

| Acción | Ruta HTTP | Ruta Name | Descripción | Requiere Auth |
|--------|-----------|-----------|-------------|---------------|
| Ver disponibilidad | `GET` | `disponibilidad.index` | Consultar horarios libres | ✅ |
| Crear reserva (presencial) | `GET` | `recepcionista.reservas.create` | Formulario nueva reserva | ✅ |
| Guardar reserva presencial | `POST` | `recepcionista.reservas.store` | Registrar reserva de cliente walk-in | ✅ |
| Ver reservas del día | `GET` | `recepcionista.reservas.hoy` | Listado filtrado por fecha actual | ✅ |
| Ver todas las reservas | `GET` | `recepcionista.reservas.index` | Listado con filtros avanzados | ✅ |
| Buscar cliente | `GET` | `recepcionista.clientes.buscar` | Autocomplete por datos del cliente | ✅ |
| Registrar check-in | `POST` | `recepcionista.checkin` | Marcar hora de llegada | ✅ |
| Registrar check-out | `POST` | `recepcionista.checkout` | Marcar hora de salida y calcular cobro | ✅ |
| Cambiar estado reserva | `POST` | `recepcionista.reservas.estado` | Confirmar / cancelar / reprogramar | ✅ |
| Responder chatbot | `POST` | `recepcionista.chatbot.responder` | Responder en nombre del chatbot | ✅ |

---

### 3.3 🔧 ROL: ADMINISTRADOR

El administrador tiene acceso **total** al sistema. Su sidebar contiene todas las secciones de gestión, configuración y reportes. Además, hereda todas las capacidades de la recepcionista.

#### Sidebar del Administrador

| Sección | Elemento del Menú | Ruta (name) | URL | Acciones |
|---------|-------------------|-------------|-----|----------|
| **Principal** | 🏠 Inicio | `inicio` | `/` | Ver página de bienvenida |
| | 🏟️ Canchas | `canchas.index` | `/canchas` | Consultar catálogo |
| | 📅 Disponibilidad | `disponibilidad.index` | `/disponibilidad` | Verificar disponibilidad |
| **Mis reservas** | ➕ Reservar cancha | `reservas.crear` | `/reservas/crear` | Crear reserva personal |
| | ✅ Mis reservas | `reservas.mis` | `/mis-reservas` | Ver reservas propias |
| | ⚙️ Mi perfil | `perfil.edit` | `/perfil` | Editar perfil |
| **Administración** | 📊 Dashboard | `admin.dashboard` | `/admin` | Métricas: reservas hoy, ingresos, ocupación |
| | 📅 Reservas | `admin.reservas.index` | `/admin/reservas` | CRUD completo de reservas + Calendario |
| | 🏟️ Canchas (admin) | `admin.canchas.index` | `/admin/canchas` | CRUD de canchas (nombre, precio, capacidad, imagen) |
| | 🕐 Horarios | `admin.horarios.index` | `/admin/horarios` | Configurar horarios de atención por cancha |
| | ⚽ Balones | `admin.balones.index` | `/admin/balones` | CRUD de balones (tipo, precio por hora, imagen) |
| | 👥 Usuarios | `admin.usuarios.index` | `/admin/usuarios` | CRUD de usuarios (asignar roles, activar/desactivar) |
| | 📈 Reportes | `admin.reportes.index` | `/admin/reportes` | Estadísticas + Exportar datos |
| | 💬 Chatbot | `admin.chatbot.conversaciones` | `/admin/chatbot` | Ver historial de conversaciones |
| | 🎨 Colores del sitio | `admin.ajustes.index` | `/admin/ajustes` | Personalizar colores del tema |

#### Flujo de Navegación del Administrador

```
Inicio
  │
  ├─► DASHBOARD ──► Ver métricas → Drill-down a detalle
  │
  ├─► RESERVAS ──► Lista/Calendario
  │     ├─► Ver detalle → Cambiar estado → Eliminar
  │     └─► Calendario → Eventos (API JSON)
  │
  ├─► CANCHAS ──► CRUD
  │     ├─► Crear → Asignar nombre, precio, capacidad, imagen
  │     ├─► Editar → Modificar datos
  │     └─► Eliminar → Confirmar
  │
  ├─► HORARIOS ──► Por cancha
  │     ├─► Crear horario → Día + hora inicio/fin
  │     ├─► Editar → Modificar rangos
  │     └─► Eliminar → Confirmar
  │
  ├─► BALONES ──► CRUD
  │     ├─► Crear → Tipo, precio/hora, imagen
  │     ├─► Editar → Modificar datos
  │     └─► Eliminar → Confirmar
  │
  ├─► USUARIOS ──► CRUD
  │     ├─► Crear → Nombre, email, contraseña, rol, estado
  │     ├─► Editar → Modificar datos/rol/estado
  │     └─► Eliminar → Confirmar
  │
  ├─► REPORTES ──► Filtros por fecha/estado
  │     └─► Exportar → Descargar datos (CSV/Excel)
  │
  ├─► CHATBOT ──► Historial de conversaciones
  │     └─► Ver conversación → Mensajes individuales
  │
  └─► AJUSTES ──► Colores del sitio
        └─► Modificar colores → Guardar
```

#### Tabla de Acciones del Administrador

| Acción | Ruta HTTP | Ruta Name | Descripción | Requiere Auth |
|--------|-----------|-----------|-------------|---------------|
| **Dashboard** | | | | |
| Ver dashboard | `GET` | `admin.dashboard` | Métricas generales del sistema | ✅ Admin |
| **Reservas (admin)** | | | | |
| Listar reservas | `GET` | `admin.reservas.index` | Listado con filtros y paginación | ✅ Admin |
| Ver calendario | `GET` | `admin.reservas.calendario` | Vista de calendario interactiva | ✅ Admin |
| Obtener eventos | `GET` | `admin.reservas.eventos` | API JSON de eventos para calendario | ✅ Admin |
| Cambiar estado | `POST` | `admin.reservas.estado` | Actualizar estado de una reserva | ✅ Admin |
| Eliminar reserva | `DELETE` | `admin.reservas.destroy` | Eliminar reserva permanentemente | ✅ Admin |
| **Canchas (admin)** | | | | |
| Listar canchas | `GET` | `admin.canchas.index` | Tabla de todas las canchas | ✅ Admin |
| Crear cancha | `GET/POST` | `admin.canchas.create/store` | Formulario + guardar | ✅ Admin |
| Editar cancha | `GET/PUT` | `admin.canchas.edit/update` | Formulario + actualizar | ✅ Admin |
| Eliminar cancha | `DELETE` | `admin.canchas.destroy` | Eliminar cancha | ✅ Admin |
| **Horarios** | | | | |
| Listar horarios | `GET` | `admin.horarios.index` | Horarios agrupados por cancha | ✅ Admin |
| Crear horario | `GET/POST` | `admin.horarios.create/store` | Asignar rango a cancha | ✅ Admin |
| Editar horario | `GET/PUT` | `admin.horarios.edit/update` | Modificar rango | ✅ Admin |
| Eliminar horario | `DELETE` | `admin.horarios.destroy` | Eliminar horario | ✅ Admin |
| **Balones** | | | | |
| Listar balones | `GET` | `admin.balones.index` | Tabla de balones | ✅ Admin |
| Crear balón | `GET/POST` | `admin.balones.create/store` | Formulario + guardar | ✅ Admin |
| Editar balón | `GET/PUT` | `admin.balones.edit/update` | Formulario + actualizar | ✅ Admin |
| Eliminar balón | `DELETE` | `admin.balones.destroy` | Eliminar balón | ✅ Admin |
| **Usuarios** | | | | |
| Listar usuarios | `GET` | `admin.usuarios.index` | Tabla de usuarios con roles | ✅ Admin |
| Crear usuario | `GET/POST` | `admin.usuarios.create/store` | Asignar nombre, email, rol | ✅ Admin |
| Editar usuario | `GET/PUT` | `admin.usuarios.edit/update` | Modificar datos/rol/estado | ✅ Admin |
| Eliminar usuario | `DELETE` | `admin.usuarios.destroy` | Eliminar usuario | ✅ Admin |
| **Reportes** | | | | |
| Ver reportes | `GET` | `admin.reportes.index` | Estadísticas de reservas | ✅ Admin |
| Exportar datos | `GET` | `admin.reportes.exportar` | Descargar CSV/Excel | ✅ Admin |
| **Chatbot (admin)** | | | | |
| Ver conversaciones | `GET` | `admin.chatbot.conversaciones` | Historial completo | ✅ Admin |
| Ver conversación | `GET` | `admin.chatbot.conversaciones.show` | Mensajes de una conversación | ✅ Admin |
| **Ajustes** | | | | |
| Ver ajustes | `GET` | `admin.ajustes.index` | Configuración de colores | ✅ Admin |
| Guardar ajustes | `PUT` | `admin.ajustes.update` | Aplicar colores del tema | ✅ Admin |

---

## 4. Matriz de Permisos por Rol

| Módulo | Página / Función | Cliente | Recepcionista | Admin |
|--------|------------------|:-------:|:-------------:|:-----:|
| **Público** | Inicio (`/`) | ✅ | ✅ | ✅ |
| | Catálogo canchas (`/canchas`) | ✅ | ✅ | ✅ |
| | Detalle cancha (`/canchas/{id}`) | ✅ | ✅ | ✅ |
| | Disponibilidad (`/disponibilidad`) | ✅ | ✅ | ✅ |
| | Chatbot widget (`POST /chatbot/mensaje`) | ✅ | ✅ | ✅ |
| **Autenticación** | Login | ✅ | ✅ | ✅ |
| | Registro | ✅ | ❌ | ❌ |
| | Mi perfil | ✅ | ✅ | ✅ |
| **Reservas propias** | Crear reserva | ✅ | — | ✅ |
| | Ver mis reservas | ✅ | — | ✅ |
| | Cancelar reserva propia | ✅ | — | ✅ |
| **Recepción** | Nueva reserva presencial | ❌ | ✅ | ✅ |
| | Reservas del día | ❌ | ✅ | ✅ |
| | Check-in / Check-out | ❌ | ✅ | ✅ |
| | Buscar cliente | ❌ | ✅ | ✅ |
| **Admin: Dashboard** | Métricas generales | ❌ | ❌ | ✅ |
| **Admin: Reservas** | CRUD completo + Calendario | ❌ | ❌ | ✅ |
| **Admin: Canchas** | CRUD canchas | ❌ | ❌ | ✅ |
| **Admin: Horarios** | CRUD horarios por cancha | ❌ | ❌ | ✅ |
| **Admin: Balones** | CRUD balones | ❌ | ❌ | ✅ |
| **Admin: Usuarios** | CRUD usuarios + roles | ❌ | ❌ | ✅ |
| **Admin: Reportes** | Estadísticas + Exportar | ❌ | ❌ | ✅ |
| **Admin: Chatbot** | Historial conversaciones | ❌ | ❌ | ✅ |
| **Admin: Ajustes** | Colores del sitio | ❌ | ❌ | ✅ |

---

## 5. Diagrama de Acceso por Rol

```
┌─────────────────────────────────────────────────────────────────────┐
│                        SISTEMA WALLY RESERVAS                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐          │
│  │   PÚBLICO     │    │   CLIENTE    │    │  RECEPCIONISTA│          │
│  │  (Sin auth)   │───►│  (auth)      │    │  (auth)       │          │
│  │              │    │              │    │              │          │
│  │  • Inicio    │    │  • Inicio    │    │  • Inicio    │          │
│  │  • Canchas   │    │  • Canchas   │    │  • Canchas   │          │
│  │  • Disponib. │    │  • Disponib. │    │  • Disponib. │          │
│  │  • Login     │    │  • Reservar  │    │  • Reservar  │          │
│  │  • Registro  │    │  • Mis reserv│    │    (presenal) │          │
│  │              │    │  • Mi perfil │    │  • Reservas   │          │
│  │  [Chatbot]   │    │  [Chatbot]   │    │    del día   │          │
│  └──────────────┘    └──────────────┘    │  • Check-in   │          │
│                                          │  • Check-out  │          │
│                                          │  • Buscar cli.│          │
│                                          │  • Chatbot    │          │
│                                          │  • Mi perfil  │          │
│                                          └──────┬───────┘          │
│                                                 │                   │
│                                                 ▼                   │
│                                          ┌──────────────┐          │
│                                          │    ADMIN      │          │
│                                          │  (auth+admin) │          │
│                                          │              │          │
│                                          │  Todo lo de  │          │
│                                          │  recepcionista│          │
│                                          │  +            │          │
│                                          │  • Dashboard  │          │
│                                          │  • Reservas ↑ │          │
│                                          │  • Canchas ↑  │          │
│                                          │  • Horarios ↑ │          │
│                                          │  • Balones ↑  │          │
│                                          │  • Usuarios ↑ │          │
│                                          │  • Reportes   │          │
│                                          │  • Chatbot ↑  │          │
│                                          │  • Colores ↑  │          │
│                                          └──────────────┘          │
│                                                                     │
│  ↑ = CRUD completo (Crear, Leer, Actualizar, Eliminar)             │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 6. Middleware de Protección por Ruta

| Middleware | Archivo | Ruta de verificación | Controla |
|------------|---------|---------------------|----------|
| `auth` | Laravel内置 | `app/Http/Middleware/Authenticate.php` | Exige sesión activa |
| `admin` | `app/Http/Middleware/EnsureUserIsAdmin.php` | Verifica `rol === 'admin'` | Excluye clientes y recepcionistas |
| `recepcionista` | `app/Http/Middleware/EnsureUserIsRecepcionista.php` | Verifica `rol === 'recepcionista'` o `'admin'` | Permite recepcionista + admin |

### Rutas por Middleware

| Grupo de Rutas | Middleware | Roles Permitidos |
|----------------|------------|------------------|
| `/` · `/canchas` · `/disponibilidad` | `Sin middleware` | Todos (público) |
| `/reservas/crear` · `/mis-reservas` · `/perfil` | `auth` | Todos autenticados |
| `/recepcionista/*` | `auth`, `recepcionista` | Recepcionista + Admin |
| `/admin/*` | `auth`, `admin` | Solo Admin |

---

## 7. Herencia de Permisos

```
Admin ⊃ Recepcionista ⊃ Cliente
```

- El **Admin** puede hacer todo lo que hace la Recepcionista y el Cliente.
- La **Recepcionista** puede hacer todo lo que hace el Cliente, más operaciones de recepción.
- El **Cliente** solo puede consultar y gestionar sus propias reservas.

---

*Documento generado para Wally Reservas — Estructura de navegación funcional.*
