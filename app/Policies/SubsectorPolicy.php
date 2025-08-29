<?php

namespace App\Policies;

use App\Models\Subsector;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubsectorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver sub sector');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Subsector $subsector): bool
    {
        return $user->can('ver sub sector');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('crear sub sector');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Subsector $subsector): bool
    {
        return $user->can('editar sub sector');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Subsector $subsector): bool
    {
        return $user->can('eliminar sub sector');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Subsector $subsector): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Subsector $subsector): bool
    {
        return false;
    }
}
