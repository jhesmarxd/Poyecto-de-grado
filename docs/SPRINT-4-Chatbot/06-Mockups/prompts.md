# Prompts Detallados Mockups Sprint 4

---

## 🖥️ S4_01 — Widget Cerrado (Desktop + Mobile)

```
Wireframe chatbot widget closed state (FAB).
Desktop: Bottom-right fixed, 60x60px circular button.
Mobile: Same, bottom-right 20px from edges.

ELEMENTS:
① FAB: Circular, primary blue background (#2E86DE), white icon (chat bubble / Wally logo)
   - Box-shadow: 0 4px 12px rgba(46,134,222,0.4)
   - Hover: scale(1.05), shadow 0 6px 16px
   - Focus: outline 2px solid #2E86DE, offset 2px
② Tooltip on hover (desktop): "¿Necesitas ayuda?" (appears left of FAB)
③ Notification Badge (optional): Red circle top-right of FAB with count "3"
   - Only shows if unread messages from previous session

MOBILE: Safe area inset (avoid home indicator), 56x56px min touch target
```

---

## 🖥️ S4_02 — Widget Abierto (Desktop)

```
Wireframe chatbot panel open.
Desktop: 380px wide × 550px tall, fixed bottom-right, z-index 1000.
Mobile: Full-screen overlay (100vw × 100vh), slide-up animation.

PANEL STRUCTURE:
HEADER (60px, sticky top):
① Logo "Wally" + "Asistente Virtual"
② Status badge: 🟢 En línea / 🟡 Ocupado / 🔴 Desconectado
③ Actions: [Minimizar _] [Cerrar ✕] (icon buttons)

MESSAGES AREA (flex: 1, overflow-y auto, padding 16px):
④ Message Bubbles (max-width 80%):
   - User (right-aligned): Blue background #2E86DE, white text, rounded 18px 18px 0 18px
   - Assistant (left-aligned): Gray #F1F3F4, dark text, rounded 18px 18px 18px 0
   - System/Human (left, distinct): Green border-left 3px #27AE60
   - Each bubble: Timestamp (11px, gray, right/bottom), Intent badge (small, if assistant)
   - Quick Replies: Chips below assistant message (max 3, horizontal scroll)
   - Buttons: Full-width action buttons (primary/secondary) below bubbles

TYPING INDICATOR (when assistant processing):
⑤ Three bouncing dots "Wally está escribiendo..." (left-aligned, gray bubble)

INPUT AREA (sticky bottom, 80px):
⑥ Textarea: Auto-grow (min 44px, max 120px), placeholder "Escribe un mensaje..."
   - Left: [📎 Adjuntar] (future) | Right: [🎤 Voz] [Enviar ▶] (primary, disabled if empty)
   - Enter = Send, Shift+Enter = New line
⑦ Character counter: "120/500" (right, small, gray)

EMPTY STATE (no messages):
- Centered illustration + "¡Hola! ¿En qué te ayudo?" + Quick start chips:
  [🏟️ Ver canchas] [📅 Reservar] [❓ FAQ] [👤 Humano]

ANNOTATIONS: Red circles ①-⑦
```

---

## 🖥️ S4_03 — Flujo Reserva por Chat (Desktop)

```
Wireframe conversation flow: User makes reservation via chat.
Show 8-message sequence in widget panel (desktop width).

MESSAGE 1 (User): "Quiero reservar fútbol 5 mañana a las 18:00"
   - Intent badge: 🎯 crear_reserva (92%)
   - Entities badge: 📅 mañana, ⏰ 18:00, 🏟️ fútbol 5

MESSAGE 2 (Assistant): "¡Claro! Tengo varias canchas de fútbol 5 para mañana 15/10 a las 18:00:"
   - Quick Replies (chips): [Cancha 1 - $30/h] [Cancha 2 - $25/h] [Cancha 3 - $35/h]
   - Button: "Ver todas las opciones →"

MESSAGE 3 (User clicks "Cancha 1 - $30/h")
   - Entity extracted: cancha_id=1

MESSAGE 4 (Assistant): "Perfecto, Cancha 1 (Fútbol 5, Cerrada, Césped sintético) a las 18:00-19:30 (90 min).
   ¿Qué balón prefieres?"
   - Quick Replies: [⚽ Balón propio - $30/h] [🏐 Prestado: Nike Strike - $35/h] [⭐ Premium: Adidas Finale - $45/h]

MESSAGE 5 (User clicks "Premium: Adidas Finale")
   - Entity: balon_id=3, tipo=premium

MESSAGE 6 (Assistant): "Resumen de tu reserva:
   🏟️ Cancha 1 (Fútbol 5)
   📅 Mañana 15/10/2026
   ⏰ 18:00 - 19:30 (90 min)
   ⭐ Balón Premium: Adidas Finale
   💰 Total: $45
   
   ¿Confirmo la reserva?"
   - Action Buttons: [✅ Confirmar] [❌ Cancelar] [✏️ Cambiar datos]

MESSAGE 7 (User clicks "Confirmar")
   - Loading spinner in button

MESSAGE 8 (Assistant): "✅ ¡Reserva #1234 confirmada!
   📋 Detalles: Cancha 1 | 15/10 18:00-19:30 | Balón Premium Adidas Finale | $45
   📧 Te envié confirmación por email.
   
   ¿Necesitas algo más?"
   - Quick Replies: [📅 Ver mis reservas] [🏟️ Nueva reserva] [🏠 Inicio]
```

---

## 🖥️ S4_05 — Admin Métricas Chatbot (Desktop)

```
Wireframe chatbot metrics dashboard.
Desktop, admin layout with sidebar.

HEADER: "Métricas Chatbot" + Period Selector: [Hoy ▼] [Últimos 7 días] [Últimos 30 días] [Personalizado]

ROW 1 - KPI CARDS (5 cards, col-2-2-2-2-2 on xl, stacked mobile):
① "Conversaciones Totales" — "1,234" + trend "↑ 12% vs período anterior"
   Subtitle: "Últimos 30 días"
② "Mensajes Procesados" — "8,567" + "Promedio 6.9 msg/conv"
③ "Fallback Rate" — "18.5%" 🔴 (rojo si > 20%, amarillo 10-20%, verde < 10%)
   Subtitle: "Objetivo < 15%"
④ "Tiempo Respuesta Promedio" — "1.2s" 🟢
   Subtitle: "P95: 2.8s"
⑤ "Satisfacción (CSAT)" — "4.2/5.0" ⭐ + "234 encuestas"

ROW 2 - CHARTS (col-8 + col-4):
⑥ "Conversaciones por Día" (Line Chart, col-8):
   - X: Días | Y: Conversaciones
   - Series: Total (solid), Completadas (dashed), Transferidas (dotted)
   - Annotations: Deployment markers ▼
⑦ "Top 10 Intenciones" (Horizontal Bar, col-4):
   - crear_reserva (340), consultar_disponibilidad (280), cancelar_reserva (120), faq_horarios (95), faq_precios (87), ver_mis_reservas (76), transferir_humano (45), faq_ubicacion (38), saludar (32), despedirse (28)

ROW 3 - FALLBACK ANALYSIS (col-6 + col-6):
⑧ "Fallback Rate Trend" (Line, col-6): Daily % with 7-day moving average
⑨ "Intenciones con Mayor Fallback" (Table, col-6):
   Columns: Intención | Total | Fallbacks | Fallback % | Confianza Promedio
   Highlight rows > 30% fallback in red

ROW 4 - CHANNEL BREAKDOWN (col-4 each):
⑩ "Por Canal" (Donut): Web (65%), WhatsApp (25%), Telegram (8%), API (2%)
⑪ "Por Hora del Día" (Heatmap): Hour × Day intensity
⑫ "Resolución Primer Contacto" (Gauge): 72% (target 80%)
```

---

## 🖥️ S4_06 — Admin Historial Conversaciones (Desktop)

```
Wireframe conversations history table with filters.
Desktop, full-width table with sticky sidebar filters.

LEFT SIDEBAR FILTERS (col-3, sticky):
① Date Range: [Inicio] [Fin] + Presets
② Estado: [Activa] [Finalizada] [Transferida] [Abandonada] (multi)
③ Canal: [Web] [WhatsApp] [Telegram] [API] (multi)
④ Usuario: Search input (debounce, searches name/email)
⑤ Intención Principal: Select (top 20 + "Otra")
⑥ Fallback: [Sí] [No] [Cualquiera]
⑦ Button: "Aplicar Filtros" (primary) / "Limpiar" (ghost)
⑧ Button: "Exportar CSV" (secondary)

MAIN TABLE (col-9):
Columns (responsive, hide less important on mobile):
- Sesión (ID truncado, copy on click)
- Usuario (Nombre + avatar, link to perfil)
- Canal (badge color)
- Inicio (fecha/hora relative)
- Fin (fecha/hora o "Activa")
- Mensajes (count)
- Estado (badge colored)
- Fallback (Sí/No badge)
- Duración (min:sec)
- Acciones: [Ver] [Exportar JSON] [Tomar Control] (condicional)

ROW STYLES:
- Activa: blue left border + pulse animation on "Activa" badge
- Transferida: purple left border
- Fallback=Sí: orange indicator dot
- Click row → navigate to S4_07

PAGINATION: Bottom, 25/50/100 per page
```

---

## 🖥️ S4_07 — Admin Detalle Conversación (Desktop)

```
Wireframe conversation detail with timeline and traces.
Desktop, three-panel layout: Header (full) | Timeline (col-8) | Traces (col-4).

HEADER (full width, sticky top, gray bg):
① Session ID: "conv_abc123def" [📋 Copiar]
② Usuario: "Juan Pérez" (avatar + link) | Canal: 🌐 Web | Estado: 🟢 Activa
③ Iniciada: "Hace 12 min (14:32)" | Duración: "12:34" | Mensajes: 8
④ Badges: Fallback: No | Intención actual: crear_reserva (87%)
⑤ Actions: [Tomar Control] (primary, si activa) [Finalizar] [Exportar JSON]

TIMELINE (col-8, scrollable):
Messages in chronological order, grouped by date:
MSG 1 (14:32:15) User: "Hola, quiero reservar"
   Meta: Intent: saludar (98%) | Entities: {} | Confidence: 0.98
MSG 2 (14:32:16) Assistant: "¡Hola! ¿En qué te ayudo?" + Quick replies
   Meta: Action: respuesta_faq | Duration: 45ms | Success: ✅
MSG 3 (14:33:22) User: "Cancha 1 mañana 18:00"
   Meta: Intent: crear_reserva (91%) | Entities: {fecha: "mañana", hora: "18:00", cancha: "1"} | Confidence: 0.91
MSG 4 (14:33:23) Assistant: "Tengo Cancha 1..." + Quick replies [Cancha 1] [Cancha 2]
   Meta: Action: consultar_disponibilidad | Duration: 120ms | Success: ✅
... etc.

TRACES PANEL (col-4, sticky):
⑥ "Trazas de Ejecución" (List):
   Each trace card:
   - Trace ID: "trc_xyz789" [Copy]
   - Acción: "crear_reserva"
   - Inicio: 14:35:12.100 | Fin: 14:35:12.850 | Duración: 750ms
   - Estado: ✅ Éxito / ❌ Error
   - Parámetros: {cancha_id: 1, horario_id: 5, balon_id: 3, ...}
   - Resultado: {reserva_id: 1234, estado: "confirmada"}
   - Error (si falla): "Deadlock detected, retry..."

COLORS:
- Success trace: green left border
- Error trace: red left border + expandable error details
- Warning (retry): yellow left border
```

---

## 🖥️ S4_10 — Configuración Respuestas Chatbot (Desktop)

```
Wireframe chatbot response configuration.
Desktop, tabbed interface.

TABS: [Intenciones] [Entidades] [Respuestas] [Seguridad]

TAB INTENCIONES:
① Table: Nombre | Patrones (ejemplos) | Acción | Respuesta | Fallback % | Acciones
② Row: crear_reserva | "reservar", "quiero jugar", "hacer reserva" | crearReservaDesdeChat | Template + Quick replies | 8% | [Editar] [Duplicar] [Eliminar]
③ Button: "+ Nueva Intención" → Modal

MODAL NUEVA INTENCIÓN:
④ Nombre (slug): "consultar_horarios"
⑤ Nombre visible: "Consultar Horarios"
⑥ Patrones de entrenamiento (textarea, uno por línea):
   - "qué horarios tienen"
   - "cuándo abren"
   - "horarios de la cancha 1"
⑦ Acción: Select [consultar_disponibilidad] [respuesta_faq] [transferir_humano] [crear_reserva]
⑧ Respuesta (si acción=respuesta_faq): Rich text editor con variables {{cancha}} {{fecha}} {{horarios}}
⑨ Quick Replies sugeridos (chips input): [Hoy] [Mañana] [Esta semana] [Cancha 1]
⑩ Umbral confianza: Slider 0.5 - 0.95 (default 0.7)
⑪ [Cancelar] [Guardar]

TAB RESPUESTAS:
⑫ Editor de templates por intención con preview en vivo
⑬ Variables disponibles: {{usuario.nombre}}, {{cancha.nombre}}, {{fecha}}, {{hora}}, {{precio}}, {{balon}}

TAB SEGURIDAD:
⑭ Rate Limit: "Máx 30 mensajes/minuto por usuario" [Input number]
⑮ Palabras bloqueadas (textarea): Lista de términos que activan respuesta segura
⑯ Detección inyección: [☑] Activado — Umbral: 0.8
⑰ Sanitización HTML: [☑] Activado
⑱ Longitud máxima mensaje: 500 caracteres
⑲ Logs de seguridad: [Ver logs] (link to traces filtered by security events)
```

---

## 🎨 Estilo Visual Sprint 4

```
WIDGET:
- Panel border-radius: 16px 16px 0 0 (desktop) / 0 (mobile fullscreen)
- Shadow: 0 -4px 24px rgba(0,0,0,0.12)
- Animation: slide-up 300ms cubic-bezier(0.4, 0, 0.2, 1)
- Z-index: 1000 (above header, below modals)

MESSAGE BUBBLES:
- User: #2E86DE, white text, border-radius: 18px 18px 0 18px
- Assistant: #F1F3F4, #202124 text, border-radius: 18px 18px 18px 0
- Human Agent: #E8F5E9, #1B5E20 text, border-left: 3px solid #27AE60
- System: #FFF3E0, #E65100 text, border-left: 3px solid #F39C12

INTENT BADGES:
- Small pill: 10px height, 4px radius, font 10px
- Colors by category: Reserva=Blue, Consulta=Green, FAQ=Gray, Handoff=Purple, Error=Red

QUICK REPLIES:
- Chips: 32px height, 16px radius, #E8F0FE bg, #1A73E8 text
- Max 3 visible, horizontal scroll with scroll-snap
- Touch-friendly: 44px min tap target

TYPING INDICATOR:
- 3 dots, 6px each, bounce animation 1.4s infinite stagger

METRICS CHARTS:
- Chart.js v4, same color palette as Sprint 3
- Real-time updates: WebSocket / polling 30s for live view
- Tooltips: Custom HTML with formatted numbers

TRACES:
- Monospace font (JetBrains Mono 11px)
- JSON syntax highlighting (keys=blue, strings=green, numbers=purple)
- Collapsible: click to expand/collapse parameters/result
- Copy buttons on trace ID, parameters, result
```