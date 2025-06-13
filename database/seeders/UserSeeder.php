<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder{
    public function run(): void{
        $adminRole = Role::where('name', 'administrador')->first();
        $personalRole = Role::where('name', 'personal')->first();
        $permissions = Permission::all();
        if ($adminRole) {
            $adminRole->syncPermissions($permissions);
        }

        $admin_1 = User::create([
            'name' => 'Jefferson',
            'dni' => '0000000',
            'apellidos' => '0k',
            'nacimiento' => '2003-03-11',
            'email' => 'ok@gmail.com',
            'username' => 'jeferson',
            'password' => Hash::make('12345678'),
            'status' => true,
            'monto'=> 100,
            'restablecimiento' => 1,
        ]);

        $admin_2 = User::create([
            'name' => 'Lucia',
            'dni' => '0000001',
            'apellidos' => '0k',
            'nacimiento' => '2003-03-11',
            'email' => 'ok1@gmail.com',
            'username' => 'lucia',
            'password' => Hash::make('12345678'),
            'status' => true,
            'monto'=> 100,
            'restablecimiento' => 1,
        ]);

        $admin_3 = User::create([
            'name' => 'Antony',
            'dni' => '0000002',
            'apellidos' => '0k',
            'nacimiento' => '2003-03-11',
            'email' => 'ok2@gmail.com',
            'username' => 'antony',
            'password' => Hash::make('12345678'),
            'status' => true,
            'monto'=> 100,
            'restablecimiento' => 1,
        ]);

        $admin_4 = User::create([
            'name' => 'Alex',
            'dni' => '0000003',
            'apellidos' => '0k',
            'nacimiento' => '2003-03-11',
            'email' => 'ok3@gmail.com',
            'username' => 'alex',
            'password' => Hash::make('12345678'),
            'status' => true,
            'monto'=> 100,
            'restablecimiento' => 1,
        ]);

        $admin_1->assignRole($adminRole);
        $admin_2->assignRole($adminRole);
        $admin_3->assignRole($adminRole);
        $admin_4->assignRole($adminRole);
    }
}
