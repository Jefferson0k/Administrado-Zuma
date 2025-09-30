<?php

namespace App\Policies;
use Spatie\Permission\Models\Permission;
use App\Models\User;
class PermissionPolicy{
    public function viewAny(User $user): bool{
        return $user->can('ver permisos');
    }
    public function view(User $user, Permission $permission): bool{
        return $user->can('ver permisos');
    }
    public function create(User $user): bool{
        return $user->can('crear permisos');
    }
    public function update(User $user, Permission $permission): bool{
        return $user->can('editar permisos');
    }
    // public function delete(User $user, Permission $permission): bool{
    //     return $user->can('eliminar permisos');
    // }
    public function restore(User $user, Permission $permission): bool{
        return false;
    }
    public function forceDelete(User $user, Permission $permission): bool{
        return false;
    }
}
