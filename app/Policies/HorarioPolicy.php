<?php

namespace App\Policies;

use App\Models\Horario;
use App\Models\User;

class HorarioPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Cualquier usuario puede ver horarios (disponibilidad)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Horario $horario): bool
    {
        // Cualquier usuario puede ver un horario específico
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo admin puede crear horarios
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Horario $horario): bool
    {
        // Solo admin puede actualizar horarios
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Horario $horario): bool
    {
        // Solo admin puede eliminar horarios
        return $user->esAdministrador();
    }
}
