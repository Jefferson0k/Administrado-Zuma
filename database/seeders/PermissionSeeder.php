<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder{
    /**
     * Run the database seeds.
     */
    public function run(): void{
        #User
        Permission::create(['name' => 'crear usuarios']);
        Permission::create(['name' => 'editar usuarios']);
        Permission::create(['name' => 'eliminar usuarios']);
        Permission::create(['name' => 'ver usuarios']);
        # Roles
        Permission::create(['name' =>'crear roles']);
        Permission::create(['name' =>'editar roles']);
        Permission::create(['name' =>'eliminar roles']);
        Permission::create(['name' =>'ver roles']);
        # Permisos
        Permission::create(['name' =>'crear permisos']);
        Permission::create(['name' =>'editar permisos']);
        Permission::create(['name' =>'eliminar permisos']);
        Permission::create(['name' =>'ver permisos']);
        #Tasas fijas
        Permission::create(['name' => 'crear tasas fijas']);
        Permission::create(['name' => 'editar tasas fijas']);
        Permission::create(['name' => 'eliminar tasas fijas']);
        Permission::create(['name' => 'ver tasas fijas']);
        #Prestamos
        Permission::create(['name' => 'crear prestamos']);
        Permission::create(['name' => 'editar prestamos']);
        Permission::create(['name' => 'eliminar prestamos']);
        Permission::create(['name' => 'ver prestamos']);
        #Factoring
        Permission::create(['name' => 'ver factoring']);
    }
}
