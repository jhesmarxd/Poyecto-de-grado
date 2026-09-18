<?php

namespace App\Policies;

use App\Models\User;

class UsuarioPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Solo admin puede ver la lista de usuarios
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $usuario): bool
    {
        // Solo admin puede ver detalles de usuarios
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo admin puede crear usuarios
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $usuario): bool
    {
        // Solo admin puede actualizar usuarios
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $usuario): bool
    {
        // Admin no puede eliminarse a sí mismo
        if ($user->id === $usuario->id) {
            return false;
        }

        // Solo admin puede eliminar usuarios
        return $user->esAdministrador();
    }
}
