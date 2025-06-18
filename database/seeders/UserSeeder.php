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
            'name' => 'Usuario 1',
            'dni' => '0000001',
            'apellidos' => '0k',
            'nacimiento' => '2003-03-11',
            'email' => 'user1@gmail.com',
            'username' => 'usuario1',
            'password' => Hash::make('12345678'),
            'status' => true,
            'monto'=> 100,
            'restablecimiento' => 1,
        ]);

        $admin_2 = User::create([
            'name' => 'Usuario 2',
            'dni' => '0000002',
            'apellidos' => '0k',
            'nacimiento' => '2003-03-11',
            'email' => 'user2@gmail.com',
            'username' => 'usuario2',
            'password' => Hash::make('12345678'),
            'status' => true,
            'monto'=> 100,
            'restablecimiento' => 1,
        ]);

        $admin_3 = User::create([
            'name' => 'Usuario 3',
            'dni' => '0000003',
            'apellidos' => '0k',
            'nacimiento' => '2003-03-11',
            'email' => 'user3@gmail.com',
            'username' => 'usuario3',
            'password' => Hash::make('12345678'),
            'status' => true,
            'monto'=> 100,
            'restablecimiento' => 1,
        ]);

        $admin_4 = User::create([
            'name' => 'Usuario 4',
            'dni' => '0000004',
            'apellidos' => '0k',
            'nacimiento' => '2003-03-11',
            'email' => 'user4@gmail.com',
            'username' => 'usuario4',
            'password' => Hash::make('12345678'),
            'status' => true,
            'monto'=> 100,
            'restablecimiento' => 1,
        ]);

        $admin_5 = User::create([
            'name' => 'Usuario 5',
            'dni' => '0000005',
            'apellidos' => '0k',
            'nacimiento' => '2003-03-11',
            'email' => 'user5@gmail.com',
            'username' => 'usuario5',
            'password' => Hash::make('12345678'),
            'status' => true,
            'monto'=> 100,
            'restablecimiento' => 1,
        ]);

        $admin_6 = User::create([
            'name' => 'Usuario 6',
            'dni' => '0000006',
            'apellidos' => '0k',
            'nacimiento' => '2003-03-11',
            'email' => 'user6@gmail.com',
            'username' => 'usuario6',
            'password' => Hash::make('12345678'),
            'status' => true,
            'monto'=> 100,
            'restablecimiento' => 1,
        ]);

        $admin_7 = User::create([
            'name' => 'Usuario 7',
            'dni' => '0000007',
            'apellidos' => '0k',
            'nacimiento' => '2003-03-11',
            'email' => 'user7@gmail.com',
            'username' => 'usuario7',
            'password' => Hash::make('12345678'),
            'status' => true,
            'monto'=> 100,
            'restablecimiento' => 1,
        ]);

        $admin_8 = User::create([
            'name' => 'Usuario 8',
            'dni' => '0000008',
            'apellidos' => '0k',
            'nacimiento' => '2003-03-11',
            'email' => 'user8@gmail.com',
            'username' => 'alex',
            'password' => Hash::make('12345678'),
            'status' => true,
            'monto'=> 100,
            'restablecimiento' => 1,
        ]);

        $admin_9 = User::create([
            'name' => 'Usuario 9',
            'dni' => '0000009',
            'apellidos' => '0k',
            'nacimiento' => '2003-03-11',
            'email' => 'user9@gmail.com',
            'username' => 'usuario9',
            'password' => Hash::make('12345678'),
            'status' => true,
            'monto'=> 100,
            'restablecimiento' => 1,
        ]);

        $admin_10 = User::create([
            'name' => 'Usuario 10',
            'dni' => '0000010',
            'apellidos' => '0k',
            'nacimiento' => '2003-03-11',
            'email' => 'user10@gmail.com',
            'username' => 'usuario10',
            'password' => Hash::make('12345678'),
            'status' => true,
            'monto'=> 100,
            'restablecimiento' => 1,
        ]);

        $admin_1->assignRole($adminRole);
        $admin_2->assignRole($adminRole);
        $admin_3->assignRole($adminRole);
        $admin_4->assignRole($adminRole);
        $admin_5->assignRole($adminRole);
        $admin_6->assignRole($adminRole);
        $admin_7->assignRole($adminRole);
        $admin_8->assignRole($adminRole);
        $admin_9->assignRole($adminRole);
        $admin_10->assignRole($adminRole);
    }
}
