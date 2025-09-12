<?php

namespace App\Policies;

use App\Models\Exchange;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExchangePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver tipo cambio');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Exchange $exchange): bool
    {
        return $user->can('ver tipo cambio');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('crear tipo cambio');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Exchange $exchange): bool
    {
        return $user->can('editar tipo cambio');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Exchange $exchange): bool
    {
        return $user->can('eliminar tipo cambio');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Exchange $exchange): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Exchange $exchange): bool
    {
        return false;
    }
}
