<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $personalRole = Role::where('name', 'personal')->first();

        $users = [
            [
                'name' => 'Usuario 1',
                'email' => 'user1@gmail.com',
                'role' => $adminRole,
            ],
            [
                'name' => 'Usuario 2',
                'email' => 'user2@gmail.com',
                'role' => $adminRole,
            ],
            [
                'name' => 'Usuario 3',
                'email' => 'user3@gmail.com',
                'role' => $adminRole,
            ],
            [
                'name' => 'Usuario 4',
                'email' => 'user4@gmail.com',
                'role' => $adminRole,
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('12345678'),
                ]
            );

            if ($data['role']) {
                $user->assignRole($data['role']);
            }
        }
    }
}
