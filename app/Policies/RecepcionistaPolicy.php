<?php

namespace App\Policies;

use App\Models\User;

class RecepcionistaPolicy
{
    /**
     * Determine whether the user can access recepcionista features.
     */
    public function access(User $user): bool
    {
        // Solo recepcionistas y administradores pueden acceder
        return $user->rol === 'recepcionista' || $user->rol === 'admin';
    }

    /**
     * Determine whether the user can manage reservations.
     */
    public function manageReservations(User $user): bool
    {
        return $user->rol === 'recepcionista' || $user->rol === 'admin';
    }

    /**
     * Determine whether the user can perform check-in/check-out.
     */
    public function performCheckin(User $user): bool
    {
        return $user->rol === 'recepcionista' || $user->rol === 'admin';
    }

    /**
     * Determine whether the user can search clients.
     */
    public function searchClients(User $user): bool
    {
        return $user->rol === 'recepcionista' || $user->rol === 'admin';
    }
}
