# Prompts Detallados Mockups Sprint 3

---

## 🖥️ S3_01 — Dashboard Admin KPIs (Desktop)

```
Wireframe Admin Dashboard with charts and KPIs.
Desktop 1920x1080, admin layout with sidebar.

HEADER: Standard admin header (breadcrumb "Dashboard", search, notifications, avatar)

MAIN CONTENT (grid):
ROW 1 - STATS CARDS (4 cards, col-3 each):
① "Ocupación Promedio" — Large number "68%" + trend "↑ 5% vs mes anterior" + sparkline mini-chart
   Subtitle: "Últimos 30 días"
② "Ingresos Mes Actual" — "$12,450" + trend "↑ 12%" + sparkline
   Subtitle: "vs mes anterior: $11,120"
③ "Cancelaciones" — "23" (15%) + trend "↓ 3%"
   Subtitle: "Tasa: 15% del total"
④ "Clientes Activos" — "156" + trend "↑ 8"
   Subtitle: "Nuevos este mes: 12"

ROW 2 - CHARTS (col-8 + col-4):
⑤ "Ocupación Semanal" (Line/Bar Chart, col-8):
   - X: Días (Lun-Dom) | Y: % Ocupación
   - Series: Semana actual (solid), Semana anterior (dashed), Promedio 4 sem (dotted)
   - Tooltip: Día, % ocupación, reservas/horas totales
   - Legend + Zoom/Reset buttons
⑥ "Top 5 Canchas por Ingresos" (Horizontal Bar Chart, col-4):
   - Bars: Cancha 1 ($3,200), Cancha 2 ($2,800), Cancha 3 ($2,100), Cancha 4 ($1,900), Cancha 5 ($1,500)
   - Color: Primary blue gradient
   - Click bar → filter reportes by cancha

ROW 3 - RECENT ACTIVITY (col-12):
⑦ "Reservas Recientes" (Table, last 10):
   Columns: Fecha | Hora | Cancha | Cliente | Estado | Monto | Origen
   Origen badges: [Web] [Recepción] [Chatbot] [Google Cal]
   Row click → modal detail

RESPONSIVE: Mobile → cards stacked, charts full width stacked, table horizontal scroll
```

---

## 🖥️ S3_02 — Configuración Reservas (Desktop)

```
Wireframe reservation policies configuration.
Desktop, centered form (max-width 900px), card-based sections.

HEADER: "Configuración de Políticas de Reserva" + "Última actualización: DD/MM/YYYY HH:MM"

SECTION 1 - DURACIONES PERMITIDAS:
① Checklist con duraciones (minutos): [☑] 60  [☑] 90  [☑] 120  [☐] 150  [☐] 180
   Help text: "Duraciones que el cliente puede elegir. Mínimo 1 seleccionada."

SECTION 2 - VENTANAS TEMPORALES:
② Number input: "Anticipación mínima (horas)" — default 2, min 0, max 168
   Help: "Cuántas horas antes como mínimo se puede reservar"
③ Number input: "Anticipación máxima (días)" — default 30, min 1, max 365
   Help: "Hasta cuántos días en el futuro se puede reservar"
④ Number input: "Ventana de cancelación (horas)" — default 4, min 0, max 168
   Help: "Horas antes del inicio para cancelar sin penalización"
⑤ Number input: "Check-in permitido antes (minutos)" — default 15, min 0, max 60

SECTION 3 - REGLAS OPERATIVAS:
⑥ Toggle: "Permitir reservas solapadas en distintas canchas" — ON
⑦ Toggle: "Bloquear balón premium si no hay stock" — ON
⑧ Select: "Comportamiento conflicto horario" — [Rechazar nueva] [Permitir lista espera]

SECTION 4 - NOTIFICACIONES AUTOMÁTICAS:
⑨ Toggle: "Recordatorio 24h antes" — ON
⑩ Toggle: "Recordatorio 1h antes" — ON
⑪ Toggle: "Notificar check-in a recepción" — ON

ACTIONS:
⑫ Button: "Revisar Cambios" (primary) → navega a S3_03
⑬ Button: "Cancelar" (secondary) → vuelve a dashboard
⑭ Button: "Restaurar Valores Por Defecto" (ghost, danger zone)
```

---

## 🖥️ S3_03 — Revisar Impacto Cambios (Desktop)

```
Wireframe review impact before applying config changes.
Desktop, full-width layout.

HEADER: "Revisar Impacto de Cambios" + Badge "X reservas afectadas" (orange)
Subtitle: "Los siguientes cambios afectarán reservas existentes:"

IMPACT SUMMARY CARDS (3 cards):
① "Reprogramadas Automáticamente" — "12" (blue)
② "Canceladas por Complejo" — "3" (red)
③ "Solo Notificadas" — "5" (yellow)

DETAIL TABLE (col-12):
Columns: Reserva | Cliente | Cancha | Fecha | Hora | Estado Actual | Nueva Política | Acción Propuesta | Detalle
Row example:
- #145 | Juan Pérez | Cancha 1 | 15/10/2026 | 18:00-19:30 | Confirmada | Duración 90min no permitida | Reprogramar | "Duración 90min → cambiar a 60 o 120min"
- #146 | María López | Cancha 2 | 16/10/2026 | 20:00-21:00 | Pendiente | Horario fuera de ventana | Cancelar | "Anticipación máxima 30d → reserva a 45d"

ROW COLORS:
- Reprogramar: blue left border
- Cancelar: red left border
- Notificar: yellow left border

ACTIONS BAR (sticky bottom):
⑫ Button: "Aplicar Cambios" (primary, confirms modal: "¿Confirmar? Se enviarán notificaciones a X clientes")
⑬ Button: "Volver a Configuración" (secondary)
⑭ Button: "Exportar Detalle (CSV)" (ghost)
```

---

## 🖥️ S3_04 — Google Calendar Connect (Desktop)

```
Wireframe Google Calendar integration status.
Desktop, card layout.

CARD 1 - CONNECTION STATUS:
① Status Badge: 🟢 Conectado / 🔴 Desconectado / 🟡 Expirado
② Calendar ID: "primary" (editable if disconnected)
③ Connected Account: "deporteswally@gmail.com" (if connected)
④ Last Sync: "Hace 5 min" (relative time) + status icon ✅/❌
⑤ Next Scheduled Sync: "En 10 min" (cron job)

CARD 2 - ACTIONS:
⑥ If Disconnected: Button "Conectar Google Calendar" (primary, Google brand) → OAuth flow
   If Connected: Button "Desconectar" (danger, requires confirmation)
⑦ Button: "Sincronizar Ahora" (secondary) → triggers SyncReservationToGoogleCalendar job
⑧ Button: "Ver Logs de Sincronización" → modal/table

CARD 3 - RECENT SYNC LOG (Table, last 20):
Columns: Fecha | Reserva | Acción (Crear/Actualizar/Eliminar) | Estado | Error | Duración
Row states: ✅ Synced (green), ❌ Error (red, hover shows error), ⏳ Pending (yellow)
```

---

## 🖥️ S3_05 — Reportes (Desktop)

```
Wireframe reports generator.
Desktop, sidebar filters + main results.

LEFT SIDEBAR (col-3, sticky):
① Report Selector (radio cards):
   📊 Ocupación por Cancha  (selected)
   💰 Ingresos por Período
   ❌ Cancelaciones
   👥 Top Clientes
② Date Range: [Fecha Inicio] [Fecha Fin] + Presets: [Esta Semana] [Este Mes] [Últ. 30d] [Año Actual]
③ Cancha Filter: Multi-select dropdown (Todas / Cancha 1 / Cancha 2...)
④ Estado Filter: Multi-select (Confirmada, Completada, Cancelada, Todas)
⑤ Button: "Generar Reporte" (primary, full width)
⑥ Button: "Programar Envío Email" (ghost) → modal schedule

MAIN AREA (col-9):
⑦ Chart Preview (responsive chart based on report type)
   - Ocupación: Stacked bar (canchas × semanas)
   - Ingresos: Line chart (daily) + bar (monthly)
   - Cancelaciones: Pie chart (motivos) + trend line
⑧ Results Table (paginated, sortable):
   Columns dynamic per report
   - Ocupación: Cancha | Horas Totales | Horas Ocupadas | % Ocupación | Ingresos
   - Ingresos: Fecha | Cancha | Reservas | Ingreso Bruto | Ingreso Neto
   - Cancelaciones: Motivo | Cantidad | % Total | Ingreso Perdido
⑨ Export Buttons: [Excel .xlsx] [PDF] [CSV] [Imprimir]
⑩ Pagination: ‹ 1 2 3 › | Filas por página: [25 ▼]
```

---

## 🖥️ S3_06 — Cierres Cancha (Desktop)

```
Wireframe court closures management.
Desktop, split view: Calendar left (col-8) + List right (col-4).

LEFT - CALENDAR VIEW:
① Month header with ‹ Mes Anterior | Oct 2026 | Mes Siguiente ›
② Calendar grid: Days with closures highlighted
   - Closure badge: "🔴 Mantenimiento Cancha 1 (09:00-18:00)"
   - Recurring: "🔄 Todos los lunes: Cancha 2 cerrada"
   - Click day → shows closures list for that day in right panel
③ Legend: 🔴 Mantenimiento  🟡 Evento Privado  🔄 Recurrente

RIGHT - LIST / FORM:
④ "Cierres Programados" (List):
   Each row: Fecha | Cancha | Tipo | Motivo | Recurrente | Acciones [Editar] [Eliminar]
⑤ Button: "+ Nuevo Cierre" → Modal Form

MODAL NUEVO CIERRE:
⑥ Cancha (select, required)
⑦ Tipo: [Mantenimiento] [Evento Privado] [Otro]
⑧ Fecha Inicio + Fecha Fin (date pickers)
⑨ Checkbox: [☐] Recurrente → shows: Día semana [Lunes▼] + Hasta fecha [Date]
⑩ Motivo (textarea)
⑪ Preview: "Este cierre afectará X reservas programadas" (calculado en vivo)
⑫ [Cancelar] [Guardar Cierre]
```

---

## 🎨 Estilo Visual Sprint 3

```
CHARTS (Chart.js v4):
- Colors: Primary #2E86DE, Success #27AE60, Warning #F39C12, Danger #E74C3C, Info #17A2B8
- Grid: Light gray #E9ECEF
- Font: 12px axis, 11px tooltip
- Responsive: maintainAspectRatio false
- Animation: duration 750ms, easing easeOutQuart

TABLES:
- Sticky header
- Row hover: #F8F9FA
- Sortable columns: cursor pointer, sort icons
- Pagination: 25/50/100 rows

MODALS:
- Sizes: sm (400px), md (600px), lg (800px), xl (1000px)
- Backdrop: rgba(0,0,0,0.5) + blur(2px)
- Focus trap, ESC to close
```