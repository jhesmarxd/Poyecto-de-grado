# Casos de prueba — Sistema de Reservas de Canchas Wally

## Datos y condiciones comunes de prueba

- Navegador: Mozilla Firefox.
- Servidor local: en la terminal de Visual Studio Code ejecutar `php artisan serve`; abrir `http://127.0.0.1:8000`.
- Base de datos de prueba cargada con los seeders del proyecto. Cuentas activas:
  - Administrador: **Carlos Mendoza**, `admin@wally.com`, contraseña `Admin123!`.
  - Recepcionista: **María Elena Torres**, `recepcion@wally.com`, contraseña `Recep123!`.
  - Cliente: **Juan Pablo García**, `juan.garcia@email.com`, contraseña `Cliente123!`.
- Canchas iniciales: **Cancha 1** y **Cancha 2**, ambas con tarifa de **Bs. 25,00 por hora**.
- Para no alterar los datos de demostración, los registros creados en los casos usan el sufijo `-CP` y se eliminan al terminar la evidencia. Cuando se solicite una fecha futura, usar la **próxima fecha futura del día indicado** mostrada por el sistema.
- Si una precondición no se cumple, el caso queda **No ejecutado** y no se continúan sus pasos.

Cada tabla conserva la estructura del ejemplo. El bloque **Resultado obtenido / evidencia** está intencionalmente vacío para insertar las capturas de pantalla.

---

# Sprint 1 — Acceso y usuarios

## HU-01 — Iniciar sesión

| Campo | Detalle |
|---|---|
| Título | **Iniciar sesión como administrador con credenciales válidas** |
| ID | TC-S1-001 |
| Prioridad | Crítica |
| Precondición | El servidor local está iniciado, Firefox muestra `http://127.0.0.1:8000/login` y existe activa la cuenta Carlos Mendoza. |
| Descripción breve | Se valida el acceso de un administrador mediante el correo `admin@wally.com` y la contraseña `Admin123!`. |
| Pasos | 1. Seleccionar el campo **Correo electrónico**.<br>2. Escribir `admin@wally.com`.<br>3. Seleccionar el campo **Contraseña**.<br>4. Escribir `Admin123!`.<br>5. Presionar **Iniciar sesión**. |
| Resultado esperado | El sistema inicia la sesión y muestra el panel administrativo en la URL `/admin`, con el título **Panel de control**. |
| Resultado obtenido / evidencia | **[Insertar imagen del formulario diligenciado y del panel administrativo.]**<br><br><br> |
| Estado | Pendiente |

| Campo | Detalle |
|---|---|
| Título | **Rechazar el inicio de sesión con contraseña incorrecta** |
| ID | TC-S1-002 |
| Prioridad | Alta |
| Precondición | El servidor local está iniciado, Firefox muestra `http://127.0.0.1:8000/login` y existe activa la cuenta Carlos Mendoza. |
| Descripción breve | Se usa un correo existente con una contraseña distinta a la registrada. Correo: `admin@wally.com`; contraseña: `Admin123`. |
| Pasos | 1. Escribir `admin@wally.com` en **Correo electrónico**.<br>2. Escribir `Admin123` en **Contraseña**.<br>3. Presionar **Iniciar sesión**. |
| Resultado esperado | El formulario permanece visible, no se crea una sesión y se muestra el mensaje **Estas credenciales no coinciden con nuestros registros.** |
| Resultado obtenido / evidencia | **[Insertar imagen del mensaje de credenciales inválidas.]**<br><br><br> |
| Estado | Pendiente |

| Campo | Detalle |
|---|---|
| Título | **Redirigir al recepcionista a sus reservas del día** |
| ID | TC-S1-003 |
| Prioridad | Alta |
| Precondición | El servidor local está iniciado, Firefox muestra `http://127.0.0.1:8000/login` y existe activa la cuenta María Elena Torres. |
| Descripción breve | Se verifica que el rol Recepcionista sea dirigido a su módulo autorizado. Correo: `recepcion@wally.com`; contraseña: `Recep123!`. |
| Pasos | 1. Escribir `recepcion@wally.com` en **Correo electrónico**.<br>2. Escribir `Recep123!` en **Contraseña**.<br>3. Presionar **Iniciar sesión**. |
| Resultado esperado | El sistema inicia la sesión y redirige a `/recepcionista/reservas/hoy`, donde se muestran las reservas del día para atención presencial. |
| Resultado obtenido / evidencia | **[Insertar imagen de la pantalla “Reservas de hoy”.]**<br><br><br> |
| Estado | Pendiente |

## HU-02 — Gestionar usuarios

| Campo | Detalle |
|---|---|
| Título | **Registrar una cuenta de cliente desde Usuarios y accesos** |
| ID | TC-S1-004 |
| Prioridad | Alta |
| Precondición | El administrador Carlos Mendoza inició sesión y se encuentra en `/admin`. No existe el correo `prueba.cp@wally.com`. |
| Descripción breve | Se registra una cuenta de cliente con datos válidos para verificar la persistencia y la asignación de rol. |
| Pasos | 1. Abrir el menú **Usuarios** y seleccionar **Usuarios y accesos**.<br>2. Presionar **+ Nuevo usuario**.<br>3. En **Nombre completo**, escribir `Cliente Prueba CP`.<br>4. En **Correo electrónico**, escribir `prueba.cp@wally.com`.<br>5. En **Teléfono**, escribir `79990001`.<br>6. En **Rol**, mantener **Cliente**.<br>7. En **Contraseña**, escribir `ClienteCP1!` y repetirla en **Confirmar contraseña**.<br>8. Presionar **Guardar usuario**. |
| Resultado esperado | Se muestra **Usuario creado correctamente.** y el listado contiene a Cliente Prueba CP con correo `prueba.cp@wally.com`, rol Cliente y estado Activo. |
| Resultado obtenido / evidencia | **[Insertar imagen del formulario y del usuario creado en el listado.]**<br><br><br> |
| Estado | Pendiente |

| Campo | Detalle |
|---|---|
| Título | **Impedir el registro de un correo de usuario duplicado** |
| ID | TC-S1-005 |
| Prioridad | Alta |
| Precondición | El administrador Carlos Mendoza inició sesión; existe la cuenta `juan.garcia@email.com`. |
| Descripción breve | Se intenta crear otro usuario con el correo existente de Juan Pablo García. |
| Pasos | 1. Abrir **Usuarios y accesos** y presionar **+ Nuevo usuario**.<br>2. Escribir `Juan duplicado CP` en **Nombre completo**.<br>3. Escribir `juan.garcia@email.com` en **Correo electrónico**.<br>4. Escribir `79990002` en **Teléfono**.<br>5. Seleccionar **Cliente** en **Rol**.<br>6. Escribir `ClienteCP1!` en **Contraseña** y **Confirmar contraseña**.<br>7. Presionar **Guardar usuario**. |
| Resultado esperado | No se crea el usuario y debajo del correo se muestra **Este email ya está registrado.** |
| Resultado obtenido / evidencia | **[Insertar imagen de la validación del correo duplicado.]**<br><br><br> |
| Estado | Pendiente |

---

# Sprint 2 — Canchas y horarios

## HU-03 — Administrar canchas

| Campo | Detalle |
|---|---|
| Título | **Registrar una cancha disponible con datos válidos** |
| ID | TC-S2-006 |
| Prioridad | Alta |
| Precondición | El administrador Carlos Mendoza inició sesión y está disponible la ruta `/admin/canchas`. No existe una cancha llamada `Cancha Prueba CP`. |
| Descripción breve | Se registra una cancha abierta con una tarifa válida para comprobar el alta de infraestructura deportiva. |
| Pasos | 1. Abrir **Gestión de canchas** y presionar **+ Nueva cancha**.<br>2. En **Nombre**, escribir `Cancha Prueba CP`.<br>3. En **Descripción**, escribir `Cancha creada para evidencia de prueba`.<br>4. En **Ubicación**, escribir `Complejo Wally · Zona de pruebas`.<br>5. En **Precio por hora**, escribir `30`.<br>6. Seleccionar el estado **Disponible**.<br>7. En **Tipo de recinto**, seleccionar **Abierta**.<br>8. Presionar **Guardar cancha**. |
| Resultado esperado | El sistema muestra **Cancha creada correctamente.** y el listado incluye Cancha Prueba CP con tarifa **Bs. 30,00** y estado **Disponible**. |
| Resultado obtenido / evidencia | **[Insertar imagen del registro y la fila creada.]**<br><br><br> |
| Estado | Pendiente |

| Campo | Detalle |
|---|---|
| Título | **Rechazar una cancha con precio por hora negativo** |
| ID | TC-S2-007 |
| Prioridad | Media |
| Precondición | El administrador Carlos Mendoza inició sesión y se muestra el formulario **Nueva cancha**. |
| Descripción breve | Se valida que la tarifa no acepte valores negativos. |
| Pasos | 1. Escribir `Cancha Precio Inválido CP` en **Nombre**.<br>2. Escribir `-25` en **Precio por hora**.<br>3. Seleccionar **Disponible** en **Estado**.<br>4. Seleccionar **Cerrada** en **Tipo de recinto**.<br>5. Presionar **Guardar cancha**. |
| Resultado esperado | No se registra la cancha y se muestra el mensaje **El precio no puede ser negativo.** |
| Resultado obtenido / evidencia | **[Insertar imagen de la validación de precio.]**<br><br><br> |
| Estado | Pendiente |

## HU-04 — Gestionar horarios

| Campo | Detalle |
|---|---|
| Título | **Configurar franjas reservables consecutivas para una cancha** |
| ID | TC-S2-008 |
| Prioridad | Alta |
| Precondición | El administrador Carlos Mendoza inició sesión; existe Cancha 1 y se muestra `/admin/horarios?cancha_id=1`. |
| Descripción breve | Se crean franjas de una hora para Cancha 1 usando la configuración masiva. |
| Pasos | 1. En el selector **Cancha**, seleccionar **Cancha 1**.<br>2. En **Días habilitados**, marcar únicamente **Lunes**.<br>3. En **Desde**, seleccionar la fecha del próximo lunes.<br>4. En **Hasta**, seleccionar la misma fecha.<br>5. En **Apertura**, escribir `18:00` y en **Cierre**, escribir `22:00`.<br>6. En **Cada**, seleccionar **60 minutos**.<br>7. Presionar **Aplicar configuración** y confirmar la acción en el cuadro de confirmación. |
| Resultado esperado | El listado de franjas de Cancha 1 muestra para lunes los períodos 18:00–19:00, 19:00–20:00, 20:00–21:00 y 21:00–22:00, disponibles y con la vigencia indicada. |
| Resultado obtenido / evidencia | **[Insertar imagen de la configuración y de las franjas generadas.]**<br><br><br> |
| Estado | Pendiente |

| Campo | Detalle |
|---|---|
| Título | **Ocultar una fecha cerrada en la consulta de disponibilidad** |
| ID | TC-S2-009 |
| Prioridad | Alta |
| Precondición | El administrador inició sesión, está en **Franjas reservables** de Cancha 1 y se dispone de una fecha futura sin reservas. |
| Descripción breve | Se registra un cierre por mantenimiento y se verifica que esa fecha no ofrezca horarios de Cancha 1. |
| Pasos | 1. En **Fecha cerrada**, seleccionar una fecha futura de lunes.<br>2. En **Motivo**, escribir `Mantenimiento CP`.<br>3. Presionar **Registrar cierre** y confirmar.<br>4. Cerrar sesión.<br>5. Abrir `/disponibilidad`.<br>6. Seleccionar **Cancha 1** en **Elige la cancha** y la misma fecha en **Elige la fecha**.<br>7. Presionar **Consultar horarios**. |
| Resultado esperado | La consulta no presenta franjas disponibles para Cancha 1 en esa fecha y muestra **No hay horarios libres para esta fecha**. |
| Resultado obtenido / evidencia | **[Insertar imagen del cierre registrado y de la consulta sin horarios.]**<br><br><br> |
| Estado | Pendiente |

---

# Sprint 3 — Reservas y chatbot NLP

## HU-05 — Administrar reservas

| Campo | Detalle |
|---|---|
| Título | **Registrar una reserva de cliente en una franja disponible** |
| ID | TC-S3-010 |
| Prioridad | Crítica |
| Precondición | Juan Pablo García inició sesión. Cancha 2 tiene una franja libre de 18:00–19:00 en el próximo martes futuro y dicha fecha no tiene cierre. |
| Descripción breve | El cliente reserva Cancha 2 con balón propio y una observación, utilizando la disponibilidad real mostrada por el sistema. |
| Pasos | 1. Abrir **Consultar disponibilidad**.<br>2. Seleccionar **Cancha 2 — Bs. 25,00/h**.<br>3. Elegir el próximo martes futuro.<br>4. Presionar **Consultar horarios**.<br>5. Seleccionar la franja disponible **18:00 – 19:00**.<br>6. Mantener seleccionada la opción **Traigo mi propio balón**.<br>7. En **Observaciones (opcional)**, escribir `Reserva de evidencia CP`.<br>8. Presionar **Confirmar reserva**. |
| Resultado esperado | El sistema redirige a **Mis reservas**, muestra **Reserva #[número] registrada correctamente.** y registra Cancha 2, la fecha elegida, horario 18:00–19:00, monto Bs. 25,00 y estado Pendiente. |
| Resultado obtenido / evidencia | **[Insertar imagen de la confirmación y de la reserva en “Mis reservas”.]**<br><br><br> |
| Estado | Pendiente |

| Campo | Detalle |
|---|---|
| Título | **Impedir una segunda reserva en la misma cancha, fecha y franja** |
| ID | TC-S3-011 |
| Prioridad | Crítica |
| Precondición | Existe una reserva activa creada por TC-S3-010 para Cancha 2, próximo martes, 18:00–19:00. Juan Pablo García mantiene la sesión iniciada. |
| Descripción breve | Se consulta la misma cancha y fecha para verificar que la franja ya ocupada no pueda volver a reservarse. |
| Pasos | 1. Abrir **Consultar disponibilidad**.<br>2. Seleccionar **Cancha 2**.<br>3. Seleccionar la fecha usada en TC-S3-010.<br>4. Presionar **Consultar horarios**. |
| Resultado esperado | La franja **18:00 – 19:00** no aparece con el estado **Disponible** ni permite continuar a **Confirmar reserva**. |
| Resultado obtenido / evidencia | **[Insertar imagen de la disponibilidad sin la franja ocupada.]**<br><br><br> |
| Estado | Pendiente |

| Campo | Detalle |
|---|---|
| Título | **Registrar la entrada de una reserva confirmada del día** |
| ID | TC-S3-012 |
| Prioridad | Alta |
| Precondición | María Elena Torres inició sesión. Existe una reserva de hoy en estado Confirmada y sin hora de entrada, visible en **Check-in**. |
| Descripción breve | Se comprueba la atención presencial al registrar la llegada del cliente. |
| Pasos | 1. Abrir el menú **Check-in**.<br>2. Localizar la reserva confirmada del día del cliente indicado en la fila de la tabla.<br>3. Presionar **Registrar entrada** para esa reserva.<br>4. Confirmar la operación en el cuadro de diálogo. |
| Resultado esperado | El sistema informa que la entrada fue registrada; la reserva conserva el estado Confirmada y muestra su hora de check-in. |
| Resultado obtenido / evidencia | **[Insertar imagen antes y después del registro de entrada.]**<br><br><br> |
| Estado | Pendiente |

## HU-06 — Gestionar chatbot NLP

| Campo | Detalle |
|---|---|
| Título | **Consultar disponibilidad mediante el chatbot** |
| ID | TC-S3-013 |
| Prioridad | Alta |
| Precondición | Firefox muestra la página pública del sistema; el widget WallyBot está visible y Cancha 1 tiene una franja futura libre. |
| Descripción breve | Se consulta en lenguaje natural una cancha libre para validar la respuesta conversacional con datos del sistema. |
| Pasos | 1. Presionar el botón flotante **Abrir chat de ayuda**.<br>2. En **Escribe tu mensaje**, escribir `¿Qué canchas están libres mañana?`.<br>3. Presionar **Enviar**. |
| Resultado esperado | WallyBot responde en la conversación con información de disponibilidad para la fecha consultada o indica claramente si no existen franjas libres, sin revelar información interna del sistema. |
| Resultado obtenido / evidencia | **[Insertar imagen de la consulta y de la respuesta de WallyBot.]**<br><br><br> |
| Estado | Pendiente |

| Campo | Detalle |
|---|---|
| Título | **Solicitar inicio de sesión para consultar reservas propias en el chatbot** |
| ID | TC-S3-014 |
| Prioridad | Alta |
| Precondición | No existe sesión iniciada en Firefox y el widget WallyBot está visible en una página pública. |
| Descripción breve | Un visitante intenta consultar sus reservas sin autenticarse. |
| Pasos | 1. Presionar **Abrir chat de ayuda**.<br>2. Escribir `Quiero ver mis reservas` en **Escribe tu mensaje**.<br>3. Presionar **Enviar**. |
| Resultado esperado | WallyBot indica que se requiere iniciar sesión para consultar reservas propias y ofrece una referencia o enlace hacia **Iniciar sesión**; no muestra reservas de ningún usuario. |
| Resultado obtenido / evidencia | **[Insertar imagen de la respuesta que solicita autenticación.]**<br><br><br> |
| Estado | Pendiente |

---

# Sprint 4 — Dashboard, métricas y reportes

## HU-07 — Generar reportes y estadísticas

| Campo | Detalle |
|---|---|
| Título | **Visualizar indicadores administrativos en el panel de control** |
| ID | TC-S4-015 |
| Prioridad | Alta |
| Precondición | Carlos Mendoza inició sesión y existen reservas registradas en la base de datos. |
| Descripción breve | Se verifica que el administrador acceda al dashboard con sus indicadores y gráficos. |
| Pasos | 1. Abrir **Dashboard** desde la navegación administrativa.<br>2. Verificar las tarjetas de resumen visibles en el panel.<br>3. Verificar los gráficos de reservas y de canchas más utilizadas. |
| Resultado esperado | El sistema presenta el **Panel de control** con indicadores de reservas, ingresos y estados, además de gráficos cuyos datos corresponden a las reservas persistidas. |
| Resultado obtenido / evidencia | **[Insertar imagen completa del dashboard.]**<br><br><br> |
| Estado | Pendiente |

| Campo | Detalle |
|---|---|
| Título | **Filtrar el reporte operativo por cancha y rango de fechas** |
| ID | TC-S4-016 |
| Prioridad | Alta |
| Precondición | Carlos Mendoza inició sesión; hay al menos una reserva de Cancha 2 en el período a consultar. |
| Descripción breve | Se aplican filtros del reporte para obtener solo las reservas de Cancha 2 en un rango que incluya la reserva de prueba. |
| Pasos | 1. Abrir **Reportes operativos**.<br>2. En **Desde**, seleccionar la fecha de inicio del período de prueba.<br>3. En **Hasta**, seleccionar la fecha final del período de prueba.<br>4. En **Estado**, seleccionar **Todos**.<br>5. En **Cancha**, seleccionar **Cancha 2**.<br>6. Presionar **Filtrar**. |
| Resultado esperado | La tabla muestra únicamente reservas de Cancha 2 dentro del período indicado y actualiza los totales **Reservas en el rango**, **Ingresos estimados** y **Reservas activas**. |
| Resultado obtenido / evidencia | **[Insertar imagen de los filtros aplicados y la tabla resultante.]**<br><br><br> |
| Estado | Pendiente |

| Campo | Detalle |
|---|---|
| Título | **Exportar el reporte operativo filtrado en formato CSV** |
| ID | TC-S4-017 |
| Prioridad | Media |
| Precondición | Carlos Mendoza inició sesión y la página **Reportes operativos** contiene un resultado filtrado, como el de TC-S4-016. |
| Descripción breve | Se descarga el archivo CSV compatible con Excel conservando los filtros activos. |
| Pasos | 1. Mantener los filtros de TC-S4-016 en **Reportes operativos**.<br>2. Presionar **Exportar CSV**.<br>3. En el cuadro de descarga de Firefox, elegir **Guardar archivo** y confirmar.<br>4. Abrir el archivo descargado con Microsoft Excel. |
| Resultado esperado | Firefox descarga un archivo CSV y Excel muestra las columnas Cliente, Cancha, Fecha, Hora, Monto, Estado y Observaciones con los registros filtrados. |
| Resultado obtenido / evidencia | **[Insertar imagen de la descarga y del archivo abierto en Excel.]**<br><br><br> |
| Estado | Pendiente |

---

## Registro de ejecución

| ID | Resultado | Estado | Responsable | Fecha |
|---|---|---|---|---|
| TC-S1-001 a TC-S4-017 | Pendiente de captura | Pendiente |  |  |
