<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Lista de usuarios del sistema.
     */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $usuarios = User::orderBy('created_at', 'desc')->get();

        return view('admin.usuarios.index', compact('usuarios'));
    }

    /**
     * Formulario para crear un usuario.
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.usuarios.create');
    }

    /**
     * Registra un nuevo usuario desde el panel de administración.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'rol' => $data['rol'],
            'password' => $data['password'],
            'estado' => true,
        ]);

        return redirect()->route('admin.usuarios.index')
            ->with('status', 'Usuario creado correctamente.');
    }

    /**
     * Formulario para editar un usuario.
     */
    public function edit(User $usuario): View
    {
        $this->authorize('update', $usuario);

        return view('admin.usuarios.edit', compact('usuario'));
    }

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

    /**
     * Elimina un usuario del sistema (no permite eliminarse a sí mismo).
     */
    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($request->user()->is($usuario)) {
            return redirect()->back()
                ->withErrors(['eliminacion' => 'No puedes eliminar tu propia cuenta.']);
        }

        $this->authorize('delete', $usuario);

        $usuario->delete();

        return redirect()->route('admin.usuarios.index')
            ->with('status', 'Usuario eliminado correctamente.');
    }
}
