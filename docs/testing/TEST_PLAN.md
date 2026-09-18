# TEST_PLAN — Plan de Pruebas

> Sprint 0. Estrategia de pruebas según la sección 34 del prompt maestro.
> Estado actual verificado: **70 pruebas / 162 aserciones** en 11 archivos (PHPUnit, SQLite en memoria).

---

## 1. Estrategia

Pirámide de pruebas sobre PHPUnit (Laravel):
1. **Unitarias** (`tests/Unit`): motor del chatbot (intenciones, entidades, contexto), reglas y servicios aislados.
2. **Integración** (`tests/Feature`): módulos completos contra BD SQLite (auth, canchas, horarios, reservas, dashboard, reportes, chatbot).
3. **Funcionales**: flujos de usuario completos (registro→login→reserva→cancelación).
4. **IA**: intención correcta/incorrecta, entidad faltante/incorrecta, contexto, ambigüedad, fuera de alcance, alucinaciones, permisos.
5. **Seguridad y roles**: acceso denegado a no autorizados, middleware admin, cliente solo ve su información.

---

## 2. Estado actual (verificado el 17/08/2026)

**Resultado real:** 70 pruebas definidas / 162 aserciones. En un entorno limpio (`unset SESSION_PATH APP_ENV; APP_ENV=testing php artisan test`) pasan **69/70**.

### 2.1 Notas de entorno (importante para la evaluación)
- El shell de desarrollo exporta variables del `.env` (`SESSION_PATH` con ruta de Git Bash `C:/Program Files/Git/` y `APP_ENV=local`). Como Laravel usa Dotenv inmutable y PHPUnit no fuerza variables ya existentes (`force="false"` por defecto), esto **rompe la suite**: 500 por cookie de sesión inválida (SESSION_PATH) y 419 CSRF en todos los POST (APP_ENV=local desactiva el bypass de pruebas).
- **Solución:** ejecutar las pruebas con `unset SESSION_PATH APP_ENV; APP_ENV=testing php artisan test`, o exportar el entorno sin esas variables. Alternativa recomendada a futuro: `force="true"` en `phpunit.xml` para las variables clave.
- **Fallo corregido (17/08/2026):** `ChatbotTest > la disponibilidad excluye franjas reservadas` fallaba **únicamente los lunes**: el test usaba `now()->next(1)` (próximo lunes) mientras el bot interpreta "lunes" como *hoy* cuando hoy es lunes (`ExtractorEntidades::proximoDia` devuelve hoy mismo). **Solución aplicada:** el test ahora calcula la fecha con la misma lógica del bot (`ExtractorEntidades::fecha('el lunes')`), eliminando la dependencia del día de ejecución. Resultado: **70/70 pruebas pasan (162 aserciones)**.

### 2.2 Cobertura actual por archivo


| Archivo | Tipo | Cobertura |
|---|---|---|
| `tests/Feature/AuthenticationTest.php` | Integración | Registro, login, logout, acceso autenticado |
| `tests/Feature/UserManagementTest.php` | Integración | CRUD usuarios, roles, perfil, auto-eliminación |
| `tests/Feature/CanchasPublicasTest.php` | Integración | Catálogo, detalle, solo canchas disponibles |
| `tests/Feature/HorarioManagementTest.php` | Integración | CRUD horarios, anti-superposición |
| `tests/Feature/ReservaTest.php` | Integración | Creación, duplicidad, cancelación, disponibilidad |
| `tests/Feature/DashboardReportesTest.php` | Integración | KPIs, filtros, exportación CSV |
| `tests/Feature/ChatbotTest.php` | Integración | Flujo chatbot, persistencia, permisos |
| `tests/Unit/ChatbotMotorTest.php` | Unitaria | Intenciones, entidades, contexto, ambigüedades |

---

## 3. Plan por tipo (sección 34)

### 3.1 Pruebas unitarias (ampliar en Sprint 7)
- [ ] Servicios: `EstadisticasService` (métricas, ocupación, ingresos).
- [ ] Reglas: `SinSuperposicionHorario` (casos límite: franjas contiguas, cruzadas, idénticas).
- [ ] Modelos: `Reserva::esCancelablePorCliente`, `Cancha::horariosDisponiblesPara`.
- [ ] Skills (Sprint 6): cada skill con parámetros válidos/inválidos.

### 3.2 Pruebas de integración (ampliar)
- [ ] Pagos (Sprint 4): registro, estados, relación con reserva.
- [ ] Roles nuevos (Sprint 6): recepcionista/gerente.
- [ ] Auditoría (Sprint 6).
- [ ] Multiagente (Sprint 6): delegación del Orquestador, bloqueo del Agente de Seguridad.

### 3.3 Pruebas funcionales
- [ ] Flujo completo: registro → login → consultar disponibilidad → reservar → mis reservas → cancelar.
- [ ] Chatbot: "Quiero reservar una cancha mañana a las 19:00" → guía con datos reales.

### 3.4 Pruebas de IA (sección 34)
- [ ] Intención correcta e incorrecta.
- [ ] Entidad faltante e incorrecta.
- [ ] Contexto entre mensajes.
- [ ] Consultas ambiguas ("a las 8" → 20:00).
- [ ] Consultas fuera de alcance → respuesta de límite (sin inventar).
- [ ] Alucinaciones: verificar que nunca se inventan precios/disponibilidad (respuestas derivadas de BD).
- [ ] Permisos: cliente no obtiene reservas de otro usuario.

### 3.5 Pruebas de seguridad
- [ ] CSRF en formularios (Laravel por defecto).
- [ ] Acceso 403 al panel admin como cliente.
- [ ] Validación de entradas (requeridos, tipos, máximos).
- [ ] Hashing de contraseñas (nunca se devuelve la contraseña).

---

## 4. Comandos

```bash
php artisan test          # toda la suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

---

## 5. Métricas objetivo (Sprint 7)

- 100 % de requisitos funcionales con al menos 1 prueba de integración.
- Cobertura de ramas clave: anti-duplicidad, anti-superposición, cancelación, chatbot multiagente.
- Documentación de casos de prueba en `docs/testing/TEST_CASES.md` (TC-001…).
