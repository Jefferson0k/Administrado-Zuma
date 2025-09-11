<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cargo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder{
    public function run(): void{
        $adminRole = Role::where('name', 'administrador')->first();
        $personalRole = Role::where('name', 'personal')->first();
        $permissions = Permission::all();
        if ($adminRole) {
            $adminRole->syncPermissions($permissions);
        }
        $cargoAdmin = Cargo::where('nombre', 'Administrador')->first();
        $cargoPersonal = Cargo::where('nombre', 'Personal')->first();
        $admin_1 = User::create([
            'name'              => 'user1',
            'dni'               => '12345678',
            'apellidos'         => 'Admin Test',
            'email'             => 'user1@gmail.com',
            'password'          => Hash::make('12345678'),
            'status'            => true,
            'restablecimiento'  => 0,
            'cargo_id'          => 1,
            'created_by'        => null,
            'updated_by'        => null,
            'deleted_by'        => null,
        ]);
        $personal_1 = User::create([
            'name'              => 'user2',
            'dni'               => '87654321',
            'apellidos'         => 'Personal Test',
            'email'             => 'user2@gmail.com',
            'password'          => Hash::make('12345678'),
            'status'            => true,
            'restablecimiento'  => 0,
            'cargo_id'          => $cargoPersonal?->id,
            'created_by'        => null,
            'updated_by'        => null,
            'deleted_by'        => null,
        ]);
        if ($adminRole) {
            $admin_1->assignRole($adminRole);
        }
        if ($personalRole) {
            $personal_1->assignRole($personalRole);
        }
    }
}
