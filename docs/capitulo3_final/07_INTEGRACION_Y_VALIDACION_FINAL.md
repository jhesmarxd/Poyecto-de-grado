# 3.4. INTEGRACIÓN Y VALIDACIÓN FINAL DEL SISTEMA

Una vez concluidas las cuatro iteraciones de desarrollo bajo el marco ágil Scrum (abarcando las Historias de Usuario oficiales **HU-01 a HU-07**), se ejecutó el proceso de integración global y aseguramiento de la calidad (QA). Esta fase certifica que los módulos de acceso y usuarios, canchas y horarios, reservas y chatbot, así como dashboard y reportes interactúen de forma armónica, consistente y sin degradación de rendimiento.

---

### 3.4.1. Pruebas de Integración

Las pruebas de integración validaron la interoperabilidad entre los diferentes componentes del sistema y los servicios externos:

1. **Integración Flujo de Reserva - Motor de Disponibilidad - Google Calendar:**
   * **Objetivo:** Verificar que una reserva realizada desde la interfaz web o mediante la asistencia conversacional reserve el turno, bloquee inmediatamente la disponibilidad en el cálculo algorítmico y genere el evento en la API externa de Google Calendar.
   * **Resultado:** La transacción de base de datos asegura el turno mediante bloqueo pesimista y el trabajo en segundo plano (`GoogleCalendarSyncJob`) actualiza el calendario en un tiempo medio de 1.2 segundos sin demorar la respuesta al usuario.
2. **Integración Autenticación Externa (Google Auth) - Cuentas de Usuario:**
   * **Objetivo:** Comprobar que el inicio de sesión con Google Auth enlace la cuenta en `users` y `social_accounts` sin provocar duplicidad de correos o inconsistencias de roles.
   * **Resultado:** Las cuentas se vinculan correctamente preservando la integridad referencial y asignando el rol seguro `cliente`.
3. **Integración Chatbot NLP - Base de Datos Transaccional y API de Gemini:**
   * **Objetivo:** Validar que las intenciones interpretadas por la API externa de Gemini consulten en tiempo real los registros reales de canchas y balones en la base de datos local MariaDB/MySQL.
   * **Resultado:** El asistente virtual entrega información actualizada de precios y turnos sin discrepancias con la base de datos.

---

### 3.4.2. Pruebas Funcionales Generales (End-to-End)

Se ejecutaron pruebas funcionales de extremo a extremo que simularon el recorrido completo de los usuarios reales en el sistema:

| ID Prueba | Flujo Funcional Evaluado | Pasos de Ejecución | Resultado Obtenido | Estado |
| :---: | :--- | :--- | :--- | :---: |
| **PFE-01** | Registro, reserva web y confirmación con calendario | 1. Registro de nuevo cliente.<br>2. Selección de Cancha 1 y Turno 19:00.<br>3. Agregado de balón reglamentario.<br>4. Confirmación de reserva. | Reserva creada con total desglosado, turno bloqueado para terceros y evento reflejado en Google Calendar. | **Exitosa** |
| **PFE-02** | Operatoria presencial en recepción (Check-in a Check-out) | 1. Recepcionista busca reserva confirmada.<br>2. Ejecuta "Check-in" al llegar el cliente.<br>3. Transcurrida la hora, ejecuta "Check-out". | Marcas de tiempo registradas con éxito; la reserva pasa a estado `completada` y se contabiliza en caja. | **Exitosa** |
| **PFE-03** | Consulta mediante Chatbot NLP | 1. Cliente abre widget de chat.<br>2. Pregunta: *"¿Tienen cancha libre mañana a las 20:00?"*.<br>3. Bot valida turnos y ofrece opciones.<br>4. Cliente recibe opciones reales. | Consulta resuelta con precisión a partir de los datos reales del sistema. | **Exitosa** |
| **PFE-04** | Auditoría gerencial y exportación de reportes | 1. Administrador accede al Dashboard.<br>2. Comprueba ingresos actualizados por las reservas del día.<br>3. Genera y descarga el reporte en PDF y Excel. | Cifras financieras del Dashboard coinciden al 100% con los totales impresos en el PDF descargado. | **Exitosa** |

---

### 3.4.3. Validación de los Requisitos

A continuación se presenta la **Matriz de Trazabilidad**, contrastando los requerimientos funcionales y las siete Historias de Usuario oficiales contra el módulo implementado y su estado de cumplimiento:

| Código RF | Historia Oficial | Descripción del Requerimiento Funcional | Módulo y Sprint | Estado de Cumplimiento |
| :---: | :---: | :--- | :---: | :---: |
| **RF-01** | **HU-01** | Autenticación y Control de Acceso Multi-Rol | `Auth` (Sprint 1) | **100% Implementado** |
| **RF-02** | **HU-02** | Gestión Integral de Usuarios y Roles | `Admin\UserController` (Sprint 1) | **100% Implementado** |
| **RF-03** | **HU-03** | Administración de Canchas y Balones | `Admin\CanchaController` (Sprint 2) | **100% Implementado** |
| **RF-04** | **HU-03** | Registro de Cierres Temporales y Mantenimientos | Modelo `CierreCancha` (Sprint 2) | **100% Implementado** |
| **RF-05** | **HU-04** | Configuración de Horarios de Atención | `Admin\HorarioController` (Sprint 2) | **100% Implementado** |
| **RF-06** | **HU-04** | Cálculo y Consulta de Disponibilidad de Canchas | `DisponibilidadController` (Sprint 2) | **100% Implementado** |
| **RF-07** | **HU-05** | Registro y Administración de Reservas de Canchas | `ReservaController` (Sprint 3) | **100% Implementado** |
| **RF-08** | **HU-05** | Control Operativo Presencial en Recepción | `Recepcionista\CheckinController` (Sprint 3) | **100% Implementado** |
| **RF-09** | **HU-05** | Sincronización Externa con Google Calendar | `GoogleCalendarSyncJob` (Sprint 3) | **100% Implementado** |
| **RF-10** | **HU-06** | Asistente Virtual Inteligente con Chatbot NLP | `ChatbotController` (Sprint 3) | **100% Implementado** |
| **RF-11** | **HU-07** | Dashboard Administrativo con Indicadores Operativos | `Admin\DashboardController` (Sprint 4) | **100% Implementado** |
| **RF-12** | **HU-07** | Generación y Exportación de Reportes Administrativos | `Admin\ReporteController` (Sprint 4) | **100% Implementado** |

---

### 3.4.4. Resultados Finales de las Pruebas

El plan integral de aseguramiento de calidad ejecutado sobre el sistema arrojó los siguientes resultados cuantitativos:

* **Cobertura de Pruebas Automatizadas con PHPUnit:**
  * Pruebas de Característica (*Feature Tests*): **24 archivos de prueba** ejecutados.
  * Pruebas Unitarias (*Unit Tests*): **6 archivos de prueba** de servicios y reglas matemáticas.
  * Pruebas de Integración y Concurrencia (*Integration Tests*): **2 pruebas de estrés transaccional** en base de datos.
  * **Tasa de Aprobación:** **100% de pruebas exitosas (0 fallos, 0 errores sintácticos)**.
* **Seguridad y Robustez:**
  * Cifrado Bcrypt verificado en todas las contraseñas de cuentas locales.
  * Protección CSRF activa y validación estricta de formularios mediante `FormRequests`.
  * Prevención de solapamiento de turnos validada mediante transacciones con bloqueo pesimista en base de datos.
* **Aceptación de Usuario Final:**
  * Los criterios de aceptación de las siete Historias de Usuario oficiales (HU-01 a HU-07) fueron validados satisfactoriamente, certificando que el sistema se encuentra completamente integrado, validado y listo para su presentación y defensa de grado.
