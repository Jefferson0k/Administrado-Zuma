<?php

namespace App\Policies;

use App\Models\Investor;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InvestorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver inversionistas');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Investor $investor): bool
    {
        return $user->can('ver inversionistas');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('crear inversionistas');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Investor $investor): bool
    {
        return $user->can('editar inversionistas');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Investor $investor): bool
    {
        return $user->can('eliminar inversionistas');
    }

    public function approve(User $user, Investor $investor): bool
    {
        return $user->can('aprobar inversionistas');
    }
    public function reject(User $user, Investor $investor): bool
    {
        return $user->can('rechazar inversionistas');
    }




    public function approve1(User $user, Investor $investor): bool
    {
        return $user->can('aprobar primera validacion inversionistas');
    }

    public function approve2(User $user, Investor $investor): bool
    {
        return $user->can('aprobar segunda validacion inversionistas');
    }

    public function observe1(User $user, Investor $investor): bool
    {
        return $user->can('observar primera validacion inversionistas');
    }

    public function observe2(User $user, Investor $investor): bool
    {
        return $user->can('observar segunda validacion inversionistas');
    }

    public function observednifront(User $user, Investor $investor): bool
    {
        return $user->can('observar dni frontal inversionistas');
    }

    public function observedniback(User $user, Investor $investor): bool
    {
        return $user->can('observar dni posterior inversionistas');
    }

    public function observefoto(User $user, Investor $investor): bool
    {
        return $user->can('observar foto inversionistas');
    }

    public function comment1(User $user, Investor $investor): bool
    {
        return $user->can('comentar primera validacion inversionistas');
    }

    public function comment2(User $user, Investor $investor): bool
    {
        return $user->can('comentar segunda validacion inversionistas');
    }

    public function reject1(User $user, Investor $investor): bool
    {
        return $user->can('rechazar primera validacion inversionistas');
    }

    public function reject2(User $user, Investor $investor): bool
    {
        return $user->can('rechazar segunda validacion inversionistas');
    }

    public function uploadspectro(User $user, Investor $investor): bool
    {
        return $user->can('subir evidencia espectro inversionistas');
    }

    public function uploadpep(User $user, Investor $investor): bool
    {
        return $user->can('subir evidencia pep inversionistas');
    }


    public function deletespectro(User $user, Investor $investor): bool
    {
        return $user->can('eliminar evidencia espectro inversionistas');
    }

    public function deletepep(User $user, Investor $investor): bool
    {
        return $user->can('eliminar evidencia pep inversionistas');
    }






    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Investor $investor): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Investor $investor): bool
    {
        return false;
    }
}
