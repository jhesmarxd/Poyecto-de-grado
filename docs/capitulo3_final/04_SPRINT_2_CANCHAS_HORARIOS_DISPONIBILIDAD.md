# 3.3. IMPLEMENTACIÓN DEL SISTEMA MEDIANTE SPRINTS (Continuación)

---

## 3.3.2. Sprint 2: Canchas y horarios

### 3.3.2.1. Objetivo del sprint
Modelar, parametrizar y disponibilizar la infraestructura deportiva física del complejo de wally. El incremento entregable comprende la administración del catálogo de canchas con sus tarifas e imágenes, el inventario de balones deportivos, el registro de bloqueos o cierres por mantenimiento, la configuración de la matriz de horarios de atención comercial por cancha y el motor de cálculo de disponibilidad de turnos en tiempo real.

---

### 3.3.2.2. Sprint Backlog

El Sprint Backlog de esta segunda iteración se organiza en dos Historias de Usuario oficiales:

| Nº | Historia | Tareas principales | Prioridad |
| :---: | :--- | :--- | :---: |
| **3** | **HU-03: Administrar canchas** | Registrar canchas, editar datos, gestionar balones, programar cierres por mantenimiento, activar/desactivar recursos. | Alta |
| **4** | **HU-04: Gestionar horarios** | Configurar franjas horarias por día, definir turnos, calcular disponibilidad en tiempo real, validar solapamientos. | Alta |

#### A. Detalle de la Historia de Usuario 03 (HU-03)
* **Historia de Usuario (Oficial):**  
  **HU-03: Administrar canchas**  
  *Registrar y administrar canchas, balones y cierres por mantenimiento.*  
  *Narrativa ágil:* Como Administrador, quiero registrar, actualizar y gestionar las canchas de wally, balones deportivos y programar cierres por mantenimiento, para asegurar que los recursos ofertados reflejen la realidad operativa del negocio.
* **Criterios de aceptación:**
  * El Administrador puede registrar nuevas canchas especificando nombre, precio por hora, color identificativo e imagen.
  * El sistema no permite registrar nombres de canchas duplicados.
  * El Administrador puede modificar los datos y estado (activa/inactiva) de una cancha.
  * El Administrador puede registrar y gestionar balones deportivos y sus tarifas de alquiler.
  * El Administrador puede programar cierres temporales o mantenimientos indicando fecha de inicio, fin y motivo.
  * Las canchas inactivas o con cierre programado no deben figurar como disponibles para reservas.
* **Tareas técnicas asociadas:**
  * Diseño de interfaces de catálogo de canchas y balones.
  * Almacenamiento seguro de archivos de imagen en el disco público.
  * Formulario y validación de mantenimiento en `CierreCancha`.
  * Controladores y rutas protegidas (`Admin\CanchaController`, `Admin\BalonController`).
  * Persistencia e integridad referencial en tablas `canchas`, `balones` y `cierres_cancha`.

#### B. Detalle de la Historia de Usuario 04 (HU-04)
* **Historia de Usuario (Oficial):**  
  **HU-04: Gestionar horarios**  
  *Configurar horarios y consultar la disponibilidad de las canchas.*  
  *Narrativa ágil:* Como Administrador o Recepcionista, quiero configurar los horarios comerciales de atención y consultar la disponibilidad de turnos libres en tiempo real, para evitar solapamientos y facilitar la programación de reservas.
* **Criterios de aceptación:**
  * El Administrador puede definir franjas horarias de atención por día de la semana para cada cancha.
  * La hora de inicio comercial debe ser estrictamente menor a la hora de fin.
  * El sistema debe dividir la franja horaria en turnos regulares de 60 minutos.
  * La consulta de disponibilidad por fecha y cancha debe excluir automáticamente turnos ocupados por reservas activas (confirmadas o pendientes).
  * La consulta de disponibilidad debe excluir los turnos afectados por cierres de mantenimiento.
  * Los clientes y recepcionistas pueden consultar los turnos libres de manera ágil y visual.
* **Tareas técnicas asociadas:**
  * Diseño de interfaz interactiva de consulta de turnos con selector de fecha.
  * Lógica algorítmica de cálculo de intervalos en `DisponibilidadService`.
  * Endpoint REST `/api/disponibilidad` optimizado para peticiones asíncronas.
  * Controlador administrativo `Admin\HorarioController`.
  * Pruebas automatizadas de consistencia de turnos.

---

### 3.3.2.3. Diseño y arquitectura del módulo

El módulo se modela utilizando UML con foco exclusivo en los tres actores del sistema:

#### A. Diagrama de Casos de Uso del Sprint 2
```
+-------------------------------------------------------------------------------------------------------+
|                                    MÓDULO: CANCHAS Y HORARIOS                                         |
+-------------------------------------------------------------------------------------------------------+
|                                                                                                       |
|    [Cliente] ------------------\                                                                      |
|                                 \                                                                     |
|    [Recepcionista] -------------> (CU-01: Consultar Disponibilidad de Canchas)                        |
|                                 /                                                                     |
|    [Administrador] ------------/                                                                      |
|         |                                                                                             |
|         +-----------------------> (CU-02: Administrar Catálogo de Canchas)                            |
|         |                                                                                             |
|         +-----------------------> (CU-03: Administrar Balones Deportivos)                             |
|         |                                                                                             |
|         +-----------------------> (CU-04: Programar Cierres de Mantenimiento)                         |
|         |                                                                                             |
|         \-----------------------> (CU-05: Configurar Horarios Comerciales)                           |
|                                                                                                       |
+-------------------------------------------------------------------------------------------------------+
```

#### B. Diagrama de Clases del Módulo
* **Clase `Cancha` (Modelo):** `id: bigint`, `nombre: string`, `precio_por_hora: decimal`, `color: string`, `activa: bool`, `imagen: string`. Relaciones: `horarios(): HasMany`, `cierres(): HasMany`, `reservas(): HasMany`.
* **Clase `Horario` (Modelo):** `id: bigint`, `cancha_id: bigint`, `dia_semana: int`, `hora_inicio: time`, `hora_fin: time`, `activo: bool`.
* **Clase `CierreCancha` (Modelo):** `id: bigint`, `cancha_id: bigint`, `fecha_inicio: date`, `fecha_fin: date`, `hora_inicio: time`, `hora_fin: time`, `motivo: string`.
* **Clase `Balon` (Modelo):** `id: bigint`, `marca: string`, `modelo: string`, `precio_alquiler: decimal`, `estado: string`, `imagen: string`.
* **Clase `DisponibilidadController` (Controlador API):** `verificarDisponibilidad(Request): JsonResponse`.
* **Clase `Admin\CanchaController` (Controlador):** `index(): View`, `create(): View`, `store(Request): RedirectResponse`, `edit(Cancha): View`, `update(Request, Cancha): RedirectResponse`.

#### C. Diagrama de Secuencia: Consulta de Disponibilidad de Canchas
1. **Actor (Cliente o Recepcionista)** -> Selecciona Cancha y Fecha en la interfaz web.
2. **Navegador** -> Envía petición asíncrona `GET /api/disponibilidad?cancha_id=1&fecha=2026-10-20`.
3. **`DisponibilidadController`** -> Recibe parámetros y valida formato de fecha.
4. **`DisponibilidadController`** -> Consulta el horario de atención configurado en `horarios` para el día de la semana.
5. **`DisponibilidadController`** -> Genera los intervalos regulares de 60 minutos entre `hora_inicio` y `hora_fin`.
6. **`DisponibilidadController`** -> Consulta en `reservas` los turnos ocupados (estados pendiente y confirmada).
7. **`DisponibilidadController`** -> Consulta en `cierres_cancha` bloqueos activos por mantenimiento.
8. **`DisponibilidadController`** -> Cruza y excluye los bloques no disponibles, retornando un arreglo estructurado en JSON.
9. **Navegador** -> Renderiza la grilla interactiva habilitando únicamente los turnos disponibles.

#### D. Diagrama de Actividades: Verificación de Turnos Libres
* **Inicio** -> Obtener `cancha_id` y `fecha` ->
* ¿El complejo abre en el día de la semana solicitado?
  * **No:** Retornar lista vacía con mensaje "Día no laborable" -> **Fin**.
  * **Sí:** Desglosar franja en bloques horarios de 60 minutos ->
* Por cada turno generado:
  * ¿Existe una reserva activa en ese turno? -> Marcar "No disponible".
  * ¿Existe un cierre de mantenimiento en ese turno? -> Marcar "Mantenimiento".
  * En caso contrario -> Marcar "Disponible".
* Retornar colección de turnos calculados -> **Fin**.

#### E. Mockups e Implementación de Interfaces
* **Catálogo Administrativo de Canchas (`resources/views/admin/canchas/index.blade.php`):** Grilla visual con tarjetas por cada cancha, visualizando su fotografía, color institucional, tarifa horaria y switch de activación.
* **Matriz de Selección de Turnos (`resources/views/canchas/disponibilidad.blade.php`):** Calendario responsivo con chips interactivos verdes para turnos disponibles y grises deshabilitados para turnos ocupados.

---

### 3.3.2.4. Implementación del código y validaciones

A continuación se presentan los fragmentos de código fuente que implementan la lógica de cálculo de turnos y la persistencia de infraestructura:

#### 1. Motor Algorítmico de Cálculo de Disponibilidad de Horarios
* **Dónde:** Archivo `app/Http/Controllers/DisponibilidadController.php`.
* **Cómo:** Evalúa en tiempo real los horarios base de la cancha, contrastándolos con las reservas activas y los cierres por mantenimiento.
* **Para qué:** Garantiza que no se generen turnos duplicados y expone los bloques disponibles de 60 minutos.

```php
public function verificarDisponibilidad(Request $request)
{
    $request->validate([
        'cancha_id' => 'required|exists:canchas,id',
        'fecha' => 'required|date|after_or_equal:today',
    ]);

    $fecha = Carbon::parse($request->fecha);
    $diaSemana = $fecha->dayOfWeek; // 0 = Domingo, 1 = Lunes, etc.

    // 1. Obtener horario comercial configurado para el día
    $horario = Horario::where('cancha_id', $request->cancha_id)
        ->where('dia_semana', $diaSemana)
        ->where('activo', true)
        ->first();

    if (!$horario) {
        return response()->json(['turnos' => [], 'mensaje' => 'No hay atención comercial en esta fecha.']);
    }

    // 2. Obtener reservas confirmadas o pendientes que ocupan turnos
    $reservasOcupadas = Reserva::where('cancha_id', $request->cancha_id)
        ->where('fecha', $fecha->toDateString())
        ->whereIn('estado', ['pendiente', 'confirmada'])
        ->get(['hora_inicio', 'hora_fin']);

    // 3. Obtener cierres de mantenimiento
    $cierres = CierreCancha::where('cancha_id', $request->cancha_id)
        ->where('fecha_inicio', '<=', $fecha->toDateString())
        ->where('fecha_fin', '>=', $fecha->toDateString())
        ->get(['hora_inicio', 'hora_fin']);

    // 4. Generación y filtrado de turnos de 60 minutos
    $turnos = [];
    $inicio = Carbon::createFromTimeString($horario->hora_inicio);
    $fin = Carbon::createFromTimeString($horario->hora_fin);

    while ($inicio->copy()->addHour()->lte($fin)) {
        $turnoInicio = $inicio->format('H:i:s');
        $turnoFin = $inicio->copy()->addHour()->format('H:i:s');

        $estaOcupado = $reservasOcupadas->contains(function ($reserva) use ($turnoInicio) {
            return $reserva->hora_inicio === $turnoInicio;
        }) || $cierres->contains(function ($cierre) use ($turnoInicio, $turnoFin) {
            return ($turnoInicio >= $cierre->hora_inicio && $turnoFin <= $cierre->hora_fin);
        });

        $turnos[] = [
            'hora_inicio' => $inicio->format('H:i'),
            'hora_fin' => $inicio->copy()->addHour()->format('H:i'),
            'disponible' => !$estaOcupado,
        ];

        $inicio->addHour();
    }

    return response()->json(['turnos' => $turnos]);
}
```

#### 2. Registro y Carga Segura de Canchas
* **Dónde:** Archivo `app/Http/Controllers/Admin/CanchaController.php`.
* **Cómo:** Procesa la carga de imágenes, valida la unicidad del nombre y persiste las propiedades físicas.
* **Para qué:** Permite al Administrador ampliar o actualizar las canchas del complejo deportivo.

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'nombre' => 'required|string|max:100|unique:canchas,nombre',
        'precio_por_hora' => 'required|numeric|min:10|max:500',
        'color' => 'required|string|max:7',
        'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
    ]);

    if ($request->hasFile('imagen')) {
        $path = $request->file('imagen')->store('canchas', 'public');
        $validated['imagen'] = $path;
    }

    $cancha = Cancha::create($validated);

    return redirect()->route('admin.canchas.index')
        ->with('success', "Cancha '{$cancha->nombre}' registrada exitosamente.");
}
```

---

### 3.3.2.5. Pruebas del sprint

#### A. Pruebas de Caja Negra (Funcionales)
| ID Caso | Escenario de Prueba | Datos de Entrada | Resultado Esperado | Resultado Obtenido | Estado |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **CN-07** | Registro de cancha con nombre duplicado | Nombre: "Cancha Principal" (existente) | Bloqueo por validación `unique:canchas,nombre`. | Mensaje: "El nombre de cancha ya está en uso". | **Aprobado** |
| **CN-08** | Consulta de disponibilidad en turno previamente reservado | Cancha 1, Fecha: Mañana, Turno: 18:00 a 19:00 (reservado) | El turno figura con bandera `disponible: false`. | Turno bloqueado visualmente en la grilla. | **Aprobado** |
| **CN-09** | Registro de cierre de cancha por mantenimiento | Cancha 2, Rango: 14:00 a 16:00 | Los bloques entre 14:00 y 16:00 quedan inhabilitados. | Bloques inhabilitados con motivo "Mantenimiento". | **Aprobado** |

#### B. Pruebas Automatizadas con PHPUnit
Se ejecutaron las pruebas automatizadas de catálogo y disponibilidad en `tests/Feature/CanchaManagementTest.php` y `tests/Feature/HorarioManagementTest.php`:
* **Comando ejecutado:** `php artisan test tests/Feature/CanchaManagementTest.php tests/Feature/HorarioManagementTest.php`
* **Pruebas y aserciones validadas:**
  * `test_admin_can_create_a_cancha`: Verifica persistencia y código 302 hacia el listado.
  * `test_cancha_availability_endpoint_calculates_free_slots`: Valida el cálculo de slots libres y estructura JSON retornada.
* **Resultado del reporte:**  
  `PASS  tests/Feature/CanchaManagementTest.php`  
  `PASS  tests/Feature/HorarioManagementTest.php`  
  `Tests: 7 passed (19 assertions)`

---

### 3.3.2.6. Resultado del sprint
Se entregó un incremento de software enfocado en la infraestructura deportiva:
1. Catálogo completo de canchas y balones con asignación de tarifas y carga de imágenes.
2. Módulo de configuración de horarios y registro de cierres de mantenimiento.
3. Motor API de disponibilidad en tiempo real capaz de excluir reservas y bloqueos sin riesgo de colisión de turnos.
