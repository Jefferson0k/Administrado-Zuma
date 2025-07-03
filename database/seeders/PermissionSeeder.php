<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Ejemplo de permisos si decides mantener este seeder
        Permission::create(['name' => 'invoice-approval']);
        Permission::create(['name' => 'deposits-second-approval']);
        Permission::create(['name' => 'user-management']);
    }
}
