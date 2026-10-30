# Prompts Detallados Mockups Sprint 1 — Copiar y Pegar en Figma/Excalidraw/IA

---

## 🖥️ S1_01 — Login (Desktop 1920×1080)

```
Wireframe low-fidelity login page for "Wally - Sistema de Reservas de Canchas".
Desktop 1920x1080, centered card layout, Bootstrap 5 inspired, grayscale wireframe style.

ELEMENTS (annotate with red numbered circles ①-⑨ + legend on right):
① Logo "Wally" top-center (placeholder rectangle 120x40px)
② White card centered (max-width 420px, padding 40px, border-radius 8px, subtle shadow)
③ Card title: "Iniciar Sesión" (H3, centered, margin-bottom 24px)
④ Email input: label "Correo electrónico", icon @ left, type=email, full width
⑤ Password input: label "Contraseña", icon 🔒 left, show/hide toggle right, type=password, full width
⑥ Checkbox row: [☐] "Recordarme" (left) + Link "¿Olvidaste tu contraseña?" (right, aligned)
⑦ Primary button: "Entrar" (full width, btn-primary style, margin-top 24px)
⑧ Divider: "o" centered with horizontal lines
⑨ Secondary button: "Continuar con Google" (full width, btn-outline, Google icon)
⑩ Footer links centered: "¿No tienes cuenta? Regístrate" (link to /register)
⑪ Copyright footer: "© 2026 Wally - Sistema de Reservas de Canchas"

SPACING: 24px between form fields, 16px between checkbox row and button
ANNOTATIONS: Red circles ①-⑪ with thin lines pointing to elements, legend box top-right
```

---

## 📱 S1_01 — Login (Mobile 375×667)

```
Same as desktop but mobile 375x667.
- Card full width (margin 16px, padding 24px)
- Stack all elements vertically
- Logo smaller (80x30px)
- Buttons full width, touch-friendly (min-height 48px)
- Inputs with 16px font-size (prevents zoom on iOS)
- Footer links stacked vertically
```

---

## 🖥️ S1_02 — Registro (Desktop 1920×1080)

```
Wireframe registration page for Wally.
Desktop 1920x1080, same card layout as login.

ELEMENTS (annotate ①-⑫):
① Logo "Wally" top-center
② Card "Crear Cuenta" (max-width 420px)
③ Title "Regístrate" (H3)
④ Nombre input: label "Nombre completo", icon 👤, required
⑤ Email input: label "Correo electrónico", icon @, type=email, required
⑥ Password input: label "Contraseña", icon 🔒, show/hide, min 8 chars, required
⑦ Confirm Password input: label "Confirmar contraseña", icon 🔒, show/hide, required
⑧ Checkbox: [☐] "Acepto los Términos y Condiciones" (link "Términos" opens modal)
⑨ Primary button: "Registrarse" (full width)
⑩ Divider "o"
⑪ Button: "Registrarse con Google" (Google icon)
⑫ Footer: "¿Ya tienes cuenta? Inicia sesión" (link to /login)
⑬ Copyright

VALIDATION NOTES (annotate with small red tags):
- Email: formato válido, único en BD
- Password: min 8 chars, 1 mayúscula, 1 número
- Confirm: debe coincidir
```

---

## 📱 S1_02 — Registro (Mobile 375×667)

```
Same as desktop, mobile adaptations:
- Card margin 16px, padding 24px
- Inputs stacked, full width
- Checkbox label wraps on 2 lines
- Buttons min-height 48px
```

---

## 🖥️ S1_03 — Recuperar Contraseña (Desktop)

```
Wireframe "Recuperar contraseña" page.
Desktop, centered card (max-width 420px).

ELEMENTS (①-⑥):
① Logo
② Card title "Recuperar Contraseña"
③ Text: "Ingresa tu email y te enviaremos un enlace para restablecer tu contraseña."
④ Email input: label "Correo electrónico", icon @
⑤ Primary button: "Enviar enlace de recuperación"
⑥ Link: "Volver al inicio de sesión" (← /login)
```

---

## 🖥️ S1_04 — Dashboard Admin (Desktop)

```
Wireframe Admin Dashboard for Wally.
Desktop 1920x1080, Bootstrap 5 admin layout.

LAYOUT:
- Left Sidebar (260px fixed): Logo + Navigation
  ① Logo "Wally Admin" 
  ② Nav: Dashboard (active), Canchas, Horarios, Reservas, Usuarios, Configuración, Chatbot, Reportes, Google Calendar
  ③ Bottom: User avatar dropdown (Perfil, Cerrar sesión)
- Top Header (remaining width): 
  ④ Breadcrumb: "Inicio / Dashboard"
  ⑤ Search bar (global search)
  ⑥ Notifications bell icon (badge count)
  ⑦ User avatar + name + rol badge "Administrador"
- Main Content (grid 12 cols):
  ⑧ Stats Row (4 cards, col-3 each):
     - Card "Usuarios Totales" (icon 👥, number, trend ↑)
     - Card "Canchas Activas" (icon 🏟️, number)
     - Card "Reservas Hoy" (icon 📅, number, badge "pendientes: X")
     - Card "Ingresos Mes" (icon 💰, number, trend ↑)
  ⑨ Quick Actions Row (4 buttons, col-3):
     - "Nueva Cancha" → /admin/canchas/create
     - "Nuevo Horario" → /admin/horarios/create
     - "Nuevo Usuario" → /admin/usuarios/create
     - "Ver Reportes" → /admin/reportes
  ⑩ Recent Activity Feed (col-8): List of last 10 actions (user, action, time)
  ⑪ Upcoming Reservations (col-4): Next 5 reservations (cancha, hora, cliente, estado)
```

---

## 📱 S1_04 — Dashboard Admin (Mobile)

```
Mobile 375x667:
- Hamburger menu ☰ opens sidebar drawer
- Header: Logo + hamburger + notifications + avatar
- Stats cards: stacked vertical (full width)
- Quick actions: 2x2 grid
- Activity feed: stacked cards
```

---

## 🖥️ S1_05 — Dashboard Recepcionista (Desktop)

```
Wireframe Recepcionista Dashboard.
Desktop, same sidebar layout.

SIDEBAR NAV:
① Dashboard (active), Reservas Hoy, Nueva Reserva, Clientes, Check-in/out, Perfil

HEADER:
② Breadcrumb "Recepción / Hoy"
③ Date picker (hoy por defecto)
④ Notifications + Avatar (rol "Recepcionista")

MAIN CONTENT:
⑤ Tabs: "Todas" | "Pendientes" | "Confirmadas" | "En Curso" | "Finalizadas"
⑥ Reservations Table (col-12):
   Columns: Hora | Cancha | Cliente | Teléfono | Estado | Acciones
   Row actions: [Ver] [Check-in] [Check-out] [Cancelar] (conditional)
⑦ Empty state: "No hay reservas para hoy" + botón "Crear reserva walk-in"
⑧ Floating Action Button (bottom-right): "+ Nueva Reserva" → /recepcionista/reservas/create
```

---

## 🖥️ S1_06 — Dashboard Cliente (Desktop + Mobile)

```
Wireframe Client Dashboard (Mobile-first, responsive).
Uses bottom navigation on mobile, sidebar on desktop.

DESKTOP (sidebar layout):
- Sidebar: Inicio, Canchas, Mis Reservas, Perfil
- Main: Hero "¡Bienvenido, [Nombre]!" + Próxima reserva card (cancha, fecha, hora, estado, [Cancelar])
- Quick actions: [Reservar Ahora] [Ver Canchas] [Ver Disponibilidad]
- Canchas favoritas carousel

MOBILE (bottom nav):
- Bottom nav fixed: 🏠 Inicio | 🏟️ Canchas | 📅 Reservas | 👤 Perfil
- Screen "Inicio": Hero + Próxima reserva card (swipeable) + Quick actions (2x2 grid) + Favoritas
```

---

## 🖥️ S1_07 — Perfil Usuario (Desktop)

```
Wireframe Profile Edit page.
Desktop, two-column layout (col-4 sidebar + col-8 form).

LEFT SIDEBAR (col-4):
① Avatar circle (120px) + "Cambiar foto" link
② Nombre completo
③ Email
④ Rol badge (color: admin=rojo, recepcionista=azul, cliente=verde)
⑤ Menu: Datos Personales (active), Contraseña, Seguridad, Eliminar Cuenta

RIGHT FORM (col-8):
- Section "Datos Personales":
  ⑥ Nombre input
  ⑦ Email input (disabled if email verified)
  ⑧ Teléfono input (opcional)
  ⑨ Botón "Guardar cambios" (primary)
- Section "Contraseña" (separate tab):
  ⑩ Contraseña actual
  ⑪ Nueva contraseña
  ⑫ Confirmar nueva
  ⑬ Botón "Actualizar contraseña"
- Section "Eliminar Cuenta" (danger zone):
  ⑭ Texto advertencia
  ⑮ Input "Escribe 'ELIMINAR' para confirmar"
  ⑯ Botón "Eliminar mi cuenta" (btn-danger, requires modal confirmation)
```

---

## 🖥️ S1_08 — Reset Password Form (Desktop)

```
Wireframe "Establecer Nueva Contraseña" (token válido).
Desktop, centered card.

ELEMENTS:
① Logo
② Card title "Nueva Contraseña"
③ Text: "Tu enlace de recuperación es válido. Ingresa tu nueva contraseña."
④ Password input: "Nueva contraseña" + strength meter (weak/medium/strong)
⑤ Confirm Password input: "Confirmar nueva contraseña"
⑥ Checkbox: [☐] "Cerrar sesión en todos los dispositivos"
⑦ Primary button: "Actualizar contraseña"
⑧ Link: "Volver al inicio de sesión"
```

---

## 🎨 Estilo Visual Común (aplicar a todos)

```
COLORS (grayscale wireframe):
- Background: #F8F9FA
- Card Background: #FFFFFF
- Primary Border: #DEE2E6
- Primary Text: #212529
- Secondary Text: #6C757D
- Annotation Red: #E74C3C (solo círculos y líneas de anotación)
- Primary Button (wireframe): Outline only, label "PRIMARY"
- Secondary Button: Outline only, label "SECONDARY"

TYPOGRAPHY:
- Font: System UI / Inter / Roboto
- H1: 28px, H2: 24px, H3: 20px, Body: 16px, Small: 14px
- Labels: 14px, medium weight

COMPONENTS:
- Inputs: 40px height, 8px border-radius, 1px border, 12px padding
- Buttons: 44px height (mobile 48px), 8px border-radius, font-weight 500
- Cards: 8px border-radius, shadow: 0 2px 8px rgba(0,0,0,0.08)
- Spacing scale: 4, 8, 16, 24, 32, 48px
```