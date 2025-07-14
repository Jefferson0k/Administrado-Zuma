<?php

namespace App\Policies;

use Spatie\Permission\Models\Role;
use App\Models\User;
class RolePolicy{
    public function viewAny(User $user): bool{
        return $user->can('ver roles');
    }
    public function view(User $user, Role $role): bool{
        return $user->can('ver roles');
    }
    public function create(User $user): bool{
        return $user->can('crear roles');
    }
    public function update(User $user, Role $role): bool{
        return $user->can('editar roles');
    }
    public function delete(User $user, Role $role): bool{
        return $user->can('eliminar roles');
    }
    public function restore(User $user, Role $role): bool{
        return false;
    }
    public function forceDelete(User $user, Role $role): bool{
        return false;
    }
}
