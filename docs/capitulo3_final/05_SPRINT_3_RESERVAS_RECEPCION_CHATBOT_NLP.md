# 3.3. IMPLEMENTACIÓN DEL SISTEMA MEDIANTE SPRINTS (Continuación)

---

## 3.3.3. Sprint 3: Reservas y Asistente Chatbot NLP

### 3.3.3.1. Objetivo del sprint
Implementar el núcleo principal del negocio y el asistente inteligente de atención al cliente. En este sprint se construye el sistema completo de reservas de canchas (para que los clientes reserven en línea o en recepción, alquilen balones, registren su llegada mediante Check-in / Check-out y sincronicen su turno con Google Calendar), junto con el asistente virtual interactivo (Chatbot NLP "WallyBot") que atiende dudas en lenguaje natural sobre horarios, disponibilidad y tarifas consumiendo de manera integrada la API externa de Gemini.

---

### 3.3.3.2. Sprint Backlog

El Sprint Backlog de esta tercera iteración se divide formalmente en dos Historias de Usuario:

| Nº | Historia | Resumen del valor entregado | Prioridad |
| :---: | :--- | :--- | :---: |
| **5** | **HU-05: Administrar reservas** | Permite registrar reservas web y presenciales, alquilar balones, cobrar importes, controlar el ingreso y salida en recepción (Check-in/out) y sincronizar turnos con el calendario. | Muy Alta |
| **6** | **HU-06: Gestión de Chatbot NLP** | Permite a los usuarios realizar consultas en lenguaje común y cotidiano sobre disponibilidad de canchas y tarifas mediante un asistente virtual inteligente en la página web. | Alta |

---

### 3.3.3.3. HISTORIA DE USUARIO 05: ADMINISTRAR RESERVAS (HU-05)

#### Descripción y Criterios de Aceptación
* **Narrativa en lenguaje cotidiano:**  
  *Como Cliente o Recepcionista, quiero registrar la reserva de una cancha de wally para una fecha y hora específica, agregando si deseo un balón deportivo, para asegurar el espacio de juego y llevar el control ordenado de pagos y asistencia presencial.*
* **Criterios de aceptación comprobables:**
  1. El usuario puede ver qué canchas y horarios están libres y elegir el turno que desea.
  2. Si el usuario lo desea, puede sumar el alquiler de un balón deportivo y el sistema calcula el total de forma automática (precio de cancha + precio de balón).
  3. Dos personas no pueden reservar la misma cancha a la misma hora; el sistema bloquea automáticamente los choques de turnos.
  4. Al confirmar la reserva, el sistema envía los datos para agendar el turno en Google Calendar.
  5. En el mostrador de recepción, el recepcionista puede marcar la llegada del grupo (Check-in) y su salida al terminar de jugar (Check-out).
  6. La reserva pasa por estados ordenados y comprensibles: *Pendiente*, *Confirmada*, *En curso*, *Completada* o *Cancelada*.

---

#### A. Casos de Uso de HU-05 (Administrar Reservas)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_CasosUso_HU05.puml`

Este diagrama muestra quiénes usan las funciones de reserva y qué pueden hacer de manera sencilla:

```
+----------------------------------------------------------------------------------------------------+
|                              HISTORIA DE USUARIO 05: ADMINISTRAR RESERVAS                          |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|    [Cliente] -------------------> (CU-05.1: Crear Reserva Online)                                  |
|                                       |                                                            |
|                                       \-- <<include>> ---> (Calcular Importe Total Cancha + Balón) |
|                                       |                                                            |
|                                       \-- <<extend>> ----> (CU-05.5: Sincronizar Google Calendar)  |
|                                                                                                    |
|    [Recepcionista] -------------> (CU-05.2: Registrar Reserva Presencial)                          |
|         |                             |                                                            |
|         |                             \-- <<include>> ---> (Calcular Importe Total Cancha + Balón) |
|         |                             |                                                            |
|         |                             \-- <<extend>> ----> (CU-05.5: Sincronizar Google Calendar)  |
|         |                                                                                          |
|         +-----------------------> (CU-05.3: Registrar Check-in de Jugadores)                       |
|         |                                                                                          |
|         \-----------------------> (CU-05.4: Registrar Check-out y Liquidación Final)               |
|                                                                                                    |
|    [Administrador] -------------> (CU-05.6: Configurar Parámetros y Políticas de Reserva)          |
|                                                                                                    |
+----------------------------------------------------------------------------------------------------+
```

* **Explicación clara del diagrama:**
  * **Cliente:** Entra a la web, selecciona su cancha, turno y balón opcional, y genera su reserva en línea (CU-05.1).
  * **Recepcionista:** Puede registrar clientes que llegan o llaman al mostrador (CU-05.2), marcar cuando entran a la cancha (Check-in en CU-05.3) y marcar cuando terminan de jugar devolviendo los balones (Check-out en CU-05.4).
  * **Administrador:** Define las reglas generales (CU-05.6), como cuántos minutos de tolerancia se esperan antes de liberar un turno si nadie llega.
  * **Calcular Importe Total:** Siempre que se crea una reserva, el sistema suma de forma automática el costo por hora de la cancha elegida y el alquiler del balón seleccionado.
  * **Sincronizar Google Calendar:** Ocurre como un paso adicional automático que crea un evento en el calendario de Google para que el negocio y el cliente tengan su cita registrada.

---

#### B. Diagrama de Clases de HU-05 (Estructura del Código)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_Clases_HU05.puml`

El diagrama de clases describe qué piezas de código y modelos de datos sostienen las reservas:

```
+----------------------------------+            +----------------------------------+
|             Reserva              | 0..*    1  |               User               |
+----------------------------------+----------->+----------------------------------+
| + id: bigint                     |            | + id: bigint                     |
| + user_id: bigint                |            | + name: string                   |
| + cancha_id: bigint              |            | + email: string                  |
| + horario_id: bigint             |            | + rol: enum                      |
| + balon_id: bigint (opcional)    |            +----------------------------------+
| + fecha: date                    |
| + hora_inicio_reserva: time      |            +----------------------------------+
| + hora_fin_reserva: time         | 0..*    1  |              Cancha              |
| + monto_total: decimal           |----------->+----------------------------------+
| + estado: enum                   |            | + id: bigint                     |
| + origen: enum                   |            | + nombre: string                 |
| + checkin_at: timestamp          |            | + precio_por_hora: decimal       |
| + checkout_at: timestamp         |            +----------------------------------+
+----------------------------------+
| + esConfirmada(): bool           |            +----------------------------------+
| + estaActiva(): bool             | 0..*  0..1 |              Balon               |
| + calcularTotal(): float         |----------->+----------------------------------+
+----------------------------------+            | + id: bigint                     |
                  |                             | + nombre: string                 |
                  | 1                           | + precio_alquiler: decimal       |
                  |                             +----------------------------------+
                  v 0..1
+----------------------------------+
|       GoogleCalendarEvent        |
+----------------------------------+
| + id: bigint                     |
| + reserva_id: bigint             |
| + google_event_id: string        |
| + sync_status: string            |
+----------------------------------+
```

* **Controladores y servicios que manejan la lógica:**
  * `ReservaController`: Recibe la solicitud del usuario en la pantalla y coordina la creación de la reserva.
  * `CheckinController`: Maneja los botones de Check-in y Check-out en la pantalla de recepción.
  * `ReservaService`: Contiene la regla que cuida que dos personas no aparten el mismo turno abriendo una transacción protegida en la base de datos.
  * `PoliticaReservasService`: Comprueba que el cliente reserve con la anticipación mínima permitida.
  * `SyncReservationToGoogleCalendar`: Proceso en segundo plano que comunica al sistema con la agenda externa de Google.

---

#### C. Diagrama de Actividades de HU-05 (Flujo del Proceso)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_Actividades_HU05.puml`

Este diagrama representa el viaje completo de una reserva, desde que nace hasta que termina el partido:

1. **Inicio de la reserva:** El cliente inicia en la web o el recepcionista toma la reserva en ventanilla.
2. **Selección:** Se escoge la cancha, fecha, hora y opcionalmente un balón de wally.
3. **Verificación de reglas:** El sistema revisa si el horario cumple con las políticas de tiempo.
4. **Protección contra choques:** El sistema bloquea momentáneamente el registro en la base de datos para cerciorarse de que nadie más ganó el turno en ese mismo segundo.
   * *Si ya fue ocupado:* Informa de inmediato: *"El turno acaba de ser ocupado, por favor elija otro"*.
   * *Si está libre:* Guarda la reserva con estado **Confirmada**, despacha el evento a Google Calendar y entrega el comprobante digital.
5. **Día del juego en recepción:**
   * Al llegar los jugadores, el recepcionista pulsa **Check-in** (el estado cambia a *En curso* y se guarda la hora de entrada).
   * Al terminar de jugar, devuelven el balón y se pulsa **Check-out** (el estado pasa a *Completada*).
   * Si no asisten dentro de los minutos de tolerancia, el turno puede cancelarse.

---

#### D. Diagrama de Secuencia de HU-05 (Paso a Paso en el Sistema)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_Secuencia_HU05.puml`

Muestra cómo conversan entre sí el usuario, la pantalla, el controlador y la base de datos:

1. El **Cliente** pulsa en la pantalla web: *"Confirmar Reserva"*.
2. La **Pantalla** envía los datos al controlador `ReservaController`.
3. El **Controlador** pide ayuda a `ReservaService` para revisar disponibilidad.
4. `ReservaService` abre una transacción segura en la **Base de Datos** y consulta con candado (`lockForUpdate`): *"¿Hay alguien anotado en esta cancha, fecha y hora?"*.
5. La **Base de Datos** responde que el turno está completamente desocupado.
6. El servicio guarda la nueva fila en la tabla `reservas` con el monto sumado y confirma la transacción (`commit`).
7. El servicio envía una orden en segundo plano a la cola de trabajos para que cree el evento en **Google Calendar**.
8. El sistema redirige al usuario a su pantalla de confirmación con su código de reserva.

---

#### E. Modelo de Base de Datos de HU-05 (Diagrama Entidad-Relación)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_ModeloDatos_HU05.puml`

A continuación se muestra cómo se relacionan entre sí las tablas que guardan las reservas:

```
+-----------------------+              +------------------------------------------+
|         users         |              |                 reservas                 |
+-----------------------+              +------------------------------------------+
| * id (PK)             | 1            | * id (PK)                                |
|   name                |              | * user_id (FK) -> users                  |
|   email               +-------+      | * cancha_id (FK) -> canchas              |
|   rol                 |       |      | * horario_id (FK) -> horarios            |
+-----------------------+       |      |   balon_id (FK) -> balones (opcional)    |
                                | N    |   fecha                                  |
+-----------------------+       +----->|   hora_inicio_reserva                    |
|        canchas        | 1            |   hora_fin_reserva                       |
+-----------------------+              |   monto_total                            |
| * id (PK)             +------------->|   estado (pendiente, confirmada, etc.)   |
|   nombre              | N            |   origen (web, recepcion, chatbot)       |
|   precio_por_hora     |              |   checkin_at                             |
+-----------------------+              |   checkout_at                            |
                                       +------------------------------------------+
+-----------------------+ 1                  | 1
|        balones        |                    |
+-----------------------+                    | 0..1
| * id (PK)             +------+             v
|   nombre              |      |       +------------------------------------------+
|   precio_alquiler     |      +------>|          google_calendar_events          |
+-----------------------+ 0..1   N     +------------------------------------------+
                                       | * id (PK)                                |
                                       | * reserva_id (FK) -> reservas            |
                                       |   google_event_id                        |
                                       |   sync_status                            |
                                       +------------------------------------------+
```

---

#### F. Detalle de la Base de Datos para HU-05 (Tablas, Atributos y Normalización)

Para que cualquier persona pueda entender la base de datos sin enredarse con tecnicismos innecesarios, se explica a continuación cómo está construida:

##### 1. Estructura de las tablas y qué guarda cada una
* **Tabla `reservas`:** Es el corazón del sistema de alquileres. Cada fila representa un partido reservado. Guarda qué cliente hizo la reserva, qué cancha eligió, qué día y hora jugará, si pidió prestado un balón de wally, cuánto pagó en total y a qué hora llegó y se fue.
* **Tabla `balones`:** Es el inventario de pelotas deportivas disponibles para alquilar. Guarda el nombre o modelo del balón y su precio de alquiler.
* **Tabla `google_calendar_events`:** Guarda el identificador único del evento generado en Google Calendar para vincular el turno del sistema con la nube de Google.
* **Tabla `configuracion_reservas`:** Guarda los límites de tiempo del negocio (por ejemplo, permitir reservar con hasta 15 días de anticipación y dar 10 minutos de espera al cliente antes de cancelar el turno).
* **Tablas que le brindan datos (padres):** `users` (los datos de la persona que reserva), `canchas` (la cancha elegida) y `horarios` (la franja de atención).

##### 2. Descripción clara de sus atributos (columnas)
En la tabla principal `reservas` encontramos:
* `id`: Número único asignado por el sistema para distinguir esta reserva de todas las demás.
* `user_id`: Número de carnet interno del cliente que hizo la reserva.
* `cancha_id`: Número que indica en cuál cancha se va a jugar (Cancha 1, Cancha 2, etc.).
* `horario_id`: Código del turno horario correspondiente.
* `balon_id`: Número del balón solicitado (puede quedar vacío si el cliente trae su propia pelota).
* `fecha`: Día calendario en que se jugará el partido (ejemplo: `2026-11-05`).
* `hora_inicio_reserva` y `hora_fin_reserva`: Hora exacta en que arranca y termina el turno (ejemplo: de `19:00` a `20:00`).
* `monto_total`: Precio final cobrado (calculado sumando la tarifa por hora de la cancha más el alquiler del balón).
* `estado`: Estado en que se encuentra la reserva (*pendiente*, *confirmada*, *en_curso*, *completada*, *cancelada*).
* `origen`: Por dónde se registró (*web* por el cliente, *recepcion* en mostrador o *chatbot* por el asistente virtual).
* `checkin_at`: Fecha y hora exacta en que los jugadores llegaron a la recepción.
* `checkout_at`: Fecha y hora exacta en que terminaron de jugar y entregaron la cancha.

##### 3. Llaves Primarias (PK) y Llaves Foráneas (FK)
* **Llave Primaria (Primary Key o PK):**  
  Es la columna `id` de cada tabla. Funciona igual que el número de carnet de identidad: es único para cada registro, nunca se repite y permite encontrar una reserva o un balón en una fracción de segundo.
* **Llaves Foráneas (Foreign Keys o FK):**  
  Son los puentes que conectan una tabla con otra para no repetir información:
  * `user_id` en `reservas` apunta a `id` en la tabla `users` (así sabemos de quién es la reserva sin tener que escribir su nombre y correo una y otra vez).
  * `cancha_id` en `reservas` apunta a `id` en la tabla `canchas` (conecta la reserva con su cancha).
  * `horario_id` en `reservas` apunta a `id` en `horarios` (conecta con la franja horaria).
  * `balon_id` en `reservas` apunta a `id` en `balones` (indica qué balón fue alquilado).
  * `reserva_id` en `google_calendar_events` apunta a `id` en `reservas` (conecta el evento de Google con la reserva correspondiente).

##### 4. Explicación simple de las Normalizaciones (1FN, 2FN y 3FN)
Normalizar una base de datos significa organizarla de forma limpia para que la información no esté repetida, no ocupe espacio innecesario y nunca se contradiga. En esta historia de usuario se cumple así:

* **Primera Forma Normal (1FN) - "Un solo dato por casilla y sin listas amontonadas":**  
  En ninguna celda de la tabla `reservas` se guardan listas de valores como *"balon1, balon2"* ni fechas mezcladas. Cada campo contiene un dato único y simple: una sola fecha, una sola hora de inicio y un solo monto numérico.
* **Segunda Forma Normal (2FN) - "Todo el registro depende por completo de su número identificador":**  
  Como la llave primaria de la tabla es simplemente el `id` de la reserva, todos los datos de la fila (el día del juego, el monto cobrado y la hora de entrada) describen y pertenecen exclusivamente a esa reserva específica. No hay datos que dependan solo de "la mitad" de la llave.
* **Tercera Forma Normal (3FN) - "No guardar información prestada de otras tablas":**  
  En la tabla `reservas` **no** se guarda el nombre del cliente, ni su teléfono, ni el color de la cancha, ni la marca del balón. Si guardáramos el teléfono del cliente dentro de cada reserva y el cliente cambiara de número, tendríamos que actualizar cientos de filas pasadas. Por eso, en 3FN solo se guarda el número de enlace (`user_id`, `cancha_id`, `balon_id`). Los detalles reales viven únicamente en sus tablas de origen (`users`, `canchas`, `balones`).

---

### 3.3.3.4. HISTORIA DE USUARIO 06: GESTIÓN DE CHATBOT NLP (HU-06)

#### Descripción y Criterios de Aceptación
* **Narrativa en lenguaje cotidiano:**  
  *Como Cliente del complejo de wally, quiero hacer preguntas al asistente virtual de la página web usando mis propias palabras (como "¿Tienen canchas libres hoy a las 8?" o "¿Cuánto cobran la hora?"), para recibir una respuesta clara al instante sin tener que llamar por teléfono.*
* **Criterios de aceptación comprobables:**
  1. El widget flotante de chat está disponible en la esquina inferior del sitio web para cualquier visitante o usuario.
  2. El asistente entiende intenciones comunes expresadas con distintas palabras (como pedir horarios, consultar precios o pedir instrucciones de reserva).
  3. El sistema se conecta con la API de Gemini para interpretar la frase y luego consulta la base de datos real del negocio para responder si hay canchas libres de verdad.
  4. Cuenta con un filtro de seguridad que bloquea mensajes maliciosos o palabras fuera de lugar antes de procesarlos.
  5. Cada pregunta y respuesta se guarda con su tiempo de respuesta (en milisegundos) y nivel de seguridad para que el administrador pueda auditar el servicio.

---

#### A. Casos de Uso de HU-06 (Gestión de Chatbot NLP)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_CasosUso_HU06.puml`

Este diagrama muestra cómo interactúan el cliente y el administrador con el asistente:

```
+----------------------------------------------------------------------------------------------------+
|                         HISTORIA DE USUARIO 06: GESTIÓN DE CHATBOT NLP (WALLYBOT)                  |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|    [Cliente] -------------------> (CU-06: Gestionar Chatbot NLP - WallyBot)                        |
|                                       |                                                            |
|                                       |-- <<include>> ---> (CU-06.1: Consultar Disponibilidad)     |
|                                       |                                                            |
|                                       |-- <<include>> ---> (CU-06.2: Consultar Precios y Tarifas)  |
|                                       |                                                            |
|                                       \-- <<extend>> ----> (CU-06.3: Guiar a Formulario Reserva)   |
|                                                                                                    |
|    [Administrador] -------------> (CU-06.4: Supervisar Trazas y Conversaciones)                    |
|         |                                                                                          |
|         \-----------------------> (CU-06.5: Auditar Métricas de Confianza y Latencia)              |
|                                                                                                    |
+----------------------------------------------------------------------------------------------------+
```

* **Explicación clara del diagrama:**
  * **Cliente:** Abre el chat y formula sus preguntas. El caso de uso principal (CU-06) abarca automáticamente responder qué canchas están vacías (CU-06.1) y cuánto cuesta jugar (CU-06.2). Si el cliente dice que quiere jugar en ese momento, el bot le extiende una sugerencia con un enlace directo al formulario para que concrete su reserva con los datos precargados (CU-06.3).
  * **Administrador:** No interactúa como jugador en el chat, sino que ingresa al panel de control para revisar el historial de conversaciones (CU-06.4) y comprobar si las respuestas fueron rápidas y acertadas (CU-06.5).

---

#### B. Diagrama de Clases de HU-06 (Estructura del Código)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_Clases_HU06.puml`

Estructura de las clases en el servidor que hacen posible la conversación inteligente:

```
+----------------------------------+ 1         0..* +----------------------------------+
|           Conversacion           |--------------->|          MensajeChatbot          |
+----------------------------------+                +----------------------------------+
| + id: bigint                     |                | + id: bigint                     |
| + user_id: bigint (opcional)     |                | + conversacion_id: bigint        |
| + session_id: string             |                | + emisor: enum ('user', 'bot')   |
| + ip_origen: string              |                | + mensaje: text                  |
| + canal: string                  |                | + intencion_detectada: string    |
| + estado: string                 |                | + confianza: decimal             |
| + contexto: json                 |                | + tiempo_respuesta_ms: int       |
+----------------------------------+                +----------------------------------+
| + contextoDe(key): mixed         |                                  | 1
| + guardarContexto(key, val): void|                                  |
+----------------------------------+                                  v 0..1
                  | 1                               +----------------------------------+
                  |                                 |         ChatbotEjecucion         |
                  | 0..*                            +----------------------------------+
                  +-------------------------------->| + id: bigint                     |
                                                    | + proveedor: string              |
                                                    | + modelo: string                 |
                                                    | + latencia_ms: int               |
                                                    | + tokens_entrada: int            |
                                                    | + tokens_salida: int             |
                                                    | + costo_usd: decimal             |
                                                    +----------------------------------+
```

* **Servicios de lógica:**
  * `ChatbotController`: Es la puerta de entrada; recibe lo que el usuario escribió desde la página web.
  * `ChatbotService`: Coordina todo el proceso: revisa la seguridad, llama a la inteligencia artificial y busca en la base de datos.
  * `PoliticaSeguridadChatbot`: Actúa como un escudo protector; si alguien escribe comandos raros o frases sospechosas, bloquea el mensaje con una advertencia amable.
  * `GeminiNLPService`: Conector oficial que se comunica por internet con la API de Gemini para descifrar el significado de la frase.
  * `DetectorIntencion` y `ExtractorEntidades`: Sacan las partes clave de la oración (por ejemplo, de la frase *"quiero cancha para mañana a las 7pm"*, extraen la fecha de mañana y la hora 19:00).

---

#### C. Diagrama de Actividades de HU-06 (Flujo de la Conversación)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_Actividades_HU06.puml`

Muestra el camino que sigue una pregunta desde que se escribe hasta que se responde:

1. **Ingreso:** El usuario escribe un mensaje en la ventana de chat y presiona "Enviar".
2. **Escudo de seguridad:** El sistema revisa si el mensaje contiene contenido prohibido o intentos de inyección.
   * *Si es peligroso:* Retorna una respuesta de advertencia estándar y detiene el proceso.
   * *Si es seguro:* Continúa al paso siguiente.
3. **Análisis inteligente:** Se clasifica la intención de la persona:
   * *Si es un saludo o ayuda general:* Responde cordialmente explicando qué servicios ofrece el complejo.
   * *Si pregunta precios:* Devuelve la lista clara de tarifas por hora de canchas y balones.
   * *Si pregunta disponibilidad:* El sistema extrae la fecha y hora consultada, revisa en la base de datos local qué canchas están desocupadas y formula una respuesta natural (ejemplo: *"Para mañana a las 19:00 tenemos libre la Cancha 2"*).
4. **Almacenamiento:** Se graba el mensaje del usuario y la respuesta del bot para auditoría.
5. **Entrega:** La respuesta aparece en la pantalla del usuario dentro de una burbuja de diálogo amigable.

---

#### D. Diagrama de Secuencia de HU-06 (Intercambio Técnico Paso a Paso)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_Secuencia_HU06.puml`

Detalla cómo se comunican las partes internas en cuestión de milisegundos:

1. **Usuario** -> Escribe en el widget: *"¿Hay cancha libre mañana a las 7 de la noche?"*.
2. **Widget Web (Frontend)** -> Envía la pregunta en formato JSON mediante `POST /chatbot/mensaje`.
3. **ChatbotController** -> Recibe los datos y los pasa a `ChatbotService`.
4. **ChatbotService** -> Invoca a `PoliticaSeguridadChatbot` para validar que el texto sea seguro.
5. **ChatbotService** -> Envía el texto a `GeminiNLPService` (que se conecta con la API externa de Gemini).
6. **API de Gemini** -> Descifra la frase y responde: *"Intención: consultar_disponibilidad, Fecha: mañana, Hora: 19:00"*.
7. **ChatbotService** -> Consulta al servicio interno `DisponibilidadService` usando esos datos.
8. **Base de Datos Local** -> Verifica los turnos y responde qué canchas no tienen reservas en ese horario.
9. **ChatbotService** -> Construye la respuesta en lenguaje natural y guarda el diálogo en `conversaciones` y `mensajes_chatbot`.
10. **Widget Web** -> Muestra la respuesta en pantalla con un botón opcional para reservar directamente.

---

#### E. Modelo de Base de Datos de HU-06 (Diagrama Entidad-Relación)
*Archivo UML asociado:* `plantuml/sprint3/Sprint3_ModeloDatos_HU06.puml`

Representa las tablas creadas para almacenar las sesiones de charla y su auditoría:

```
+------------------------+ 0..1       N +------------------------------------+
|         users          |------------->|            conversaciones          |
+------------------------+              +------------------------------------+
| * id (PK)              |              | * id (PK)                          |
|   name                 |              |   user_id (FK) -> users (opcional) |
|   email                |              | * session_id (UK)                  |
+------------------------+              |   ip_origen                        |
                                        |   canal (web_widget)               |
                                        |   estado (activa, cerrada)         |
                                        |   contexto (JSON)                  |
                                        +------------------------------------+
                                                           | 1
                                                           |
                                                           | N
                                                           v
+------------------------------------+ 0..1   1 +------------------------------------+
|          chatbot_ejecuciones       |<---------|          mensajes_chatbot          |
+------------------------------------+          +------------------------------------+
| * id (PK)                          |          | * id (PK)                          |
| * conversacion_id (FK)             |          | * conversacion_id (FK)             |
|   mensaje_id (FK)                  |          | * emisor ('user', 'bot')           |
|   proveedor (google_gemini)        |          | * mensaje (texto)                  |
|   modelo (gemini-1.5-flash)        |          |   intencion_detectada              |
|   latencia_ms                      |          |   confianza                        |
|   tokens_entrada / salida          |          |   tiempo_respuesta_ms              |
|   costo_usd                        |          +------------------------------------+
+------------------------------------+
```

---

#### F. Detalle de la Base de Datos para HU-06 (Tablas, Atributos y Normalización)

A continuación se detalla la base de datos conversacional de manera clara y directa:

##### 1. Estructura de las tablas y qué guarda cada una
* **Tabla `conversaciones`:** Representa una sesión de chat abierta entre un visitante y el bot. Como un cliente puede chatear sin haber iniciado sesión todavía, la sesión se identifica mediante un código único de navegador (`session_id`). Guarda además la memoria temporal de la charla (contexto).
* **Tabla `mensajes_chatbot`:** Guarda cada frase individual que se dice dentro de la conversación. Guarda el texto exacto, quién lo dijo (el usuario o el bot), qué intención se detectó y cuántos milisegundos tardó el sistema en responder.
* **Tabla `chatbot_ejecuciones`:** Es la libreta de control técnico. Guarda datos de telemetría: qué modelo de inteligencia artificial respondió (ej. Gemini), cuántas palabras o tokens consumió, cuánto tardó la conexión y el costo aproximado. Esto permite al administrador saber si el asistente es rápido y económico.

##### 2. Descripción clara de sus atributos (columnas)
* En `conversaciones`:
  * `id`: Identificador numérico único de la sesión de chat.
  * `user_id`: Código del usuario registrado (si el usuario no ha iniciado sesión, este campo queda vacío sin ningún problema).
  * `session_id`: Código largo y único que genera el navegador del usuario para no mezclar su conversación con la de otros visitantes.
  * `ip_origen`: Dirección de red desde donde escribe (útil para auditoría de seguridad).
  * `canal`: Por dónde se comunica (por ejemplo, `web_widget`).
  * `estado`: Indica si el chat sigue activo o si ya fue reiniciado/cerrado.
  * `contexto`: Espacio en formato JSON donde el bot recuerda datos dichos en mensajes anteriores (por ejemplo, si el usuario ya dijo que quiere jugar "mañana", el bot guarda esa fecha para cuando el usuario agregue "a las 7").
* En `mensajes_chatbot`:
  * `id`: Número único de cada mensaje.
  * `conversacion_id`: Indica a cuál sesión de chat pertenece el mensaje.
  * `emisor`: Indica quién habló: `user` (el cliente) o `bot` (el asistente).
  * `mensaje`: El contenido del mensaje en texto claro.
  * `intencion_detectada`: La categoría de lo que pidió el usuario (ej. `consultar_disponibilidad` o `consultar_precios`).
  * `confianza`: Un número entre 0 y 1 que indica la seguridad que tuvo el sistema al clasificar la frase (ej. 0.95 significa 95% de seguridad).
  * `tiempo_respuesta_ms`: Tiempo exacto que tardó en entregarse la respuesta, medido en milisegundos.

##### 3. Llaves Primarias (PK) y Llaves Foráneas (FK)
* **Llaves Primarias (PK):**
  * `conversaciones.id`: Llave principal de la conversación.
  * `mensajes_chatbot.id`: Llave principal de cada mensaje.
  * `chatbot_ejecuciones.id`: Llave principal de cada registro de auditoría técnica.
* **Llaves Foráneas (FK):**
  * `conversaciones.user_id` -> Conecta con `users.id` (permite saber qué cliente registrado estuvo conversando).
  * `mensajes_chatbot.conversacion_id` -> Conecta con `conversaciones.id` (asegura que cada mensaje esté enlazado a su respectiva conversación y no quede suelto).
  * `chatbot_ejecuciones.conversacion_id` y `mensaje_id` -> Conectan el registro de costo y latencia con el mensaje exacto que lo originó.

##### 4. Explicación simple de las Normalizaciones (1FN, 2FN y 3FN)
* **Primera Forma Normal (1FN):**  
  Cada mensaje es un registro individual en una fila propia. Nunca se guarda toda la conversación junta en un solo texto enorme separado por guiones o comas. Cada dato (emisor, mensaje, intención, fecha) tiene su propia columna definida.
* **Segunda Forma Normal (2FN):**  
  Todas las columnas de `mensajes_chatbot` describen únicamente a ese mensaje en particular gracias a su llave primaria `id`. No hay columnas que pertenezcan a medias o a otros conceptos ajenos.
* **Tercera Forma Normal (3FN):**  
  Se separa la sesión (`conversaciones`) de los mensajes (`mensajes_chatbot`) y estos de las métricas técnicas (`chatbot_ejecuciones`). Si guardáramos la dirección IP y el navegador del usuario en cada mensaje enviado, estaríamos repitiendo el mismo texto 20 o 30 veces en una sola charla. Al separarlo en `conversaciones`, la información del usuario se guarda una sola vez y los mensajes solo apuntan al `conversacion_id`.

---

### 3.3.3.5. Implementación del código fuente y validaciones

A continuación se presentan los extractos más significativos implementados para dar vida a las dos historias de usuario del Sprint 3:

#### 1. Registro transaccional de reservas con protección contra choques (HU-05)
* **Ubicación:** `app/Http/Controllers/ReservaController.php`.
* **Explicación simple:** Abre una transacción en la base de datos y le pone un candado temporal (`lockForUpdate`) al turno. Si dos personas intentan reservar la misma cancha a la misma hora al mismo tiempo, la primera persona se queda con el turno y a la segunda el sistema le avisa con amabilidad que el horario ya fue ocupado, evitando turnos duplicados.

```php
public function store(Request $request)
{
    $request->validate([
        'cancha_id'   => 'required|exists:canchas,id',
        'fecha'       => 'required|date|after_or_equal:today',
        'hora_inicio' => 'required|date_format:H:i',
        'balon_id'    => 'nullable|exists:balones,id',
    ]);

    $horaFin = Carbon::createFromTimeString($request->hora_inicio)->addHour()->format('H:i');

    return DB::transaction(function () use ($request, $horaFin) {
        // Bloqueo temporal para verificar que nadie gane el turno en el mismo milisegundo
        $ocupado = Reserva::where('cancha_id', $request->cancha_id)
            ->where('fecha', $request->fecha)
            ->where('hora_inicio_reserva', $request->hora_inicio)
            ->whereIn('estado', ['pendiente', 'confirmada', 'en_curso'])
            ->lockForUpdate()
            ->exists();

        if ($ocupado) {
            return back()->withErrors(['turno' => 'El horario seleccionado acaba de ser reservado por otro jugador.']);
        }

        $cancha = Cancha::findOrFail($request->cancha_id);
        $total = $cancha->precio_por_hora;

        // Si alquilo balon, sumamos su tarifa al importe total
        if ($request->filled('balon_id')) {
            $balon = Balon::findOrFail($request->balon_id);
            $total += $balon->precio_alquiler;
        }

        $reserva = Reserva::create([
            'user_id'             => Auth::id(),
            'cancha_id'           => $cancha->id,
            'horario_id'          => $request->horario_id,
            'balon_id'            => $request->balon_id,
            'fecha'               => $request->fecha,
            'hora_inicio_reserva' => $request->hora_inicio,
            'hora_fin_reserva'    => $horaFin,
            'monto_total'         => $total,
            'estado'              => 'confirmada',
            'origen'              => 'web',
        ]);

        // Envio asincrono a Google Calendar
        SyncReservationToGoogleCalendar::dispatch($reserva);

        return redirect()->route('reservas.show', $reserva)
            ->with('success', '¡Reserva confirmada con éxito!');
    });
}
```

#### 2. Procesamiento conversacional de mensajes del Chatbot NLP (HU-06)
* **Ubicación:** `app/Http/Controllers/ChatbotController.php`.
* **Explicación simple:** Recibe el texto que escribió el usuario, consulta a la API de Gemini para comprender qué quiso decir, busca en la base de datos local y responde en lenguaje natural de forma clara e instantánea.

```php
public function responder(Request $request, GeminiNLPService $nlpService)
{
    $request->validate([
        'mensaje'    => 'required|string|max:500',
        'session_id' => 'required|string',
    ]);

    $conversacion = Conversacion::firstOrCreate(
        ['session_id' => $request->session_id],
        ['user_id' => Auth::id(), 'canal' => 'web_widget', 'estado' => 'activa']
    );

    // 1. Deteccion inteligente de la intencion mediante Gemini API
    $nlpResponse = $nlpService->detectIntent($request->session_id, $request->mensaje);
    $intencion = $nlpResponse['intent_name'] ?? 'general';
    $score     = $nlpResponse['confidence'] ?? 0.0;
    $respuestaTexto = $nlpResponse['fulfillment_text'];

    // 2. Registro del mensaje del usuario
    $conversacion->mensajes()->create([
        'emisor'              => 'user',
        'mensaje'             => $request->mensaje,
        'intencion_detectada' => $intencion,
        'confianza'           => $score,
    ]);

    // 3. Si pregunto por disponibilidad, consultamos en la base de datos real
    if ($intencion === 'consultar_disponibilidad' && isset($nlpResponse['parametros']['fecha'])) {
        $fecha = $nlpResponse['parametros']['fecha'];
        $turnosLibres = DisponibilidadService::obtenerResumenTurnos($fecha);
        $respuestaTexto = "Para la fecha {$fecha}, tenemos disponibles los turnos: " . implode(', ', $turnosLibres);
    }

    // 4. Registro de la respuesta del bot
    $conversacion->mensajes()->create([
        'emisor'  => 'bot',
        'mensaje' => $respuestaTexto,
    ]);

    return response()->json([
        'respuesta' => $respuestaTexto,
        'intencion' => $intencion,
    ]);
}
```

---

### 3.3.3.6. Pruebas del sprint (Validación de HU-05 y HU-06)

#### Pruebas Funcionales de Usuario (Caja Negra)
| Caso | Historia | Acción realizada | Resultado esperado | Resultado obtenido | Estado |
| :---: | :---: | :--- | :--- | :--- | :---: |
| **CN-10** | HU-05 | Dos personas intentan reservar la misma cancha a las 19:00 al mismo segundo. | Una reserva se aprueba y a la otra se le avisa que el turno ya fue ganado. | Transacción bloqueó la colisión correctamente sin duplicar reservas. | **Aprobado** |
| **CN-11** | HU-05 | El recepcionista pulsa "Check-in" cuando llega el cliente a jugar. | Se anota la hora de entrada y el estado pasa a "En curso". | Registro guardado con hora exacta en `checkin_at`. | **Aprobado** |
| **CN-12** | HU-06 | El usuario escribe: *"¿Qué canchas tienen libres mañana en la noche?"*. | El bot identifica la fecha y devuelve las canchas libres reales. | Respondió en 1.1 segundos con los horarios reales de la base de datos. | **Aprobado** |
| **CN-13** | HU-06 | Se envía un mensaje malicioso intentando inyectar código al bot. | El filtro de seguridad detecta el intento y responde con mensaje preventivo. | Mensaje rechazado de forma segura sin consultar la base de datos. | **Aprobado** |

#### Pruebas Automatizadas con PHPUnit
Se ejecutaron los tests correspondientes en consola:
* **Comando:** `php artisan test tests/Feature/ReservaTest.php tests/Feature/RecepcionistaTest.php tests/Feature/ChatbotTest.php`
* **Resultado:**  
  `PASS tests/Feature/ReservaTest.php`  
  `PASS tests/Feature/RecepcionistaTest.php`  
  `PASS tests/Feature/ChatbotTest.php`  
  `Tests: 23 passed, 59 assertions`

---

### 3.3.3.7. Resultado del sprint
El Sprint 3 concluyó satisfactoriamente entregando:
1. El proceso completo de reservas online y presencial (HU-05), con cálculo automático de tarifas, adición de balones deportivos, control en recepción (Check-in / Check-out) y sincronización con Google Calendar.
2. El asistente conversacional interactivo "WallyBot" (HU-06), capaz de responder dudas de horarios y tarifas en lenguaje natural con soporte inteligente de Gemini y registro ordenado de conversaciones.
