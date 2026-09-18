# PRODUCT_BACKLOG — Backlog del Producto

> Sprint 0. Historias de usuario priorizadas con estimación (Fibonacci), dependencias y sprint de destino.
> Estado de implementación verificado contra el código real.

---

## 1. Historias de usuario (backlog priorizado)

| PB | ID HU | Historia | Prioridad | Estimación | Depende de | Sprint | Estado |
|---|---|---|---|---|---|---|---|
| PB-001 | HU-01 | Como usuario, quiero registrarme e iniciar sesión para acceder al sistema. | Alta | 5 | — | 1 | ✅ |
| PB-002 | HU-02 | Como administrador, quiero gestionar usuarios (crear, editar, activar, eliminar) para controlar el acceso. | Alta | 8 | HU-01 | 1 | ✅ |
| PB-003 | HU-03 | Como administrador, quiero asignar roles a los usuarios para restringir permisos. | Alta | 5 | HU-02 | 1 | ⚠️ parcial (solo admin/cliente) |
| PB-004 | HU-04 | Como usuario, quiero editar mi perfil y cambiar mi contraseña. | Media | 3 | HU-01 | 1 | ✅ |
| PB-005 | HU-05 | Como administrador, quiero gestionar canchas (alta, edición, estados, precios) para ofrecer el catálogo. | Alta | 8 | HU-01 | 2 | ✅ |
| PB-006 | HU-06 | Como administrador, quiero definir horarios por cancha y día para establecer la oferta. | Alta | 8 | HU-05 | 2 | ✅ |
| PB-007 | HU-07 | Como cliente, quiero ver el catálogo de canchas disponibles y sus características. | Alta | 5 | HU-05 | 2 | ✅ |
| PB-008 | HU-08 | Como cliente, quiero consultar la disponibilidad de una cancha para una fecha. | Alta | 5 | HU-05, HU-06 | 3 | ✅ |
| PB-009 | HU-09 | Como cliente, quiero reservar una cancha eligiendo fecha y horario. | Alta | 13 | HU-08 | 3 | ✅ |
| PB-010 | HU-10 | Como cliente, quiero ver y cancelar mis reservas (según reglas). | Alta | 8 | HU-09 | 3 | ✅ |
| PB-011 | HU-11 | Como administrador, quiero gestionar reservas y ver el calendario de ocupación. | Alta | 8 | HU-09 | 3 | ✅ |
| PB-012 | HU-12 | Como gerente, quiero un dashboard con indicadores de reservas, ocupación e ingresos. | Media | 8 | HU-11 | 4 | ✅ |
| PB-013 | HU-13 | Como gerente, quiero generar reportes con filtros y exportarlos. | Media | 8 | HU-12 | 4 | ✅ |
| PB-014 | HU-14 | Como administrador, quiero registrar pagos de reservas y su estado. | Media | 8 | HU-09 | 4 | ⏳ |
| PB-015 | HU-15 | Como administrador, quiero gestionar clientes con su historial. | Media | 5 | HU-02 | 4 | ⏳ |
| PB-016 | HU-16 | Como cliente, quiero consultar información y gestionar mi reserva por chat. | Alta | 13 | HU-08, HU-09 | 5 | ✅ (consulta) / ⏳ (gestión total) |
| PB-017 | HU-17 | Como visitante, quiero usar el chatbot para consultar horarios, precios y disponibilidad. | Alta | 8 | HU-16 | 5 | ✅ |
| PB-018 | HU-18 | Como sistema, quiero que el chatbot funcione con agentes especializados coordinados (multiagente). | Media | 21 | HU-16 | 6 | ⏳ |
| PB-019 | HU-19 | Como administrador, quiero que las operaciones críticas queden auditadas. | Media | 8 | HU-02 | 6 | ⏳ |
| PB-020 | HU-20 | Como equipo, quiero pruebas integrales (unitarias, integración, funcionales, IA) que demuestren el cumplimiento. | Alta | 13 | — | 7 | ⏳ |
| PB-021 | HU-21 | Como equipo, quiero la documentación final (manuales, UML, trazabilidad, evidencias). | Alta | 13 | — | 8 | ⏳ |

---

## 2. Priorización y velocidad

- **Escala de prioridad:** Alta / Media / Baja.
- **Estimación:** puntos de historia (1, 2, 3, 5, 8, 13, 21).
- **Velocidad proyectada:** 15–20 puntos por sprint.
- **Total estimado del backlog actual:** ≈ 170 puntos.

---

## 3. Criterios de aceptación transversales (Definición de Hecho — sección 35)

Una historia solo es DONE cuando cumple **todos**:

1. Código implementado.
2. Validaciones implementadas.
3. Interfaz terminada.
4. Base de datos actualizada (si corresponde).
5. Pruebas realizadas.
6. Errores críticos corregidos.
7. Documentación actualizada.
8. Trazabilidad actualizada.
9. UML actualizado.
10. Criterios de aceptación de la historia cumplidos.

---

## 4. Dependencias entre sprints

```
Sprint 1 (auth/usuarios/roles)
   └─► Sprint 2 (canchas/horarios) ──► Sprint 3 (reservas/disponibilidad)
                                            ├─► Sprint 4 (dashboard/reportes/clientes/pagos)
                                            └─► Sprint 5 (chatbot NLP) ──► Sprint 6 (multiagente)
                                                                                └─► Sprint 7 (calidad) ──► Sprint 8 (cierre)
```

---

## 5. Notas

- Los Sprints 1–5 ya cuentan con implementación en el repositorio; el backlog se actualizará en cada sprint con las tareas de refinamiento (roles restantes, pagos, clientes).
- **INFORMACIÓN NO DEFINIDA — REQUIERE VALIDACIÓN:** historias adicionales derivadas del documento de tesis (reportes específicos, formatos, políticas) se incorporarán tras validarlo.
