<?php

namespace App\Policies;

use App\Models\Reserva;
use App\Models\User;

class ReservaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admin puede ver todas las reservas
        // Cliente solo ve las suyas (filtrado en controlador)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Reserva $reserva): bool
    {
        // Admin puede ver cualquier reserva
        if ($user->esRecepcionistaOAdministrador()) {
            return true;
        }

        // Cliente solo puede ver sus propias reservas
        return $user->id === $reserva->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Cualquier usuario autenticado puede crear reservas
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Reserva $reserva): bool
    {
        // Solo admin puede actualizar reservas (cambiar estado, etc.)
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Reserva $reserva): bool
    {
        // Solo admin puede eliminar reservas
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can cancel the reservation.
     */
    public function cancel(User $user, Reserva $reserva): bool
    {
        // Admin puede cancelar cualquier reserva
        if ($user->esAdministrador()) {
            return true;
        }

        // El estado de la reserva es una regla de negocio que valida el controlador.
        return $user->id === $reserva->user_id;
    }

    /**
     * Determine whether the user can change the status.
     */
    public function changeStatus(User $user, Reserva $reserva): bool
    {
        // Administración y recepción gestionan el flujo operativo.
        return $user->esRecepcionistaOAdministrador();
    }
}
