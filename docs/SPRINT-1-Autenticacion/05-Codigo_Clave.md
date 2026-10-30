# Código Clave — Sprint 1: Autenticación y Roles

> Fragmentos esenciales (firma + 10-15 líneas críticas) de los 10 archivos principales del Sprint 1.

---

## 1. `app/Http/Controllers/Auth/LoginController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'remember' => ['boolean'],
        ]);

        if (! auth()->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Las credenciales no son correctas.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        // Redirección según rol
        $user = auth()->user();
        if ($user->esAdministrador()) {
            return redirect()->intended(route('admin.dashboard'));
        }
        if ($user->esRecepcionista()) {
            return redirect()->intended(route('recepcionista.reservas.hoy'));
        }

        return redirect()->intended(route('reservas.mis'));
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
```

**Puntos clave**:
- Validación con `FormRequest` implícita en `validate()`
- `attempt()` maneja remember token y hash verification
- Redirección polimórfica por rol usando helpers del modelo `User`
- `session()->regenerate()` previene fixation attacks

---

## 2. `app/Http/Controllers/Auth/RegisterController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'rol' => User::ROL_CLIENTE, // Rol por defecto
        ]);

        auth()->login($user);
        return redirect()->route('reservas.mis');
    }
}
```

**Puntos clave**:
- `password confirmed` valida coincidencia con `password_confirmation`
- `unique:users,email` a nivel BD + validación
- Asignación automática de rol `cliente` (constante en modelo)
- Login automático tras registro

---

## 3. `app/Http/Controllers/ProfileController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $user->fill($request->validated());
        
        if ($user->isDirty('email')) {
            $user->email_verified_at = null; // Requiere re-verificación
        }
        
        $user->save();
        return back()->with('status', 'Perfil actualizado.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'confirm' => ['required', 'accepted'],
        ]);

        $user = auth()->user();
        auth()->logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
```

**Puntos clave**:
- `UpdateProfileRequest` valida solo campos permitidos (name, email)
- `isDirty('email')` detecta cambio → resetea `email_verified_at`
- `current_password` validation rule verifica password actual
- `accepted` rule para checkbox confirmación eliminación

---

## 4. `app/Http/Middleware/EnsureUserIsAdmin.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->esAdministrador()) {
            abort(403, 'Acceso denegado. Se requiere rol de administrador.');
        }
        return $next($request);
    }
}
```

**Puntos clave**:
- Optional chaining `?->` maneja usuario no autenticado
- `abort(403)` con mensaje claro
- Registrado en `bootstrap/app.php` → `middleware: ['admin' => EnsureUserIsAdmin::class]`

---

## 5. `app/Http/Middleware/EnsureUserIsRecepcionista.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsRecepcionista
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->esRecepcionista()) {
            abort(403, 'Acceso denegado. Se requiere rol de recepcionista.');
        }
        return $next($request);
    }
}
```

---

## 6. `app/Policies/UsuarioPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UsuarioPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function view(User $user, User $model): bool
    {
        return $user->esAdministrador() || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, User $model): bool
    {
        return $user->esAdministrador() || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->esAdministrador() && $user->id !== $model->id;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->esAdministrador();
    }
}
```

**Puntos clave**:
- `HandlesAuthorization` trait provee `deny()`, `allow()`
- Admin ve/gestiona todos; usuario solo su propio perfil
- `delete` previene auto-eliminación (`$user->id !== $model->id`)
- `forceDelete` solo admin (soft deletes)

---

## 7. `app/Models/User.php` (Helpers de Rol y Scopes)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROL_ADMIN = 'admin';
    public const ROL_RECEPCIONISTA = 'recepcionista';
    public const ROL_CLIENTE = 'cliente';

    protected $fillable = ['name', 'email', 'password', 'rol'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    // Helpers de rol
    public function esAdministrador(): bool { return $this->rol === self::ROL_ADMIN; }
    public function esRecepcionista(): bool { return $this->rol === self::ROL_RECEPCIONISTA; }
    public function esCliente(): bool { return $this->rol === self::ROL_CLIENTE; }

    // Scopes
    public function scopeAdministradores(Builder $query): Builder
    {
        return $query->where('rol', self::ROL_ADMIN);
    }
    public function scopeRecepcionistas(Builder $query): Builder
    {
        return $query->where('rol', self::ROL_RECEPCIONISTA);
    }
    public function scopeClientes(Builder $query): Builder
    {
        return $query->where('rol', self::ROL_CLIENTE);
    }

    // Relaciones
    public function reservas() { return $this->hasMany(Reserva::class); }
    public function socialAccounts() { return $this->hasMany(SocialAccount::class); }
}
```

**Puntos clave**:
- Constantes para roles evitan magic strings
- `casts()` usa `hashed` para password (Laravel 11+)
- Scopes reutilizables en queries
- Relaciones `hasMany` para reservas y cuentas sociales

---

## 8. `app/Http/Requests/UpdateProfileRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
        ];
    }
}
```

---

## 9. `app/Http/Requests/StoreUserRequest.php` (Admin crea usuario)

```php
<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->esAdministrador(); }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'rol' => ['required', Rule::in([User::ROL_ADMIN, User::ROL_RECEPCIONISTA, User::ROL_CLIENTE])],
        ];
    }
}
```

---

## 10. `config/auth.php` (Guards y Providers - fragmento relevante)

```php
<?php

return [
    'defaults' => ['guard' => 'web', 'passwords' => 'users'],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
];
```