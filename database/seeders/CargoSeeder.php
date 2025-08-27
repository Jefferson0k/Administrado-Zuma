<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cargo;

class CargoSeeder extends Seeder{
    public function run(): void{
        $cargos = [
            [
                'nombre' => 'Administrador',
                'descripcion' => 'Acceso total al sistema, gestiona usuarios y configuraciones.',
            ],
            [
                'nombre' => 'Gerente',
                'descripcion' => 'Responsable de la gestión general de la empresa.',
            ],
            [
                'nombre' => 'Contador',
                'descripcion' => 'Encargado de las finanzas, registros contables y reportes.',
            ],
            [
                'nombre' => 'Personal',
                'descripcion' => 'Empleado con acceso limitado según sus funciones.',
            ],
        ];
        foreach ($cargos as $cargo) {
            Cargo::firstOrCreate(['nombre' => $cargo['nombre']], $cargo);
        }
    }
}
