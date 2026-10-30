# 3.3. IMPLEMENTACIÓN DEL SISTEMA MEDIANTE SPRINTS (Continuación)

---

## 3.3.4. Sprint 4: Dashboard y Reportes

### 3.3.4.1. Objetivo del sprint
Dotar a la administración del complejo deportivo de herramientas visuales y analíticas para la toma de decisiones, supervisión de la ocupación y control contable. En esta cuarta y última iteración se implementa de forma integral la Historia de Usuario **HU-07: Reportes y estadísticas**, unificando en una sola consola ejecutiva el panel interactivo (Dashboard) con indicadores en tiempo real (KPIs), el seguimiento del rendimiento del asistente inteligente (WallyBot) y la generación de reportes detallados con filtros avanzados listos para descargar en PDF y hojas de cálculo Excel.

---

### 3.3.4.2. Sprint Backlog

El Sprint Backlog de esta cuarta iteración concentra el valor analítico en la Historia de Usuario oficial general:

| Nº | Historia | Resumen del valor entregado | Prioridad |
| :---: | :--- | :--- | :---: |
| **7** | **HU-07: Reportes y estadísticas** | Permite al administrador revisar en un solo vistazo cuánto dinero ingresó en el mes, qué canchas se usan más, qué tan bien responde el chatbot y descargar listas filtradas de reservas en archivos PDF y Excel. | Media |

---

### 3.3.4.3. HISTORIA DE USUARIO 07: REPORTES Y ESTADÍSTICAS (HU-07)

#### Descripción y Criterios de Aceptación
* **Narrativa en lenguaje cotidiano:**  
  *Como Administrador del complejo, quiero ver un resumen claro en pantalla con gráficos de ventas, saber cuántos turnos se jugaron hoy, qué cancha es la preferida por la gente, supervisar qué tan rápido responde el chatbot y poder descargar listas de reservas en PDF o Excel según las fechas que yo elija, para llevar las cuentas al día sin hacer cálculos manuales.*
* **Criterios de aceptación comprobables:**
  1. **En el panel principal (Dashboard):** El administrador ve tarjetas destacadas con el dinero total recaudado en el mes, las reservas anotadas para el día de hoy, la cancha con mayor demanda y un gráfico con las reservas de la última semana.
  2. **En las métricas del asistente (WallyBot):** El sistema muestra el promedio de velocidad de respuesta (en milisegundos), las preguntas más repetidas por los clientes y el porcentaje de efectividad.
  3. **En los reportes descargables:** El administrador puede filtrar las reservas eligiendo fecha de inicio, fecha de fin, una cancha en particular o el estado (completada, cancelada, etc.).
  4. **Descargas seguras:** El sistema genera un documento formal en PDF listo para imprimir con encabezados y sumas totales calculadas, y un archivo de Excel para análisis contable.
  5. **Validación lógica:** Si se coloca por error una fecha de fin anterior a la de inicio, el sistema avisa amablemente que el rango no es válido y no produce errores.

---

#### A. Casos de Uso de HU-07 (Reportes y Estadísticas)
*Archivo UML asociado:* `plantuml/sprint4/Sprint4_CasosUso_HU07.puml`

Este diagrama muestra las opciones analíticas que tiene el Administrador:

```
+----------------------------------------------------------------------------------------------------+
|                         HISTORIA DE USUARIO 07: REPORTES Y ESTADÍSTICAS                            |
+----------------------------------------------------------------------------------------------------+
|                                                                                                    |
|    [Administrador] -------------> (CU-07: Reportes y Estadísticas)                                 |
|         |                              |                                                           |
|         |                              |-- <<include>> ---> (CU-07.1: Consultar Métricas y KPI)     |
|         |                              |                                                           |
|         |                              |-- <<include>> ---> (CU-07.2: Ver Gráficos de Ocupación)   |
|         |                              |                                                           |
|         |                              |-- <<include>> ---> (CU-07.3: Filtrar Reservas por Fecha)  |
|         |                              |                                                           |
|         |                              |-- <<include>> ---> (CU-07.5: Auditar Métricas de WallyBot)|
|         |                              |                                                           |
|         |                              \-- <<extend>> ----> (CU-07.4: Exportar a PDF y Excel)      |
|                                                                                                    |
+----------------------------------------------------------------------------------------------------+
```

* **Explicación clara del diagrama:**
  * **Administrador:** Es el único actor autorizado para ingresar a esta consola gerencial.
  * **Consultar Métricas y Gráficos (CU-07.1 y CU-07.2):** Ocurre de forma automática al abrir la pantalla; el sistema suma las reservas activas y dibuja las barras de ocupación.
  * **Filtrar Reservas (CU-07.3):** Permite buscar rápidamente ventas de una semana o un mes en particular.
  * **Exportar a PDF y Excel (CU-07.4):** Es una acción que el administrador elige libremente cuando necesita respaldar las cuentas en papel o guardarlas en su computadora.
  * **Auditar WallyBot (CU-07.5):** Permite ver si el asistente de inteligencia artificial está atendiendo bien a los clientes.

---

#### B. Diagrama de Clases de HU-07 (Estructura del Código Analítico)
*Archivo UML asociado:* `plantuml/sprint4/Sprint4_Clases_HU07.puml`

Representa las clases encargadas de reunir los datos y armar los reportes:

```
+---------------------------------------+
|        AdminDashboardController       |
+---------------------------------------+
| + index(EstadisticasService): View    |
| + metricasGenerales(): JsonResponse   |
+---------------------------------------+
                   | usa
                   v
+---------------------------------------+
|          EstadisticasService          |
+---------------------------------------+
| + metricas(): array                   |
| + reservasActivas(): int              |
| + ingresosEstimados(): float          |
| + reservasPorDia(): Collection        |
| + canchasMasUsadas(): Collection      |
+---------------------------------------+
        | consulta                      | consulta
        v                               v
+-----------------------+       +-----------------------+
|        Reserva        |       |        Cancha         |
+-----------------------+       +-----------------------+

+---------------------------------------+
|         AdminReporteController        |
+---------------------------------------+
| + index(Request): View                |
| + exportarPdf(Request): Response      |
| + exportarExcel(Request): FileResponse|
+---------------------------------------+
                   | usa
                   v
+---------------------------------------+
|             ReporteService            |
+---------------------------------------+
| + rangoFechas(Request): array         |
| + reservasConFiltros(Request): Collec |
| + calcularResumen(Collection): array  |
| + exportarPDF(Request): Response      |
| + exportarExcel(Request): FileResponse|
+---------------------------------------+

+---------------------------------------+        +---------------------------------------+
|     AdminChatbotMetricasController    |------->|         ChatbotMetricasService        |
+---------------------------------------+        +---------------------------------------+
| + index(ChatbotMetricasService): View |        | + resumenGeneral(): array             |
+---------------------------------------+        | + tasaEfectividad(): float            |
                                                 | + latenciaPromedioMs(): int           |
                                                 +---------------------------------------+
```

* **Explicación sencilla de las piezas:**
  * `AdminDashboardController` y `EstadisticasService`: Leen la base de datos para resumir en 4 números clave todo lo que ocurrió en el mes (ventas, ocupación y promedios).
  * `AdminReporteController` y `ReporteService`: Filtran la lista de partidos jugados según lo que pidió el administrador y la convierten en un PDF elegante o un archivo Excel listo para abrir.
  * `AdminChatbotMetricasController` y `ChatbotMetricasService`: Cuentan cuántas preguntas respondió el bot y qué tan rápido lo hizo.

---

#### C. Diagrama de Actividades de HU-07 (Flujo de Consulta y Reportes)
*Archivo UML asociado:* `plantuml/sprint4/Sprint4_Actividades_HU07.puml`

Muestra el camino que sigue el administrador dentro del módulo:

1. **Ingreso:** El administrador entra a la sección de reportes desde el menú principal.
2. **Elección de vista:**
   * **Si abre el Dashboard:** El sistema calcula las ventas del mes, cuenta las reservas de hoy, clasifica qué cancha se usó más y dibuja los gráficos interactivos en pantalla.
   * **Si abre Métricas del Chatbot:** El sistema saca el promedio de velocidad de respuesta, calcula la tasa de acierto y muestra las dudas más frecuentes.
   * **Si abre Reportes de Reservas:** Elige un rango de fechas (ej. del 1 al 15 de octubre) y pulsa *"Filtrar"*. El sistema valida que las fechas sean coherentes, extrae los partidos de la base de datos y muestra la tabla con el total de dinero sumado.
3. **Descarga:** Si el administrador necesita el documento físico, hace clic en *"Exportar PDF"* o *"Exportar Excel"*, y su navegador descarga el archivo al instante.

---

#### D. Diagrama de Secuencia de HU-07 (Generación de Reporte PDF Paso a Paso)
*Archivo UML asociado:* `plantuml/sprint4/Sprint4_Secuencia_HU07.puml`

Muestra qué pasa exactamente cuando se pulsa el botón de descargar reporte:

1. **Administrador** -> Selecciona el rango de fechas en la pantalla y hace clic en *"Exportar PDF"*.
2. **Navegador** -> Envía la orden `GET /admin/reportes/pdf?fecha_inicio=...&fecha_fin=...`.
3. **`AdminReporteController`** -> Valida que las fechas existan y sean lógicas.
4. **`ReporteService`** -> Ejecuta una consulta a la tabla `reservas` extrayendo las filas que coincidan con las fechas y trayendo el nombre del cliente y la cancha.
5. **Base de Datos** -> Devuelve la lista limpia de reservas.
6. **`ReporteService`** -> Suma los montos de todas las reservas confirmadas para obtener el total general.
7. **Librería DomPDF** -> Convierte la tabla y las sumas en un archivo PDF ordenado con formato de hoja membretada.
8. **Navegador** -> Inicia la descarga automática del archivo `.pdf` en la computadora del Administrador.

---

#### E. Modelo de Base de Datos de HU-07 (Diagrama Entidad-Relación Analítico)
*Archivo UML asociado:* `plantuml/sprint4/Sprint4_ModeloDatos_HU07.puml`

Muestra qué tablas de la base de datos se consultan para armar las estadísticas:

```
+------------------------------------+              +------------------------------------+
|               canchas              |              |              reservas              |
+------------------------------------+              +------------------------------------+
| * id (PK)                          | 1            | * id (PK)                          |
|   nombre                           |              | * user_id (FK) -> users            |
|   precio_por_hora                  +------------->| * cancha_id (FK) -> canchas        |
+------------------------------------+ N            | * horario_id (FK)                  |
                                                    |   fecha (para filtrar por fechas)  |
+------------------------------------+              |   monto_total (para sumar ventas)  |
|                users               | 1            |   estado (confirmada, etc.)        |
+------------------------------------+              |   origen (web, recepcion, chatbot) |
| * id (PK)                          +------------->|   created_at                       |
|   name                             | N            +------------------------------------+
|   email                            |
+------------------------------------+
                                                    +------------------------------------+
+------------------------------------+ 1          N |          mensajes_chatbot          |
|            conversaciones          |------------->+------------------------------------+
+------------------------------------+              | * id (PK)                          |
| * id (PK)                          |              | * conversacion_id (FK)             |
| * session_id                       |              |   tiempo_respuesta_ms (latencia)   |
|   created_at                       |              |   intencion_detectada              |
+------------------------------------+              +------------------------------------+
                  | 1                                                  | 1
                  |                                                    |
                  v N                                                  v 0..1
+----------------------------------------------------------------------------------------+
|                                  chatbot_ejecuciones                                   |
+----------------------------------------------------------------------------------------+
| * id (PK)                                                                              |
| * conversacion_id (FK)                                                                 |
|   mensaje_id (FK)                                                                      |
|   latencia_ms (tiempo que tardó la IA)                                                 |
|   tokens_entrada / tokens_salida                                                       |
|   costo_usd                                                                            |
+----------------------------------------------------------------------------------------+
```

---

#### F. Detalle de la Base de Datos para HU-07 (Tablas, Atributos y Normalización)

A continuación se explica cómo trabaja la base de datos en esta historia de usuario para que las consultas de reportes sean rápidas y no se equivoquen:

##### 1. Estructura de las tablas consultadas
El módulo de reportes no necesita crear tablas raras ni duplicadas; se alimenta directamente de la información limpia generada en los módulos anteriores:
* **Tabla `reservas`:** Es la fuente de los ingresos. De aquí el sistema extrae las fechas, calcula cuánto dinero entró (`SUM(monto_total)`) y cuenta cuántos turnos se jugaron (`COUNT(*)`).
* **Tabla `canchas`:** Se consulta para saber el nombre de cada cancha y cuántas veces fue reservada, permitiendo graficar cuál cancha es la más rentable.
* **Tabla `users`:** Permite mostrar en los reportes el nombre del cliente que reservó cada turno.
* **Tablas `conversaciones`, `mensajes_chatbot` y `chatbot_ejecuciones`:** Se analizan para calcular el tiempo promedio que tarda el asistente en contestar y saber qué preguntas son las más habituales.

##### 2. Descripción clara de sus atributos (columnas analizadas)
* En `reservas`:
  * `fecha`: Permite al sistema filtrar rápidamente solo los partidos que caigan dentro del rango elegido por el usuario (ejemplo: del 1 de octubre al 31 de octubre).
  * `monto_total`: El sistema suma todos estos valores numéricos para mostrar en pantalla el total exacto en Bolivianos recaudado en el período.
  * `estado`: Permite filtrar solo las reservas que realmente se cobraron (`confirmada`, `completada`), ignorando las `canceladas` para no inflar los números falsamente.
  * `origen`: Permite saber qué porcentaje de reservas llegaron por la web, cuántas por ventanilla y cuántas mediante el chatbot.
* En `canchas`:
  * `nombre`: Es el texto amigable que sale en los encabezados del reporte (ej. *"Cancha Principal"*).
* En `chatbot_ejecuciones`:
  * `latencia_ms`: Tiempo en milisegundos que tardó el servidor en procesar la respuesta. El sistema promedia todos los tiempos del mes para medir la calidad del servicio.
  * `tokens_entrada` y `tokens_salida`: Permite controlar el consumo de palabras enviadas a la API de Gemini.

##### 3. Llaves Primarias (PK) y Llaves Foráneas (FK)
* **Llaves Primarias (PK):**  
  Cada tabla tiene su `id` único. Gracias a esto, el motor de base de datos puede ubicar un registro entre miles en cuestión de milisegundos sin ralentizar la pantalla.
* **Llaves Foráneas (FK):**  
  Permiten que las consultas de reportes unan los datos de forma instantánea mediante relaciones (`JOIN`):
  * Al unir `reservas.cancha_id = canchas.id`, el reporte pone el nombre bonito de la cancha en lugar de solo mostrar un número.
  * Al unir `reservas.user_id = users.id`, el reporte incluye el nombre de la persona que jugó.
  * Al unir `chatbot_ejecuciones.conversacion_id = conversaciones.id`, el sistema sabe a qué sesión perteneció cada milisegundo registrado.

##### 4. Explicación simple de las Normalizaciones (1FN, 2FN y 3FN) y su beneficio en los reportes
A veces se piensa que normalizar solo sirve para guardar datos, pero su mayor ventaja se ve al momento de sacar reportes:

* **Primera Forma Normal (1FN):**  
  Como cada reserva tiene su propio registro con montos y fechas en celdas numéricas limpias (y no textos combinados), el sistema puede hacer operaciones matemáticas instantáneas como `SUM()` y `AVG()` sin tener que separar textos a mano.
* **Segunda Forma Normal (2FN):**  
  Cada dato de una reserva pertenece a esa reserva en particular. Esto garantiza que cuando se pide un reporte del 15 de octubre, el sistema extrae únicamente los turnos jugados ese día sin mezclar datos de otras transacciones.
* **Tercera Forma Normal (3FN):**  
  Como los datos de la cancha (su nombre y tarifa base) están guardados una sola vez en la tabla `canchas` y no repetidos en cada reserva, los reportes siempre son coherentes. Si la cancha cambia de color o de nombre en la administración, las estadísticas históricas no se corrompen ni muestran datos contradictorios.

---

### 3.3.4.4. Implementación del código fuente y validaciones

A continuación se presentan los fragmentos reales de código que calculan los números del Dashboard y compilan los reportes descargables:

#### 1. Cálculo de indicadores y gráficos para el Dashboard (HU-07)
* **Ubicación:** `app/Http/Controllers/Admin/DashboardController.php`.
* **Explicación simple:** Realiza operaciones matemáticas directas en la base de datos para obtener los ingresos del mes, contar las reservas del día y saber cuál cancha tuvo más éxito sin cargar miles de filas en la memoria del servidor.

```php
public function index()
{
    $hoy = Carbon::today()->toDateString();
    $inicioMes = Carbon::now()->startOfMonth()->toDateString();
    $finMes = Carbon::now()->endOfMonth()->toDateString();

    // 1. Suma total de dinero cobrado en el mes en curso
    $ingresosMes = Reserva::whereBetween('fecha', [$inicioMes, $finMes])
        ->whereIn('estado', ['confirmada', 'completada'])
        ->sum('monto_total');

    // 2. Cantidad de partidos agendados para jugar hoy
    $reservasHoy = Reserva::where('fecha', $hoy)->count();

    // 3. Cancha mas elegida por los clientes
    $canchaTop = Cancha::withCount('reservas')
        ->orderByDesc('reservas_count')
        ->first();

    // 4. Cantidad de reservas por dia en la ultima semana para dibujar el grafico
    $reservasPorDia = Reserva::selectRaw('fecha, COUNT(*) as cantidad')
        ->whereBetween('fecha', [Carbon::now()->subDays(6)->toDateString(), $hoy])
        ->groupBy('fecha')
        ->orderBy('fecha')
        ->pluck('cantidad', 'fecha');

    return view('admin.dashboard', compact('ingresosMes', 'reservasHoy', 'canchaTop', 'reservasPorDia'));
}
```

#### 2. Filtrado y generación de reporte en PDF formal (HU-07)
* **Ubicación:** `app/Http/Controllers/Admin/ReporteController.php`.
* **Explicación simple:** Valida que la fecha de inicio no sea mayor a la final, busca en la base de datos las reservas que coinciden con los filtros del administrador, suma el dinero total y arma un documento PDF formal con diseño listo para imprimir.

```php
public function exportarPdf(Request $request)
{
    $request->validate([
        'fecha_inicio' => 'required|date',
        'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
        'cancha_id'    => 'nullable|exists:canchas,id',
        'estado'       => 'nullable|in:pendiente,confirmada,completada,cancelada',
    ]);

    $query = Reserva::with(['cancha', 'cliente', 'balon'])
        ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);

    if ($request->filled('cancha_id')) {
        $query->where('cancha_id', $request->cancha_id);
    }

    if ($request->filled('estado')) {
        $query->where('estado', $request->estado);
    }

    $reservas = $query->orderBy('fecha', 'asc')->get();
    $totalRecaudado = $reservas->where('estado', '!=', 'cancelada')->sum('monto_total');

    // Crear el archivo PDF usando la plantilla membretada
    $pdf = \PDF::loadView('admin.reportes.pdf', [
        'reservas'       => $reservas,
        'fecha_inicio'   => $request->fecha_inicio,
        'fecha_fin'      => $request->fecha_fin,
        'totalRecaudado' => $totalRecaudado,
        'fechaEmision'   => Carbon::now()->format('d/m/Y H:i'),
    ])->setPaper('letter', 'portrait');

    return $pdf->download("reporte_reservas_{$request->fecha_inicio}_{$request->fecha_fin}.pdf");
}
```

---

### 3.3.4.5. Pruebas del sprint (Validación de HU-07)

#### Pruebas Funcionales de Usuario (Caja Negra)
| Caso | Historia | Acción realizada | Resultado esperado | Resultado obtenido | Estado |
| :---: | :---: | :--- | :--- | :--- | :---: |
| **CN-14** | HU-07 | El administrador abre el Dashboard con 10 reservas completadas en el mes (1,200 Bs en total). | La tarjeta principal de ingresos muestra claramente "1,200.00 Bs". | Cifra calculada de forma exacta contra la base de datos. | **Aprobado** |
| **CN-15** | HU-07 | Se solicita un reporte en PDF desde el 1 al 15 de octubre. | Se descarga un archivo `.pdf` con la lista detallada y el total sumado abajo. | Archivo PDF generado y estructurado con membrete. | **Aprobado** |
| **CN-16** | HU-07 | Se coloca una fecha inicial (ej. 20 de octubre) posterior a la fecha final (10 de octubre). | El sistema no genera reporte y avisa que las fechas son incorrectas. | Formulario retenido con mensaje de advertencia amigable. | **Aprobado** |
| **CN-17** | HU-07 | Se presiona el botón "Exportar a Excel" sobre las reservas filtradas. | Se descarga un archivo `.xlsx` legible en hojas de cálculo. | Archivo descargado con formato tabular correcto. | **Aprobado** |

#### Pruebas Automatizadas con PHPUnit
Se ejecutaron los tests correspondientes a las estadísticas y reportería:
* **Comando:** `php artisan test tests/Feature/DashboardReportesTest.php tests/Feature/ChatbotMetricasTest.php`
* **Resultado:**  
  `PASS tests/Feature/DashboardReportesTest.php`  
  `PASS tests/Feature/ChatbotMetricasTest.php`  
  `Tests: 7 passed, 20 assertions`

---

### 3.3.4.6. Resultado del sprint
El Sprint 4 concluyó con éxito, logrando:
1. La implementación total de la Historia de Usuario **HU-07**, ofreciendo al Administrador un Dashboard interactivo con gráficos, tarjetas de ventas y monitoreo del asistente conversacional en tiempo real.
2. Un módulo completo de reportes que permite filtrar las reservas y exportarlas a documentos PDF y Excel con totales exactos y diseño formal.
3. El cierre exitoso de los cuatro sprints de desarrollo ágil del sistema (HU-01 a HU-07), con todas sus funcionalidades integradas, modeladas en UML y validadas con pruebas.
