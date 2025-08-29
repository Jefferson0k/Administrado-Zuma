<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder{
    public function run(): void{
        #Cargos
        Permission::create(['name' => 'crear cargo']);
        Permission::create(['name' => 'editar cargo']);
        Permission::create(['name' => 'eliminar cargo']);
        Permission::create(['name' => 'ver cargo']);
        // Invoice/Facturas
        Permission::create(['name' => 'crear factura']);
        Permission::create(['name' => 'editar factura']);
        Permission::create(['name' => 'eliminar factura']);
        Permission::create(['name' => 'ver factura']);
        // Inversionistas
        Permission::create(['name' => 'crear inversionistas']);
        Permission::create(['name' => 'editar inversionistas']);
        Permission::create(['name' => 'eliminar inversionistas']);
        Permission::create(['name' => 'ver inversionistas']);
        // Empresas
        Permission::create(['name' => 'buscar sector']);
        Permission::create(['name' => 'crear empresas']);
        Permission::create(['name' => 'editar empresas']);
        Permission::create(['name' => 'eliminar empresas']);
        Permission::create(['name' => 'ver empresas']);
        // Cuentas Bancarias
        Permission::create(['name' => 'crear cuenta bancaria']);
        Permission::create(['name' => 'editar cuenta bancaria']);
        Permission::create(['name' => 'eliminar cuenta bancaria']);
        Permission::create(['name' => 'ver cuenta bancaria']);
        // Inversiones
        Permission::create(['name' => 'crear inversiones']);
        Permission::create(['name' => 'editar inversiones']);
        Permission::create(['name' => 'eliminar inversiones']);
        Permission::create(['name' => 'ver inversiones']);
        // Depósitos
        Permission::create(['name' => 'crear depositos']);
        Permission::create(['name' => 'editar depositos']);
        Permission::create(['name' => 'eliminar depositos']);
        Permission::create(['name' => 'ver depositos']);
        // Pagos
        Permission::create(['name' => 'crear pagos']);
        Permission::create(['name' => 'editar pagos']);
        Permission::create(['name' => 'eliminar pagos']);
        Permission::create(['name' => 'ver pagos']);
        // Retiros
        Permission::create(['name' => 'crear retiros']);
        Permission::create(['name' => 'editar retiros']);
        Permission::create(['name' => 'eliminar retiros']);
        Permission::create(['name' => 'ver retiros']);
        // Tipo de Cambio
        Permission::create(['name' => 'crear tipo cambio']);
        Permission::create(['name' => 'editar tipo cambio']);
        Permission::create(['name' => 'eliminar tipo cambio']);
        Permission::create(['name' => 'ver tipo cambio']);
        // Sectores
        Permission::create(['name' => 'crear sectores']);
        Permission::create(['name' => 'editar sectores']);
        Permission::create(['name' => 'eliminar sectores']);
        Permission::create(['name' => 'ver sectores']);
        // Sub Sectores
        Permission::create(['name' => 'crear sub sector']);
        Permission::create(['name' => 'editar sub sector']);
        Permission::create(['name' => 'eliminar sub sector']);
        Permission::create(['name' => 'ver sub sector']);
         // Users/Usuarios
        Permission::create(['name' => 'crear usuarios']);
        Permission::create(['name' => 'editar usuarios']);
        Permission::create(['name' => 'eliminar usuarios']);
        Permission::create(['name' => 'ver usuarios']);
        // Roles
        Permission::create(['name' => 'crear roles']);
        Permission::create(['name' => 'editar roles']);
        Permission::create(['name' => 'eliminar roles']);
        Permission::create(['name' => 'ver roles']);
        // Permisos
        Permission::create(['name' => 'crear permisos']);
        Permission::create(['name' => 'editar permisos']);
        Permission::create(['name' => 'eliminar permisos']);
        Permission::create(['name' => 'ver permisos']);
    }
}