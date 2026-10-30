# Mockups Sprint 2 — Canchas, Horarios y Reservas Core

## Vistas Requeridas (12 imágenes totales)

| # | Vista | Desktop | Mobile | Anotaciones Clave |
|---|-------|---------|--------|-------------------|
| 1 | Listado Canchas (Público) | `S2_01_canchas_desktop.png` | `S2_01_canchas_mobile.png` | ① Filtros (fecha, tipo) ② Grid cards canchas ③ Card: imagen, nombre, precio, tipo, [Ver detalle] ④ Paginación |
| 2 | Detalle Cancha | `S2_02_detalle_desktop.png` | `S2_02_detalle_mobile.png` | ① Galería imágenes ② Info: ubicación, precio, tipo piso, malla ③ Horarios semana (accordion) ④ Botón "Reservar" por horario ⑤ Mapa ubicacion |
| 3 | Crear Reserva | `S2_03_crear_desktop.png` | `S2_03_crear_mobile.png` | ① Resumen: cancha, horario, fecha ② Selector balón (propio/prestado/premium) ③ Balones ocupados (badges) ④ Observaciones ⑤ Precio total ⑥ Botón Confirmar |
| 4 | Mis Reservas (Calendario) | `S2_04_mis_cal_desktop.png` | `S2_04_mis_cal_mobile.png` | ① Selector vista: Calendario/Lista ② FullCalendar month view ③ Eventos color-coded por estado ④ Click evento → modal detalle ⑤ Próxima reserva card (sticky top) |
| 5 | Mis Reservas (Lista) | `S2_05_mis_lista_desktop.png` | `S2_05_mis_lista_mobile.png` | ① Tabs filtros: Todas, Pendientes, Confirmadas, Pasadas ② Tabla: Fecha, Cancha, Horario, Estado, Acciones ③ Acciones: [Ver] [Cancelar] (condicional) ④ Empty state |
| 6 | Recepcionista - Reservas Hoy | `S2_06_recep_hoy_desktop.png` | `S2_06_recep_hoy_mobile.png` | ① Date picker (hoy) ② Tabs estado ③ Tabla: Hora, Cancha, Cliente, Tel, Estado, Check-in/out, Acciones ④ Botón "Nueva Reserva Walk-in" ⑤ Filtro cancha |
| 7 | Recepcionista - Nueva Reserva Walk-in | `S2_07_recep_nueva_desktop.png` | `S2_07_recep_nueva_mobile.png` | ① Buscador cliente (nombre/tel/email) ② Cliente seleccionado card ③ Form reserva: cancha, horario, fecha, balón ④ Precio ⑤ Botón Crear |
| 8 | Recepcionista - Check-in | `S2_08_checkin_desktop.png` | `S2_08_checkin_mobile.png` | ① Lista reservas hoy (pendientes/confirmadas) ② Botón "Check-in" por fila ③ Modal confirmación ④ Toast success |
| 9 | Recepcionista - Check-out | `S2_09_checkout_desktop.png` | `S2_09_checkout_mobile.png` | ① Lista reservas "En curso" ② Botón "Check-out" ③ Modal confirmación ④ Resumen: duración real, monto ⑤ Toast success |
| 10 | Disponibilidad Pública (Calendario) | `S2_10_disp_desktop.png` | `S2_10_disp_mobile.png` | ① Selector fecha ② Grid canchas × horarios (heatmap) ③ Verde=libre, Rojo=ocupado, Gris=no opera ④ Click celda → /reservas/crear |
| 11 | Admin - Canchas CRUD | `S2_11_admin_canchas_desktop.png` | `S2_11_admin_canchas_mobile.png` | ① Tabla: Nombre, Tipo, Precio, Estado, Acciones ② Botón "Nueva Cancha" ③ Modal Crear/Editar ④ Subida imagen drag-drop |
| 12 | Admin - Horarios CRUD | `S2_12_admin_horarios_desktop.png` | `S2_12_admin_horarios_mobile.png` | ① Selector cancha ② Tabla horarios por día ③ Modal Crear/Editar: día, hora inicio/fin, vigencia ④ Toggle disponible |

## Prompts Detallados
Ver `prompts.md` en esta carpeta.