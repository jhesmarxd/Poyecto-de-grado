<?php

namespace App\Policies;

use App\Models\Balon;
use App\Models\User;

class BalonPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Cualquier usuario puede ver el catálogo de balones
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Balon $balon): bool
    {
        // Cualquier usuario puede ver el detalle de un balón
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo admin puede crear balones
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Balon $balon): bool
    {
        // Solo admin puede actualizar balones
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Balon $balon): bool
    {
        // Solo admin puede eliminar balones
        return $user->esAdministrador();
    }
}
