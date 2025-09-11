<?php

namespace App\Policies;

use App\Models\PropertyLoanDetail;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PropertyLoanDetailPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver informacion del cliente');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PropertyLoanDetail $propertyLoanDetail): bool
    {
        return $user->can('ver informacion del cliente');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('crear informacion del cliente');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PropertyLoanDetail $propertyLoanDetail): bool
    {
        return $user->can('editar informacion del cliente');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PropertyLoanDetail $propertyLoanDetail): bool
    {
        return $user->can('eliminar informacion del cliente');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PropertyLoanDetail $propertyLoanDetail): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PropertyLoanDetail $propertyLoanDetail): bool
    {
        return false;
    }
}
