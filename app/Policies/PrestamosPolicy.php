<?php

namespace App\Policies;

use App\Models\Prestamos;
use App\Models\User;

class PrestamosPolicy{
    public function viewAny(User $user): bool{
        return $user->can('ver prestamos');
    }
    public function view(User $user, Prestamos $prestamos): bool{
        return $user->can('ver prestamos');
    }
    public function create(User $user): bool{
        return $user->can('crear prestamos');
    }
    public function update(User $user, Prestamos $prestamos): bool{
        return $user->can('editar prestamos');
    }
    public function delete(User $user, Prestamos $prestamos): bool{
        return $user->can('eliminar prestamos');
    }
    public function restore(User $user, Prestamos $prestamos): bool{
        return false;
    }
    public function forceDelete(User $user, Prestamos $prestamos): bool{
        return false;
    }
}
