<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Auth\Access\Response;

class WithdrawPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver retiros');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Withdraw $withdraw): bool
    {
        return $user->can('ver retiros');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('crear retiros');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Withdraw $withdraw): bool
    {
        return $user->can('editar retiros');
    }

        public function approve1(User $user, Withdraw $withdraw): bool
    {
        return $user->can('aprobar primera validacion retiros');
    }

        public function approve2(User $user, Withdraw $withdraw): bool
    {
        return $user->can('aprobar segunda validacion retiros');
    }

        public function uploadFiles(User $user, Withdraw $withdraw): bool
    {
        return $user->can('subir archivos retiros');
    }

    /**
     * Determine whether the user can delete the model.
     */
    // public function delete(User $user, Withdraw $withdraw): bool
    // {
    //     return $user->can('eliminar retiros');
    // }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Withdraw $withdraw): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Withdraw $withdraw): bool
    {
        return false;
    }


    public function pay(User $user, Withdraw $withdraw): bool
    {
        // Ajusta a tu esquema de permisos/roles
        return $user->can('pagar retiros');
    }

}
