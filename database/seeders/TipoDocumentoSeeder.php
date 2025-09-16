<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoDocumentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tipo_documento')->insert([
            [
                'nombre_tipo_documento' => 'DNI',
                'orden_tipo_documento'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre_tipo_documento' => 'RUC',
                'orden_tipo_documento'  => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // [
            //     'nombre_tipo_documento' => 'Pasaporte',
            //     'orden_tipo_documento'  => 3,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
        ]);
    }
}
