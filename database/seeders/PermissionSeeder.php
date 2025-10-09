<?php

namespace Database\Seeders;

use Faker\Provider\ar_EG\Person;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder{
    public function run(): void{
        #Hipotecas
        // Subastas
        Permission::create(['name' => 'activar subasta']);
        Permission::create(['name' => 'editar subasta']);
        Permission::create(['name' => 'eliminar subasta']);
        Permission::create(['name' => 'ver subasta']);
        #Informacion del cliente
        Permission::create(['name' => 'crear informacion del cliente']);
        Permission::create(['name' => 'editar informacion del cliente']);
        Permission::create(['name' => 'eliminar informacion del cliente']);
        Permission::create(['name' => 'ver informacion del cliente']);
        #Calendario de pagos
        Permission::create(['name' => 'crear calendario de pagos']);
        Permission::create(['name' => 'editar calendario de pagos']);
        Permission::create(['name' => 'eliminar calendario de pagos']);
        Permission::create(['name' => 'ver calendario de pagos']);
        #Inversionista de Propiedad
        Permission::create(['name' => 'crear inversionista de propiedad']);
        Permission::create(['name' => 'editar inversionista de propiedad']);
        Permission::create(['name' => 'eliminar inversionista de propiedad']);
        Permission::create(['name' => 'activar subasta de propiedad']);
        Permission::create(['name' => 'ver inversionista de propiedad']);
        #Configuracion de propiedades
        Permission::create(['name' => 'crear reglas del imueble']);
        Permission::create(['name' => 'editar reglas del imueble']);
        Permission::create(['name' => 'eliminar reglas del imueble']);
        Permission::create(['name' => 'ver reglas del imueble']);
        #Imagenes de Propiedades
        Permission::create(['name' => 'crear imagenes de propiedades']);
        Permission::create(['name' => 'editar imagenes de propiedades']);
        Permission::create(['name' => 'eliminar imagenes de propiedades']);
        Permission::create(['name' => 'ver imagenes de propiedades']);
        #Propiedades
        Permission::create(['name' => 'crear propiedades']);
        Permission::create(['name' => 'editar propiedades']);
        Permission::create(['name' => 'eliminar propiedades']);
        Permission::create(attributes: ['name' => 'ver propiedades']);
        #Hipotecas
        #Cargos
        Permission::create(['name' => 'crear cargo']);
        Permission::create(['name' => 'editar cargo']);
        Permission::create(['name' => 'eliminar cargo']);
        Permission::create(['name' => 'ver cargo']);
        // Invoice/Facturas
        Permission::create(['name' => 'ver factura']);
        Permission::create(['name' => 'crear factura']);
        Permission::create(['name' => 'editar factura']);
        Permission::create(['name' => 'adelantar pago factura']);
        Permission::create(['name' => 'exportar factura']);
        Permission::create(['name' => 'aprobar primera validacion factura']);
        Permission::create(['name' => 'aprobar segunda validacion factura']);
        Permission::create(['name' => 'poner standby factura']);
        
        // Inversionistas
        Permission::create(['name' => 'ver inversionistas']);
        Permission::create(['name' => 'editar inversionistas']);
        Permission::create(['name' => 'aprobar primera validacion inversionistas']);
        Permission::create(['name' => 'aprobar segunda validacion inversionistas']);
        Permission::create(['name' => 'subir evidencia espectro inversionistas']);
        Permission::create(['name' => 'subir evidencia pep inversionistas']);
        Permission::create(['name' => 'eliminar evidencia espectro inversionistas']);
        Permission::create(['name' => 'eliminar evidencia pep inversionistas']);
        // Empresas
        Permission::create(['name' => 'ver empresas']);
        Permission::create(['name' => 'crear empresas']);
        Permission::create(['name' => 'editar empresas']);
        // Permission::create(['name' => 'buscar sector']);
        Permission::create(['name' => 'exportar empresas']);
        // Cuentas Bancarias
        Permission::create(['name' => 'ver cuenta bancaria']);
        Permission::create(['name' => 'aprobar primera validacion cuenta bancaria']);
        Permission::create(['name' => 'aprobar segunda validacion cuenta bancaria']);
        Permission::create(['name' => 'subir archivos cuenta bancaria']);
        Permission::create(['name' => 'eliminar archivos cuenta bancaria']);
        Permission::create(['name' => 'exportar cuenta bancaria']);

        // Permission::create(['name' => 'crear cuenta bancaria']);
        Permission::create(['name' => 'editar cuenta bancaria']);
        // Permission::create(['name' => 'eliminar cuenta bancaria']);
        
        // Inversiones
        Permission::create(['name' => 'ver inversiones']);
        Permission::create(['name' => 'crear inversiones']);
        Permission::create(['name' => 'editar inversiones']);
        Permission::create(['name' => 'eliminar inversiones']);

        // Depósitos

        Permission::create(['name' => 'ver depositos']);
        Permission::create(['name' => 'editar depositos']);
        Permission::create(['name' => 'aprobar primera validacion depositos']);
        Permission::create(['name' => 'aprobar segunda validacion depositos']);
        Permission::create(['name' => 'subir archivos depositos']);
        Permission::create(['name' => 'eliminar archivos depositos']);
        // Pagos
        Permission::create(['name' => 'crear pagos']);
        Permission::create(['name' => 'editar pagos']);
        Permission::create(['name' => 'eliminar pagos']);
        Permission::create(['name' => 'ver pagos']);
        // Retiros
        Permission::create(['name' => 'ver retiros']);
        // Permission::create(['name' => 'crear retiros']);
        Permission::create(['name' => 'editar retiros']);
        Permission::create(['name' => 'aprobar primera validacion retiros']);
        Permission::create(['name' => 'aprobar segunda validacion retiros']);
        Permission::create(['name' => 'subir archivos retiros']);
        Permission::create(['name' => 'exportar retiros']);
        Permission::create(['name' => 'pagar retiros']);

        // Permission::create(['name' => 'eliminar retiros']);

        // Tipo de Cambio
        Permission::create(['name' => 'crear tipo cambio']);
        Permission::create(['name' => 'editar tipo cambio']);
        Permission::create(['name' => 'eliminar tipo cambio']);
        Permission::create(['name' => 'ver tipo cambio']);
        // Sectores
        Permission::create(['name' => 'ver sectores']);
        Permission::create(['name' => 'crear sectores']);
        Permission::create(['name' => 'editar sectores']);
        Permission::create(['name' => 'eliminar sectores']);
        
        // Sub Sectores
        Permission::create(['name' => 'ver sub sectores']);
        Permission::create(['name' => 'crear sub sectores']);
        Permission::create(['name' => 'editar sub sectores']);
        Permission::create(['name' => 'eliminar sub sectores']);
        
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

        // Posts
        Permission::create(['name' => 'crear posts']);
        Permission::create(['name' => 'editar posts']);
        Permission::create(['name' => 'eliminar posts']);
        Permission::create(['name' => 'ver posts']);


        // Categorias
        Permission::create(['name' => 'crear categorias']);
        Permission::create(['name' => 'editar categorias']);
        Permission::create(['name' => 'eliminar categorias']);
        Permission::create(['name' => 'ver categorias']);

        //Bank Accounts

        //Deposits



    }
}