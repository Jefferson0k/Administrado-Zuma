<?php

namespace App\Policies;

use App\Models\Deposit;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DepositPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver depositos');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Deposit $deposit): bool
    {
        return $user->can('ver depositos');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('crear depositos');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Deposit $deposit): bool
    {
        return $user->can('editar depositos');
    }



     public function approve1(User $user, Deposit $deposit): bool
    {
        return $user->can('aprobar primera validacion depositos');
    }

    public function approve2(User $user, Deposit $deposit): bool
    {
        return $user->can('aprobar segunda validacion depositos');
    }

    public function uploadFiles(User $user, Deposit $deposit): bool
    {
        return $user->can('subir archivos depositos');
    }
    
    public function deletefiles(User $user, Deposit $deposit): bool
    {
        return $user->can('eliminar archivos depositos');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Deposit $deposit): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Deposit $deposit): bool
    {
        return false;
    }

    public function export(User $user): bool
    {
        return $user->can('exportar depositos');
    }
}
