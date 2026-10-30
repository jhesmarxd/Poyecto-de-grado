# Prompts Detallados Mockups Sprint 2 — Copiar y Pegar

---

## 🖥️ S2_01 — Listado Canchas Público (Desktop)

```
Wireframe public courts listing for Wally.
Desktop 1920x1080, responsive grid.

LAYOUT:
- Header: Logo Wally + Nav (Canchas, Horarios, Login/Registro)
- Hero section (optional): "Encuentra tu cancha ideal" + search bar
- Main content:
  ① Filter Bar (sticky top on scroll):
     - Date picker: "Fecha" (default hoy)
     - Select: "Tipo cancha" (Todas, Fútbol 5, Fútbol 7, Pádel, Tenis)
     - Select: "Recinto" (Todos, Abierta, Cerrada)
     - Button: "Filtrar" (primary)
  ② Courts Grid (responsive: 4 cols desktop, 2 tablet, 1 mobile):
     Each Court Card (aspect-ratio 4:3):
     ③ Image placeholder (16:9, object-fit cover, skeleton loader)
     ④ Badge: "Disponible" (green) / "Mantenimiento" (orange) / "Inactiva" (red)
     ⑤ Name (H4, truncate 2 lines)
     ⑥ Location icon + address (1 line, truncate)
     ⑦ Price: "Desde $XX/hora" (prominent, bold)
     ⑧ Tags: [Fútbol 5] [Cerrada] [Césped sintético]
     ⑨ Button: "Ver detalle" → /canchas/{id} (primary, full width card bottom)
  ③ Pagination: ‹ Anterior 1 2 3 Siguiente ›

ANNOTATIONS: Red circles ①-⑨
```

---

## 🖥️ S2_02 — Detalle Cancha (Desktop)

```
Wireframe court detail page.
Desktop, two-column layout (col-8 main + col-4 sidebar).

MAIN COLUMN (col-8):
① Image Gallery: Main image (16:9) + thumbnails strip (4-6 images)
② Name + Location (icon 📍)
③ Description (markdown rendered)
④ Specs Grid (2x2 cards):
   - Tipo piso | Altura malla | Color malla | Tipo recinto
⑤ Horarios Semanales (Accordion per day):
   Day header: "Lunes" + toggle
   Expanded: List of time slots (Hora inicio - Hora fin) + [Reservar] button each
   Disabled slots: grayed out + "No disponible" / "Mantenimiento"
⑥ Map: Static map placeholder (400x300) + "Ver en Google Maps" link

SIDEBAR (col-4, sticky):
⑦ Price Card: "Desde $XX/hora" + "Reservar ahora" button (primary, full width)
⑧ Availability Summary: "Próximos 7 días: X horarios libres"
⑨ Quick Actions: [Compartir] [Favorito] [Reportar]
⑩ Reviews Summary (si existe): ⭐ 4.8 (12 reseñas) + [Ver todas]

STATE: Si cancha en mantenimiento → Banner superior "⚠️ En mantenimiento hasta DD/MM"
```

---

## 🖥️ S2_03 — Crear Reserva (Desktop)

```
Wireframe reservation creation form.
Desktop, centered container (max-width 720px).

HEADER:
① Breadcrumb: Inicio / Canchas / {Nombre Cancha} / Reservar
② Progress steps: 1. Detalles ✓  2. Balón  3. Confirmación

MAIN FORM:
③ Reservation Summary Card (read-only, gray background):
   - Cancha: {Nombre} | Fecha: {DD/MM/YYYY} | Horario: {HH:MM} - {HH:MM}
   - Duración: {X} min | Precio base: ${XX}/hora
④ Ball Selector (radio group, required):
   ○ Balón propio (traes tu balón) — Precio: ${base}/hora
   ○ Balón prestado (económico) — Precio: ${prestado}/hora
       ↳ Select balón específico (dropdown con balones tipo 'estandar' disponibles)
   ○ Balón premium — Precio: ${premium}/hora
       ↳ Select balón específico (dropdown con balones tipo 'premium' disponibles)
   Note: Balones ocupados en esta fecha/hora → mostrar badge "⛔ Ocupado" y disabled
⑤ Observaciones (textarea, 3 rows, placeholder: "Notas para la recepción...")
⑥ Price Breakdown (card):
   - Precio cancha/hora: ${X}
   - Balón/hora: ${Y} (o "Incluido" si propio)
   - Duración: {Z} horas
   ─────────────────
   TOTAL: ${TOTAL} (prominent, large)
⑦ Checkbox: [☐] Acepto términos y condiciones de reserva
⑧ Actions: [Cancelar] (secondary)  [Confirmar Reserva] (primary, loading state)

VALIDATION:
- Si balón prestado/premium seleccionado → validar stock > 0 y no ocupado
- Botón disabled hasta completar requeridos
```

---

## 🖥️ S2_04 — Mis Reservas Calendario (Desktop)

```
Wireframe "Mis Reservas" calendar view.
Desktop, FullCalendar integration.

LAYOUT:
① Header: "Mis Reservas" + View Toggle: [Calendario] [Lista] (pills)
② Toolbar: ‹ Mes anterior | Mes Actual | Mes siguiente › + [Hoy] button
③ Calendar (FullCalendar month view):
   - Events colored by estado:
     🟡 Amarillo = Pendiente
     🟢 Verde = Confirmada
     🔴 Rojo = Cancelada
     ⚫ Gris = Completada
     🟠 Naranja = Afectada
     🔵 Azul = Reprogramada
   - Event title: "{Hora} - {Cancha}" (truncated)
   - Click event → Modal Detail (see below)
④ Sticky Top Card (Próxima Reserva Confirmada):
   - "Próxima: {Cancha} - {Fecha} {Hora}" + Badge estado
   - [Ver detalle] [Cancelar] buttons
⑤ Empty State (si sin reservas): "No tienes reservas aún" + [Reservar ahora] button

MODAL EVENT DETAIL (on click):
- Header: Cancha + Fecha + Horario + Badge estado
- Body: Duración, Balón, Monto, Observaciones, Check-in/out times
- Footer: [Cerrar] + [Cancelar] (si cancelable) + [Ver en calendario]
```

---

## 🖥️ S2_06 — Recepcionista Reservas Hoy (Desktop)

```
Wireframe receptionist today view.
Desktop, full-width table.

HEADER:
① Title: "Reservas de Hoy - {DD/MM/YYYY}" + Date Picker (change day)
② Badge counts: Total | Pendientes | Confirmadas | En Curso | Finalizadas
③ Button: "+ Nueva Reserva Walk-in" (primary, opens modal S2_07)
④ Filter: Select Cancha (Todas / Cancha 1 / Cancha 2...)

TABLE (responsive, horizontal scroll on mobile):
Columns: Hora | Cancha | Cliente | Teléfono | Estado | Balón | Check-in | Check-out | Acciones
Row data:
- Hora: "18:00 - 19:30"
- Cancha: "Cancha 1"
- Cliente: "Juan Pérez" (link to perfil)
- Teléfono: "+591 7XXXXXXX" (click to call)
- Estado: Badge colored (Pendiente=🟡, Confirmada=🟢, En Curso=🔵, Finalizada=⚫)
- Balón: "Propio" / "Prestado: Nike Strike" / "Premium: Adidas Finale"
- Check-in: "—" or "18:05" (green check)
- Check-out: "—" or "19:28" (green check)
- Acciones (dropdown):
   - [Ver Detalle]
   - [Check-in] (si confirmada + hoy + sin check-in)
   - [Check-out] (si en curso)
   - [Cancelar] (si pendiente/confirmada)
   - [Reprogramar]

ROW STYLES:
- En Curso: row background light blue
- Finalizada: row opacity 0.6
- Pendiente sin check-in y hora pasada: row background light yellow
```

---

## 🖥️ S2_07 — Nueva Reserva Walk-in (Modal/Desktop)

```
Wireframe modal "Nueva Reserva Walk-in".
Desktop, large modal (max-width 800px).

STEP 1 - Buscar/Seleccionar Cliente:
① Search: "Buscar cliente (nombre, email, teléfono)" + debounce 300ms
② Results: List cards (Nombre, Email, Tel, [Seleccionar])
③ OR "Cliente nuevo" → inline form (Nombre, Email, Tel) + [Crear y continuar]

STEP 2 - Datos Reserva (después de seleccionar cliente):
④ Cancha selector (dropdown con canchas disponibles)
⑤ Fecha (datepicker, default hoy, min hoy)
⑥ Horario selector (radio buttons por franjas libres de esa cancha/fecha)
⑦ Balón selector (same as S2_03)
⑧ Observaciones
⑨ Price breakdown + Total
⑩ Actions: [← Volver] [Crear Reserva] (primary)

SUCCESS: Toast "Reserva #123 creada para Juan Pérez" + redirect to S2_06
```

---

## 🖥️ S2_08 — Check-in (Desktop)

```
Wireframe check-in action.
From S2_06 table, click [Check-in] → Modal confirmation.

MODAL:
① Title: "Registrar Entrada - Reserva #{id}"
② Info: Cancha, Cliente, Hora programada, Hora actual
③ Warning: "La hora actual es {HH:MM}. La reserva era a {HH:MM}."
④ Checkbox: [☐] Confirmo que el cliente está presente
⑤ Actions: [Cancelar] [Registrar Entrada] (primary)
SUCCESS: Toast "✅ Check-in registrado a las {HH:MM}" + row updates in table
```

---

## 🖥️ S2_10 — Disponibilidad Pública Calendario (Desktop)

```
Wireframe public availability heatmap.
Desktop, matrix Canchas × Horarios.

HEADER:
① Title: "Disponibilidad - {Fecha}" + Date Picker
② Legend: 🟢 Libre  🔴 Ocupado  ⚫ No opera  🟡 Mantenimiento

MATRIX (table):
Rows: Horarios (08:00-09:00, 09:00-10:00, ... 22:00-23:00)
Columns: Cancha 1 | Cancha 2 | Cancha 3 | ...
Cells:
- Clickable if libre → link /reservas/crear?cancha_id=X&horario_id=Y&fecha=Z
- Hover tooltip: "Cancha X - {Hora} - Libre/Ocupado"
- Occupied: shows "Reservado" + tooltip with cliente (si admin) / "Ocupado" (si público)
- Maintenance: "⛔ Mantenimiento"
- Outside hours: "—" (gray)

RESPONSIVE: Horizontal scroll on mobile, sticky first column (horarios)
```

---

## 🎨 Estilo Visual Común Sprint 2

```
EXTENDS Sprint 1 styles +:
- Badge colors: 
  .bg-pendiente { background: #FFC107; color: #212529; }
  .bg-confirmada { background: #198754; }
  .bg-cancelada { background: #DC3545; }
  .bg-completada { background: #6C757D; }
  .bg-afectada { background: #FD7E14; color: #212529; }
  .bg-reprogramada { background: #0DCAF0; color: #212529; }
  .bg-en-curso { background: #0DCAF0; color: #212529; }

- Table: hover row highlight #F8F9FA, sticky header
- Modal: backdrop blur, centered, max-height 90vh, scrollable body
- Date picker: flatpickr style / native input[type=date]
- Select: Choices.js / native select with search
- Loading states: skeleton loaders for cards/grids
```