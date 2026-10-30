# Mockups Sprint 4 — Chatbot IA y Observabilidad

## Vistas Requeridas (10 imágenes totales)

| # | Vista | Desktop | Mobile | Anotaciones Clave |
|---|-------|---------|--------|-------------------|
| 1 | Widget Chatbot (Cerrado) | `S4_01_widget_closed_desktop.png` | `S4_01_widget_closed_mobile.png` | ① Botón flotante (FAB) bottom-right ② Icono Wally/bot ③ Badge notificaciones (opcional) ④ Tooltip "¿Necesitas ayuda?" |
| 2 | Widget Chatbot (Abierto) | `S4_02_widget_open_desktop.png` | `S4_02_widget_open_mobile.png` | ① Panel 350x500px (desktop) / Full-screen (mobile) ② Header: Logo, título, [Minimizar] [Cerrar] ③ Mensajes burbuja (user/assistant) ④ Input + botón enviar ⑤ Quick replies / botones sugeridos ⑥ Typing indicator |
| 3 | Conversación - Flujo Reserva | `S4_03_flow_reserva_desktop.png` | `S4_03_flow_reserva_mobile.png` | ① Usuario: "Quiero reservar fútbol 5 mañana 18:00" ② Bot: Clarificación cancha + quick replies ③ Usuario selecciona ④ Bot: Pide balón + quick replies ⑤ Usuario elige ⑥ Bot: Confirmación + resumen + [Confirmar] [Cancelar] ⑦ Usuario confirma ⑧ Bot: ✅ Reserva #123 creada + [Ver mis reservas] |
| 4 | Conversación - Transferencia Humano | `S4_04_handoff_desktop.png` | `S4_04_handoff_mobile.png` | ① Usuario: "Quiero hablar con alguien" ② Bot: "Te conecto con un agente..." ③ Sistema: Badge "🟢 Agente conectado" ④ Agente humano escribe ⑤ Bot: Mensajes del agente con avatar distinto ⑥ Botón "Finalizar conversación" |
| 5 | Admin - Métricas Chatbot | `S4_05_metricas_desktop.png` | `S4_05_metricas_mobile.png` | ① Period selector (Hoy, 7d, 30d, Personalizado) ② KPI Cards: Conversaciones, Mensajes, Fallback %, Tiempo resp., Satisfacción ③ Gráfico: Conversaciones/día (línea) ④ Gráfico: Top 10 Intenciones (barras) ⑤ Gráfico: Fallback rate trend ⑥ Tabla: Intención | Count | Fallback % | Avg Confidence |
| 6 | Admin - Historial Conversaciones | `S4_06_historial_desktop.png` | `S4_06_historial_mobile.png` | ① Filtros: Fecha, Estado, Canal, Usuario, Intención ② Tabla: Sesión | Usuario | Canal | Inicio | Fin | Mensajes | Estado | Fallback | Acciones ③ Row click → S4_07 ④ Export CSV |
| 7 | Admin - Detalle Conversación | `S4_07_detalle_desktop.png` | `S4_07_detalle_mobile.png` | ① Header: Session ID, Usuario, Canal, Estado, Inicio, Duración ② Timeline mensajes (burbujas) con: timestamp, intent, confidence, entidades ③ Sidebar: Trazas ejecución (acciones, duración, éxito/error) ④ Botón "Tomar Control" (si activa) ⑤ Botón "Exportar JSON" |
| 8 | Admin - Supervisión Tiempo Real | `S4_08_live_desktop.png` | `S4_08_live_mobile.png` | ① Live feed: Conversaciones activas ahora ② Cards por sesión: Usuario, Último mensaje, Tiempo inactividad, Estado ③ Alertas: Fallback rate > 30%, Sesiones estancadas > 5min ④ Botón "Tomar Control" por card |
| 9 | Conversación - FAQ / Errores | `S4_09_faq_desktop.png` | `S4_09_faq_mobile.png` | ① Usuario pregunta FAQ ② Bot responde con template + quick replies relacionadas ③ Usuario: input inválido / inyección ④ Bot: Respuesta segura genérica + logging |
| 10 | Chatbot - Configuración Respuestas | `S4_10_config_desktop.png` | `S4_10_config_mobile.png` | ① Tabs: Intenciones / Entidades / Respuestas / Seguridad ② Tab Intenciones: Lista + [Nueva] + Editar (patrones, respuesta, acción) ③ Tab Respuestas: Editor rich text + variables {{cancha}} {{fecha}} ④ Tab Seguridad: Rate limit, palabras bloqueadas, umbral confianza |

## Prompts Detallados
Ver `prompts.md` en esta carpeta.