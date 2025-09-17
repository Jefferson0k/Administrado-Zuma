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

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->can('eliminar factura');
    }

    public function approveLevel1(User $user, Invoice $invoice): bool{
        return $user->can('aprobar factura nivel 1');
    }
    public function approveLevel2(User $user, Invoice $invoice): bool{
        return $user->can('aprobar factura nivel 2') 
            && $invoice->approval1_status === 'approved';
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
