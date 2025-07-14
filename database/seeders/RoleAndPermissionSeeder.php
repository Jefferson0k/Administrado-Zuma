<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Crear permisos
        $permissions = [
            'invoice-approval',
            'deposits-second-approval',
            'user-management',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Crear roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $operations = Role::firstOrCreate(['name' => 'operations']);
        $risk_manager = Role::firstOrCreate(['name' => 'risk-manager']);
        $personal = Role::firstOrCreate(['name' => 'personal']); // Este lo usas en otro seeder

        // Asignar permisos a roles
        $admin->syncPermissions($permissions);
        $operations->syncPermissions([]);
        $risk_manager->syncPermissions([
            'invoice-approval',
            'deposits-second-approval',
        ]);
    }
}
