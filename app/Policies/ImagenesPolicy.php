<?php

namespace App\Policies;

use App\Models\Imagenes;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ImagenesPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver imagenes de propiedades');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Imagenes $imagenes): bool
    {
        return $user->can('ver imagenes de propiedades');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('crear imagenes de propiedades');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Imagenes $imagenes): bool
    {
        return $user->can('editar imagenes de propiedades');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Imagenes $imagenes): bool
    {
        return $user->can('eliminar imagenes de propiedades');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Imagenes $imagenes): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Imagenes $imagenes): bool
    {
        return false;
    }
}
