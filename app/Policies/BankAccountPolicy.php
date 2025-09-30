<?php

namespace App\Policies;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BankAccountPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver cuenta bancaria');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('ver cuenta bancaria');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('crear cuenta bancaria');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('editar cuenta bancaria');
    }

    /**
     * Determine whether the user can delete the model.
     */
    // public function delete(User $user, BankAccount $bankAccount): bool
    // {
    //     return $user->can('eliminar cuenta bancaria');
    // }

    public function approve1(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('aprobar primera validacion cuenta bancaria');
    }

    public function approve2(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('aprobar segunda validacion cuenta bancaria');
    }


    public function uploadFiles(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('subir archivos cuenta bancaria');
    }

    public function deleteFiles(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('eliminar archivos cuenta bancaria');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BankAccount $bankAccount): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BankAccount $bankAccount): bool
    {
        return false;
    }

    



}
