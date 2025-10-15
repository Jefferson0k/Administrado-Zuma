<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver factura');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('ver factura');
    }

    public function create(User $user): bool
    {
        return $user->can('crear factura');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('editar factura');
    }



    public function approveLevel1(User $user, Invoice $invoice): bool{
        return $user->can('aprobar primera validacion factura');
    }
    public function approveLevel2(User $user, Invoice $invoice): bool{
        return $user->can('aprobar segunda validacion factura');
    }


    public function close(User $user, Invoice $invoice): bool{
        return $user->can('cerrar factura');
    }


      public function open(User $user, Invoice $invoice): bool{
        return $user->can('abrir factura');
    }


    public function standby(User $user, Invoice $invoice): bool{
        return $user->can('poner standby factura');
    }



    public function restore(User $user, Invoice $invoice): bool
    {
        return false;
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return false;
    }

    /**
     * Determine whether the user can export invoices to Excel.
     */
    public function export(User $user): bool
    {
        return $user->can('exportar factura');
    }
}
