<?php

namespace App\Policies;

use App\Models\Cancha;
use App\Models\User;

class CanchaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Cualquier usuario puede ver el catálogo de canchas
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Cancha $cancha): bool
    {
        // Cualquier usuario puede ver el detalle de una cancha
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo admin puede crear canchas
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Cancha $cancha): bool
    {
        // Solo admin puede actualizar canchas
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Cancha $cancha): bool
    {
        // Solo admin puede eliminar canchas
        return $user->esAdministrador();
    }
}
