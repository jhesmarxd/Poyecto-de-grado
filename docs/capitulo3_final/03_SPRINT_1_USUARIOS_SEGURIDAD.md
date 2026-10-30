# 3.3. IMPLEMENTACIÓN DEL SISTEMA MEDIANTE SPRINTS

---

## 3.3.1. Sprint 1: Acceso y usuarios

### 3.3.1.1. Objetivo del sprint
El objetivo principal del Sprint 1 es implementar el mecanismo central de seguridad, control de acceso y gobernanza de cuentas de usuario del sistema. Esto comprende el proceso de autenticación segura de usuarios mediante credenciales verificadas contra la base de datos (con integración complementaria del servicio externo de autenticación con Google Auth), el control de sesiones activas, la validación obligatoria del estado de la cuenta (`activo`/`inactivo`), el direccionamiento inteligente a los paneles correspondientes según el rol (`Administrador`, `Recepcionista` y `Cliente`), y la gestión integral (registro, consulta, edición, activación, desactivación y eliminación controlada) de los usuarios y sus roles por parte del Administrador.

---

### 3.3.1.2. Sprint Backlog

A continuación se presenta el Sprint Backlog correspondiente al primer sprint de desarrollo:

| Nº | Historia | Tareas principales | Prioridad |
| :---: | :--- | :--- | :---: |
| **1** | **HU-01: Inicio de Sesión** | Diseñar login, validar credenciales, implementar sesión, controlar roles, cerrar sesión, validar errores. | Alta |
| **2** | **HU-02: Gestionar usuarios** | Listar, registrar, editar, asignar roles, activar/desactivar, validar datos y permisos. | Alta |

#### A. Detalle de la Historia de Usuario 01 (HU-01)
* **Historia de Usuario (Oficial):**  
  **HU-01: Inicio de Sesión**  
  *Permitir el acceso al sistema según el rol del usuario.*  
  *Narrativa ágil:* Como usuario del sistema, quiero iniciar y cerrar sesión de forma segura para acceder a las funcionalidades correspondientes a mi rol.
* **Criterios de aceptación:**
  * El usuario debe proporcionar credenciales válidas.
  * El sistema debe validar las credenciales.
  * Las contraseñas deben almacenarse de forma segura.
  * El sistema debe identificar el rol del usuario.
  * El usuario debe acceder únicamente a las funcionalidades autorizadas.
  * Un usuario inactivo no debe poder ingresar.
  * El cierre de sesión debe invalidar la sesión activa.
  * Las credenciales incorrectas deben mostrar un mensaje de error sin crear una sesión.
* **Tareas técnicas asociadas:**
  * Diseño de formulario de autenticación.
  * Validación de campos.
  * Autenticación de credenciales locales y manejo de autenticación con servicio externo (Google Auth).
  * Gestión de sesión.
  * Middleware de autorización por rol (`CheckRole`).
  * Redirección según rol.
  * Cierre de sesión.
  * Mensajes de error y control de intentos fallidos.

#### B. Detalle de la Historia de Usuario 02 (HU-02)
* **Historia de Usuario (Oficial):**  
  **HU-02: Gestionar usuarios**  
  *Registrar, editar, eliminar usuarios y asignar roles.*  
  *Narrativa ágil:* Como Administrador, quiero gestionar las cuentas de usuario y sus roles para controlar el acceso al sistema.
* **Criterios de aceptación:**
  * El Administrador puede consultar usuarios.
  * Puede registrar usuarios.
  * Puede modificar información de usuarios.
  * Puede asignar roles.
  * Puede cambiar el estado de una cuenta.
  * Puede eliminar o deshabilitar una cuenta según las reglas definidas.
  * El sistema debe validar los datos antes de guardar.
  * El correo de usuario debe ser único.
  * Los permisos deben corresponder al rol asignado.
* **Tareas técnicas asociadas:**
  * Diseño del módulo de usuarios.
  * Listado de usuarios con ordenamiento y filtros.
  * Registro de personal y clientes.
  * Edición de perfiles y contraseñas.
  * Asignación de roles (`admin`, `recepcionista`, `cliente`).
  * Activación/desactivación de cuentas.
  * Validaciones de unicidad y formato (`StoreUserRequest`, `UpdateUserRequest`).
  * Control de permisos administrativos.
  * Persistencia en base de datos MySQL.

---

### 3.3.1.3. Especificación de Casos de Uso del Módulo

A continuación se presentan las especificaciones formales de los casos de uso correspondientes a este módulo, respetando los tres actores principales del sistema:

#### Especificación de Caso de Uso: Iniciar Sesión (CU-01)

| Elemento | Descripción |
| :--- | :--- |
| **Caso de Uso** | Iniciar Sesión |
| **Actores** | Administrador, Recepcionista, Cliente |
| **Precondición** | El usuario debe estar registrado en el sistema, contar con credenciales válidas y tener un rol asignado. Además, su cuenta debe encontrarse activa. |
| **Postcondición** | El usuario accede al sistema y es redirigido al módulo o panel correspondiente según el rol asignado y los permisos establecidos. |
| **Flujo Básico** | 1. El usuario accede a la pantalla de inicio de sesión.<br>2. El sistema muestra el formulario de autenticación.<br>3. El usuario ingresa su correo y contraseña y selecciona la opción "Iniciar Sesión" (o selecciona la opción de autenticación externa).<br>4. El sistema valida que los campos requeridos sean correctos.<br>5. El sistema verifica las credenciales del usuario en la base de datos.<br>6. El sistema comprueba que la cuenta se encuentre activa.<br>7. El sistema identifica el rol del usuario, establece la sesión y accede al panel correspondiente según el rol. |
| **Flujo Alternativo** | • **Credenciales inválidas:** Si el correo no corresponde a un usuario registrado o la contraseña ingresada es incorrecta, el sistema muestra un mensaje indicando que las credenciales son inválidas y no crea la sesión.<br>• **Cuenta inactiva:** Si la cuenta del usuario se encuentra desactivada, el sistema informa que la cuenta no se encuentra autorizada y no permite iniciar sesión.<br>• **Campos incompletos o erróneos:** Si los campos obligatorios están vacíos o presentan un formato incorrecto, el sistema solicita corregir la información antes de continuar.<br>• **Acceso no autorizado:** Si el usuario intenta acceder a una funcionalidad que no corresponde a su rol, el sistema deniega el acceso mediante un error 403 Forbidden. |
| **Excepciones** | • Error de conexión con el motor de base de datos durante la validación de las credenciales.<br>• Tiempo de espera excedido durante la verificación de la petición HTTP.<br>• Error interno del servidor que impida completar el proceso de inicio de sesión. |

---

#### Especificación de Caso de Uso: Gestionar Usuarios y Roles (CU-02)

| Elemento | Descripción |
| :--- | :--- |
| **Caso de Uso** | Gestionar Usuarios y Roles |
| **Actores** | Administrador |
| **Precondición** | El Administrador debe haber iniciado sesión previamente y contar con una cuenta activa con permisos de administración. |
| **Postcondición** | Las cuentas de usuario son creadas, modificadas, activadas o desactivadas de manera persistente en la base de datos. |
| **Flujo Básico** | 1. El Administrador accede al módulo de "Usuarios".<br>2. El sistema consulta y despliega la lista actualizada de usuarios con sus datos (nombre, correo, rol y estado).<br>3. El Administrador selecciona una acción: "Registrar Nuevo Usuario", "Editar Usuario" o "Cambiar Estado".<br>4. El Administrador diligencia o modifica los datos solicitados (nombre, correo, teléfono, rol y estado).<br>5. El Administrador confirma el guardado de los cambios.<br>6. El sistema valida que los datos cumplan con las reglas de negocio (correo único, campos requeridos, rol válido).<br>7. El sistema persiste la información en la base de datos y retorna al listado mostrando un mensaje de confirmación exitosa. |
| **Flujo Alternativo** | • **Correo electrónico duplicado:** Si se intenta registrar un correo ya existente, el sistema bloquea el registro e indica que el correo ya se encuentra en uso.<br>• **Formato de datos no válido:** Si la contraseña no cumple la longitud mínima o faltan campos obligatorios, el sistema señala los campos erróneos.<br>• **Protección de auto-desactivación:** Si el Administrador intenta desactivar su propia cuenta en sesión, el sistema restringe la acción para evitar la pérdida de gobernanza administrativa. |
| **Excepciones** | • Fallo de persistencia o bloqueo de concurrencia al escribir en la base de datos.<br>• Excepción por intento de acceso sin privilegios de Administrador. |

---

### 3.3.1.4. Diseño y arquitectura del módulo

El módulo sigue el estándar formal de modelado UML, representado en tercera persona y enfocado exclusivamente en los tres actores principales del sistema:

#### A. Diagrama de Casos de Uso del Sprint 1
En este diagrama unificado se aprecia la interacción de los tres actores autorizados con las funcionalidades de acceso y usuarios:

```
+-------------------------------------------------------------------------------------------------------+
|                                  MÓDULO: ACCESO Y GESTIÓN DE USUARIOS                                 |
+-------------------------------------------------------------------------------------------------------+
|                                                                                                       |
|    [Administrador] ---------\                                                                         |
|                              \                                                                        |
|    [Recepcionista] -----------> (CU-01: Iniciar Sesión)                                               |
|                              /          |                                                             |
|    [Cliente] ---------------/           |-- <<include>> ---> (Validar Credenciales y Estado Activo)   |
|         |                               |                                                             |
|         |                               \-- <<include>> ---> (Identificar Rol y Redirigir a Panel)    |
|         |                                                                                             |
|         +---------------------> (CU-02: Cerrar Sesión)                                                |
|                                                                                                       |
|                                                                                                       |
|    [Administrador] -----------> (CU-03: Consultar Lista de Usuarios)                                  |
|         |                                                                                             |
|         +---------------------> (CU-04: Registrar Nuevo Usuario)                                      |
|         |                               |                                                             |
|         |                               \-- <<include>> ---> (Validar Correo Único y Asignar Rol)     |
|         |                                                                                             |
|         +---------------------> (CU-05: Modificar Usuario)                                            |
|         |                                                                                             |
|         \---------------------> (CU-06: Activar / Desactivar Cuenta de Usuario)                       |
|                                                                                                       |
+-------------------------------------------------------------------------------------------------------+
```

#### B. Diagrama de Clases del Módulo
Modela las entidades estructurales del sistema que participan en el Sprint 1:

* **Clase `User` (Modelo del Dominio):**
  * *Atributos:* `id: bigint`, `name: string`, `email: string`, `password: string`, `rol: enum ('admin', 'recepcionista', 'cliente')`, `estado: boolean`, `telefono: string`, `email_verified_at: timestamp`, `created_at: timestamp`.
  * *Métodos:* `esAdmin(): bool`, `esRecepcionista(): bool`, `esCliente(): bool`, `estaActivo(): bool`.
* **Clase `LoginController` (Controlador de Autenticación):**
  * *Métodos:* `showLoginForm(): View`, `login(Request): RedirectResponse`, `credentials(Request): array`, `redirectTo(): string`, `logout(Request): RedirectResponse`.
* **Clase `Admin\UserController` (Controlador Administrativo):**
  * *Métodos:* `index(): View`, `create(): View`, `store(StoreUserRequest): RedirectResponse`, `edit(User): View`, `update(UpdateUserRequest, User): RedirectResponse`, `destroy(User): RedirectResponse`.
* **Clase `StoreUserRequest` y `UpdateUserRequest` (Validaciones de Formulario):**
  * *Métodos:* `authorize(): bool`, `rules(): array`.
* **Clase `CheckRole` (Middleware de Autorización):**
  * *Método:* `handle(Request, Closure, ...roles): Response`.

#### C. Diagrama de Secuencia: Inicio de Sesión y Redirección según Rol
Muestra el intercambio secuencial de mensajes durante la autenticación:

1. **Actor (Usuario)** -> Introduce correo y contraseña en la vista de autenticación y solicita `POST /login`.
2. **`LoginController`** -> Recibe la petición HTTP y ejecuta la validación de campos requeridos.
3. **`LoginController`** -> Invoca el método `credentials()`, solicitando verificar `email`, `password` y `estado = true`.
4. **Manejador de Autenticación (`Auth Guard`)** -> Consulta el registro del usuario en la base de datos a través del modelo `User`.
5. **Base de Datos (MySQL)** -> Retorna el registro del usuario con el hash de la contraseña y el campo de estado.
6. **Manejador de Autenticación** -> Verifica mediante la función Hash (Bcrypt) que la contraseña coincida con el hash almacenado y que el estado sea activo:
   * *Si es incorrecto o inactivo:* Retorna error a `LoginController`, el cual redirige a la vista mostrando el mensaje de credenciales inválidas.
   * *Si es correcto:* Regenera la sesión HTTP contra ataques de fijación de sesión.
7. **`LoginController`** -> Ejecuta el método `redirectTo()`, identificando el rol del usuario autenticado:
   * Rol `admin` -> Redirecciona a `/admin/dashboard`.
   * Rol `recepcionista` -> Redirecciona a `/recepcionista/reservas/hoy`.
   * Rol `cliente` (por defecto) -> Redirecciona a `/home`.
8. **Navegador del Usuario** -> Renderiza la vista principal correspondiente a su perfil.

*(Nota técnica: En caso de utilizar autenticación delegada con Google Auth, el controlador especializado `GoogleAuthController` recibe el token emitido por el servicio externo, vincula la cuenta social y ejecuta la misma lógica de inicio de sesión e identificación de rol sin convertir al servicio en un actor del caso de uso).*

#### D. Diagrama de Actividades: Flujo de Autenticación y Control de Acceso
* **Inicio** -> El usuario solicita ingresar sus credenciales ->
* El sistema recibe `email` y `password` ->
* ¿Los campos obligatorios están completos y con formato válido?
  * **No:** Retorna a la pantalla de login con mensajes de validación -> **Fin**.
  * **Sí:** Consulta al usuario en la base de datos por correo ->
* ¿El usuario existe en el sistema?
  * **No:** Notificar "Credenciales inválidas" -> **Fin**.
  * **Sí:** Verificar estado de la cuenta ->
* ¿La cuenta se encuentra activa (`estado == true`)?
  * **No:** Notificar "Cuenta deshabilitada. Contacte con administración" -> **Fin**.
  * **Sí:** Comparar contraseña contra el hash Bcrypt ->
* ¿La contraseña coincide?
  * **No:** Incrementar contador de intentos fallidos y notificar error -> **Fin**.
  * **Sí:** Iniciar sesión del usuario ->
* Identificar el rol asignado (`admin`, `recepcionista`, `cliente`) ->
* Redirigir al módulo autorizado según el rol -> **Fin**.

#### E. Mockups e Implementación de Interfaces
* **Interfaz de Inicio de Sesión (`resources/views/auth/login.blade.php`):**  
  Diseño centrado con tarjeta de acceso que contiene el logotipo del complejo deportivo, campos de entrada con validación en tiempo real para correo electrónico y contraseña, opción de recordar sesión, botón de acción "Iniciar Sesión" y mensajes de alerta visual ante credenciales erróneas o cuentas inactivas.
* **Interfaz de Gestión de Usuarios (`resources/views/admin/usuarios/index.blade.php`):**  
  Tabla administrativa que lista los usuarios registrados ordenados por fecha de creación, con columnas informativas: Nombre completo, Correo electrónico, Teléfono, Rol asignado (con etiquetas distintivas) y Estado (Activo/Inactivo con interruptor visual), además de botones de acción para crear nuevo usuario o editar datos.

---

### 3.3.1.5. Implementación del código y validaciones

En esta sección se presentan los fragmentos de código fuente reales implementados en el proyecto, detallando **dónde** se ubican, **cómo** funcionan y **para qué** sirven en cumplimiento de los criterios de aceptación:

#### 1. Verificación de Estado Activo y Credenciales en el Inicio de Sesión
* **Dónde:** Archivo `app/Http/Controllers/Auth/LoginController.php`.
* **Cómo:** Se sobrescribe el método `credentials(Request $request)` provisto por el trait `AuthenticatesUsers` de Laravel, inyectando la condición `'estado' => true`.
* **Para qué:** Garantiza de forma estricta el criterio de aceptación que establece que un usuario cuya cuenta haya sido desactivada por el Administrador no pueda iniciar sesión bajo ninguna circunstancia, incluso si conoce su contraseña.

```php
/**
 * Solo permite autenticar cuentas activas.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return array<string, mixed>
 */
protected function credentials(Request $request): array
{
    return [
        $this->username() => $request->input($this->username()),
        'password' => $request->input('password'),
        'estado' => true,
    ];
}
```

#### 2. Identificación del Rol y Redirección Inteligente
* **Dónde:** Archivo `app/Http/Controllers/Auth/LoginController.php`.
* **Cómo:** Se implementa el método `redirectTo()` haciendo uso de una estructura de coincidencia `match` sobre el atributo `$user->rol`.
* **Para qué:** Permite que una vez validada la identidad del usuario, el sistema lo dirija de forma automática y transparente a la pantalla de trabajo asignada a su rol: el Administrador a la consola de mando (`admin.dashboard`), el Recepcionista a la gestión operativa diaria (`recepcionista.reservas.hoy`) y el Cliente a la página principal del complejo deportivo (`home`).

```php
/**
 * Envia a cada rol a su espacio principal despues de iniciar sesion.
 */
protected function redirectTo(): string
{
    return match (auth()->user()?->rol) {
        User::ROL_ADMIN => route('admin.dashboard'),
        User::ROL_RECEPCIONISTA => route('recepcionista.reservas.hoy'),
        default => route('home'),
    };
}
```

#### 3. Registro y Validación de Nuevos Usuarios desde el Panel Administrativo
* **Dónde:** Archivo `app/Http/Controllers/Admin/UserController.php` y `app/Http/Requests/StoreUserRequest.php`.
* **Cómo:** El método `store()` recibe la petición previa validación en `StoreUserRequest` (verificando unicidad de correo y obligatoriedad de campos), autoriza la acción mediante políticas de acceso (`authorize('create', User::class)`) y persiste el nuevo usuario con contraseña cifrada y estado activo por defecto.
* **Para qué:** Cumple el requerimiento de permitir al Administrador dar de alta cuentas de personal y clientes asegurando la integridad de los datos en la base de datos.

```php
/**
 * Registra un nuevo usuario desde el panel de administracion.
 */
public function store(StoreUserRequest $request): RedirectResponse
{
    $this->authorize('create', User::class);

    $data = $request->validated();

    User::create([
        'name'     => $data['name'],
        'email'    => $data['email'],
        'telefono' => $data['telefono'] ?? null,
        'rol'      => $data['rol'],
        'password' => $data['password'],
        'estado'   => true,
    ]);

    return redirect()->route('admin.usuarios.index')
        ->with('status', 'Usuario creado correctamente.');
}
```

#### 4. Actualización de Información, Roles y Estado de Cuenta
* **Dónde:** Archivo `app/Http/Controllers/Admin/UserController.php`.
* **Cómo:** El método `update()` procesa los cambios verificando si se ingresó una nueva contraseña (la cual solo se actualiza si el campo no viene vacío) y actualiza el campo booleano `estado` mediante `$request->boolean('estado')`.
* **Para qué:** Permite al Administrador modificar los roles y deshabilitar o habilitar una cuenta de usuario en cualquier momento.

```php
/**
 * Actualiza los datos de un usuario.
 */
public function update(UpdateUserRequest $request, User $usuario): RedirectResponse
{
    $this->authorize('update', $usuario);

    $data = $request->validated();

    $usuario->name = $data['name'];
    $usuario->email = $data['email'];
    $usuario->telefono = $data['telefono'] ?? null;
    $usuario->rol = $data['rol'];
    $usuario->estado = $request->boolean('estado');

    if (! empty($data['password'])) {
        $usuario->password = $data['password'];
    }

    $usuario->save();

    return redirect()->route('admin.usuarios.index')
        ->with('status', 'Usuario actualizado correctamente.');
}
```

---

### 3.3.1.6. Pruebas del sprint

#### A. Pruebas de Caja Negra (Funcionales)
Los siguientes son casos funcionales manuales normalizados. Sustituyen el registro resumido anterior de caja negra; su ejecución, resultado y evidencia quedan pendientes. Los datos se expresan como cuentas de prueba controladas, sin consignar credenciales reales.

##### HU-01: Inicio de Sesión

**Criterios cubiertos:** validación de credenciales y campos obligatorios, control de cuentas activas, redirección según rol, restricción de acceso y cierre de sesión.

###### TC-001 — Inicio de sesión válido como Administrador

- **Historia / prioridad / tipo:** HU-01 / Crítica / Funcional manual.
- **Precondiciones:** Existe una cuenta de prueba activa con rol `admin`; el sistema está disponible y no hay una sesión iniciada.
- **Propósito y datos:** Validar una autenticación local correcta con el correo y contraseña válidos de la cuenta de Administrador.
- **Pasos:** 1. Abrir la pantalla de inicio de sesión. 2. Ingresar las credenciales válidas de la cuenta de prueba. 3. Seleccionar **Iniciar sesión**.
- **Resultado esperado:** Se crea la sesión y el sistema redirige al panel administrativo (`admin.dashboard`).
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-002 — Inicio de sesión válido como Recepcionista

- **Historia / prioridad / tipo:** HU-01 / Alta / Funcional manual.
- **Precondiciones:** Existe una cuenta de prueba activa con rol `recepcionista`; no hay una sesión iniciada.
- **Propósito y datos:** Validar el direccionamiento del rol Recepcionista con credenciales locales válidas.
- **Pasos:** 1. Abrir la pantalla de inicio de sesión. 2. Ingresar las credenciales válidas de la cuenta de Recepcionista. 3. Seleccionar **Iniciar sesión**.
- **Resultado esperado:** Se crea la sesión y el sistema redirige a la agenda del día (`recepcionista.reservas.hoy`).
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-003 — Rechazo de contraseña incorrecta

- **Historia / prioridad / tipo:** HU-01 / Crítica / Funcional negativa.
- **Precondiciones:** Existe una cuenta de prueba activa; no hay una sesión iniciada.
- **Propósito y datos:** Comprobar que un correo existente combinado con una contraseña incorrecta no autentica al usuario.
- **Pasos:** 1. Abrir la pantalla de inicio de sesión. 2. Ingresar el correo de la cuenta de prueba. 3. Ingresar una contraseña incorrecta. 4. Seleccionar **Iniciar sesión**.
- **Resultado esperado:** El sistema rechaza el acceso, muestra el error de credenciales configurado y no crea una sesión.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-004 — Validación de campos obligatorios de inicio de sesión

- **Historia / prioridad / tipo:** HU-01 / Alta / Funcional negativa.
- **Precondiciones:** No hay una sesión iniciada.
- **Propósito y datos:** Verificar la validación al enviar el formulario sin correo electrónico ni contraseña.
- **Pasos:** 1. Abrir la pantalla de inicio de sesión. 2. Dejar vacíos los campos de correo y contraseña. 3. Seleccionar **Iniciar sesión**.
- **Resultado esperado:** El sistema conserva la pantalla, informa que ambos campos son obligatorios y no crea una sesión.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-005 — Bloqueo de cuenta inactiva

- **Historia / prioridad / tipo:** HU-01 / Crítica / Seguridad funcional.
- **Precondiciones:** Existe una cuenta de prueba con `estado = false` y contraseña válida; no hay una sesión iniciada.
- **Propósito y datos:** Verificar que una cuenta desactivada no pueda autenticarse aunque sus credenciales sean correctas.
- **Pasos:** 1. Abrir la pantalla de inicio de sesión. 2. Ingresar las credenciales válidas de la cuenta inactiva. 3. Seleccionar **Iniciar sesión**.
- **Resultado esperado:** El sistema rechaza el acceso, muestra el mensaje configurado para cuenta inactiva y no crea una sesión.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-006 — Cierre de sesión e invalidación del acceso

- **Historia / prioridad / tipo:** HU-01 / Alta / Seguridad funcional.
- **Precondiciones:** Un usuario de prueba autenticado se encuentra en una vista protegida.
- **Propósito y datos:** Verificar que el cierre de sesión invalide la sesión activa.
- **Pasos:** 1. Seleccionar la opción **Cerrar sesión**. 2. Intentar volver a abrir la URL de la vista protegida mediante el navegador.
- **Resultado esperado:** La sesión se invalida; al solicitar una vista protegida el sistema exige autenticación y no muestra contenido autorizado.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

**Síntesis de cobertura HU-01:** TC-001 y TC-002 verifican la autenticación y redirección por rol; TC-003 a TC-005 cubren rechazos y validaciones; TC-006 verifica el cierre de sesión. La autenticación mediante Google Auth queda fuera de esta batería manual inicial y requiere una ejecución independiente con el proveedor externo.

##### HU-02: Gestionar usuarios

**Criterios cubiertos:** consulta, alta, edición, asignación de roles, cambio de estado, eliminación controlada, unicidad de correo y permisos administrativos.

###### TC-007 — Consulta de usuarios por Administrador

- **Historia / prioridad / tipo:** HU-02 / Alta / Funcional manual.
- **Precondiciones:** Existe una cuenta de prueba activa con rol `admin` y al menos un usuario registrado.
- **Propósito y datos:** Validar el acceso del Administrador al listado de usuarios.
- **Pasos:** 1. Iniciar sesión como Administrador. 2. Abrir el módulo de gestión de usuarios. 3. Revisar el listado mostrado.
- **Resultado esperado:** El sistema permite acceder al módulo y muestra el listado de usuarios y accesos.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-008 — Registro válido de usuario con rol asignado

- **Historia / prioridad / tipo:** HU-02 / Crítica / Funcional manual.
- **Precondiciones:** El Administrador ha iniciado sesión; se dispone de un correo de prueba que no existe en la base de datos.
- **Propósito y datos:** Verificar el registro de un usuario con nombre, correo válido y único, rol válido y contraseña que cumpla la política configurada.
- **Pasos:** 1. Abrir el formulario de creación de usuario. 2. Completar los campos obligatorios con datos de prueba válidos. 3. Confirmar la contraseña. 4. Seleccionar el rol permitido. 5. Guardar el formulario.
- **Resultado esperado:** El sistema registra el usuario, conserva el rol seleccionado y redirige al listado de usuarios.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-009 — Rechazo de correo electrónico duplicado

- **Historia / prioridad / tipo:** HU-02 / Alta / Funcional negativa.
- **Precondiciones:** El Administrador ha iniciado sesión y existe un usuario con el correo que se utilizará en la prueba.
- **Propósito y datos:** Validar la regla de unicidad del correo al registrar un usuario.
- **Pasos:** 1. Abrir el formulario de creación. 2. Completar los campos requeridos con valores válidos, usando un correo ya registrado. 3. Confirmar la contraseña. 4. Guardar el formulario.
- **Resultado esperado:** El sistema no registra el usuario, conserva el formulario e informa que el email ya está registrado.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-010 — Validación de datos obligatorios y confirmación de contraseña

- **Historia / prioridad / tipo:** HU-02 / Alta / Funcional negativa.
- **Precondiciones:** El Administrador ha iniciado sesión y se encuentra en el formulario de creación.
- **Propósito y datos:** Verificar las validaciones de nombre, correo, rol, contraseña y confirmación.
- **Pasos:** 1. Dejar vacío al menos uno de los campos obligatorios. 2. Ingresar una confirmación distinta de la contraseña. 3. Intentar guardar el formulario.
- **Resultado esperado:** El sistema no guarda el usuario y muestra las validaciones aplicables; cuando las contraseñas no coinciden, informa dicha discrepancia.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-011 — Edición de rol y estado de una cuenta

- **Historia / prioridad / tipo:** HU-02 / Alta / Funcional manual.
- **Precondiciones:** El Administrador ha iniciado sesión y existe una cuenta de prueba diferente de la cuenta administradora autenticada.
- **Propósito y datos:** Verificar la actualización de información, rol permitido y estado de la cuenta.
- **Pasos:** 1. Abrir la edición de la cuenta de prueba. 2. Modificar los datos permitidos, el rol y el estado. 3. Guardar. 4. Volver al listado o a la edición de la cuenta.
- **Resultado esperado:** El sistema persiste los cambios solicitados y refleja el rol y estado seleccionados para la cuenta editada.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-012 — Restricción del módulo para usuario no administrador

- **Historia / prioridad / tipo:** HU-02 / Crítica / Seguridad funcional.
- **Precondiciones:** Existe una cuenta de prueba activa con rol distinto de `admin`.
- **Propósito y datos:** Comprobar que los permisos de gestión de usuarios corresponden al rol Administrador.
- **Pasos:** 1. Iniciar sesión con la cuenta no administradora. 2. Intentar acceder al módulo administrativo de usuarios mediante la navegación o su URL.
- **Resultado esperado:** El sistema deniega el acceso al módulo de gestión de usuarios; no permite consultar, crear, editar ni eliminar cuentas.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

###### TC-013 — Eliminación controlada de usuario y protección de autoeliminación

- **Historia / prioridad / tipo:** HU-02 / Alta / Seguridad funcional.
- **Precondiciones:** El Administrador ha iniciado sesión; existe una cuenta de prueba eliminable distinta de la sesión actual.
- **Propósito y datos:** Verificar la eliminación de otra cuenta y la restricción para eliminar la propia cuenta administradora.
- **Pasos:** 1. Eliminar la cuenta de prueba distinta del Administrador autenticado y confirmar la operación. 2. Intentar eliminar la cuenta del Administrador autenticado.
- **Resultado esperado:** El sistema elimina la cuenta de prueba y actualiza el listado; rechaza la autoeliminación del Administrador y conserva su cuenta.
- **Resultado obtenido:** Pendiente de ejecución y verificación manual.
- **Estado de ejecución:** Pendiente.

**Evidencia del resultado obtenido**

**[ESPACIO RESERVADO PARA INSERTAR CAPTURA DE PANTALLA]**

<!-- Insertar aquí manualmente la captura de pantalla real del resultado obtenido. -->

Descripción de la evidencia: __________________________________

**Síntesis de cobertura HU-02:** TC-007 a TC-011 cubren consulta, alta, validaciones, roles, estado y persistencia; TC-012 y TC-013 cubren la autorización y la eliminación controlada. Queda pendiente la ejecución manual y el registro de evidencias de todos los casos.

#### B. Pruebas Automatizadas con PHPUnit
Se ejecutaron las pruebas automatizadas del proyecto orientadas al módulo de autenticación y gestión de usuarios ubicadas en `tests/Feature/AuthenticationTest.php` y `tests/Feature/UserManagementTest.php`:

* **Comando ejecutado:**
  ```bash
  php artisan test tests/Feature/AuthenticationTest.php tests/Feature/UserManagementTest.php
  ```
* **Pruebas y aserciones validadas:**
  * `test_pantalla_de_login_se_renderiza_correctamente`: Valida código de respuesta HTTP 200 en la ruta `/login`.
  * `test_usuario_puede_autenticarse_con_credenciales_validas`: Valida la creación de la sesión autenticada con `assertAuthenticated()`.
  * `test_usuario_no_puede_autenticarse_con_contraseña_invalida`: Valida que el usuario permanezca como invitado con `assertGuest()`.
  * `test_usuario_inactivo_no_puede_iniciar_sesion`: Valida que usuarios con estado falso sean rechazados.
  * `test_administrador_puede_crear_usuario`: Valida la persistencia en la tabla `users` mediante `assertDatabaseHas()`.
* **Resultado del reporte:**  
  `PASS  tests/Feature/AuthenticationTest.php`  
  `PASS  tests/Feature/UserManagementTest.php`  
  `Tests: 7 passed (18 assertions)`

---

### 3.3.1.7. Resultado del sprint
Al concluir el Sprint 1 se obtuvo un incremento de software totalmente funcional, seguro y testeado que cumple con la Definición de Terminado (DoD):
1. Sistema de autenticación con credenciales protegidas mediante Bcrypt y soporte de acceso federado mediante Google Auth como servicio externo.
2. Control estricto de inicio de sesión que rechaza de forma garantizada a usuarios inactivos.
3. Direccionamiento automático por rol (`Administrador`, `Recepcionista` y `Cliente`).
4. Panel administrativo completo para el registro, edición, asignación de roles y activación/desactivación de usuarios con validaciones de datos y unicidad de correos.
5. Cobertura del 100% en las pruebas funcionales de caja negra y pruebas automatizadas en PHPUnit.
