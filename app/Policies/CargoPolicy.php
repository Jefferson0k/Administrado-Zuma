<?php

namespace App\Policies;

use App\Models\Cargo;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CargoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver cargo');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Cargo $cargo): bool
    {
        return $user->can('ver cargo');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('crear cargo');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Cargo $cargo): bool
    {
        return $user->can('editar cargo');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Cargo $cargo): bool
    {
        return $user->can('eliminar cargo');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Cargo $cargo): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Cargo $cargo): bool
    {
        return false;
    }
}
