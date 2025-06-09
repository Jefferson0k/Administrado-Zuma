<?php

namespace App\Policies;

use App\Models\TasasFijas;
use App\Models\User;

class TasasFijasPolicy{
    public function viewAny(User $user): bool{
        return $user->can('ver tasas fijas');
    }
    public function view(User $user, TasasFijas $tasasfijas): bool{
        return $user->can('ver tasas fijas');
    }
    public function create(User $user): bool{
        return $user->can('crear tasas fijas');
    }
    public function update(User $user, TasasFijas $tasasfijas): bool{
        return $user->can('editar tasas fijas');
    }
    public function delete(User $user, TasasFijas $tasasfijas): bool{
        return $user->can('eliminar tasas fijas');
    }
    public function restore(User $user, TasasFijas $tasasfijas): bool{
        return false;
    }
    public function forceDelete(User $user, TasasFijas $tasasfijas): bool{
        return false;
    }
}
