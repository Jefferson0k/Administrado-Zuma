<?php

namespace App\Policies;

use App\Models\PropertyInvestor;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PropertyInvestorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver inversionista de propiedad');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PropertyInvestor $propertyInvestor): bool
    {
        return $user->can('ver inversionista de propiedad');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('crear inversionista de propiedad');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PropertyInvestor $propertyInvestor): bool
    {
        return $user->can('editar inversionista de propiedad');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PropertyInvestor $propertyInvestor): bool
    {
        return $user->can('eliminar inversionista de propiedad');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PropertyInvestor $propertyInvestor): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PropertyInvestor $propertyInvestor): bool
    {
        return false;
    }
}
