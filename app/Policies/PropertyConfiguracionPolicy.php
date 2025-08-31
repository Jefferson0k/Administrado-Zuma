<?php

namespace App\Policies;

use App\Models\PropertyConfiguracion;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PropertyConfiguracionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver reglas del imueble');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PropertyConfiguracion $propertyConfiguracion): bool
    {
        return $user->can('ver reglas del imueble');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('crear reglas del imueble');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PropertyConfiguracion $propertyConfiguracion): bool
    {
        return $user->can('editar reglas del imueble');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PropertyConfiguracion $propertyConfiguracion): bool
    {
        return $user->can('eliminar reglas del imueble');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PropertyConfiguracion $propertyConfiguracion): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PropertyConfiguracion $propertyConfiguracion): bool
    {
        return false;
    }
}
