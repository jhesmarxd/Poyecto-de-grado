<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Solo permite autenticar cuentas activas.
     *
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

    /**
     * Envía a cada rol a su espacio principal después de iniciar sesión.
     */
    protected function redirectTo(): string
    {
        return match (auth()->user()?->rol) {
            User::ROL_ADMIN => route('admin.dashboard'),
            User::ROL_RECEPCIONISTA => route('recepcionista.reservas.hoy'),
            default => route('home'),
        };
    }
}
