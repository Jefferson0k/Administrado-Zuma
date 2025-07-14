<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductAccount;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Producto 1: Hipoteca
        $hipoteca = Product::create([
            'nombre' => 'Hipoteca',
            'descripcion' => 'Producto de inversión respaldado por hipotecas.'
        ]);

        ProductAccount::create([
            'product_id' => $hipoteca->id,
            'entidad' => 'BCP',
            'numero_cuenta' => '001-1234567890',
            'moneda' => 'PEN'
        ]);

        ProductAccount::create([
            'product_id' => $hipoteca->id,
            'entidad' => 'Interbank',
            'numero_cuenta' => '002-9876543210',
            'moneda' => 'USD'
        ]);

        // Producto 2: Tasa Fija
        $tasaFija = Product::create([
            'nombre' => 'Tasa Fija',
            'descripcion' => 'Inversión con tasa fija mensual garantizada.'
        ]);

        ProductAccount::create([
            'product_id' => $tasaFija->id,
            'entidad' => 'BBVA',
            'numero_cuenta' => '003-4567891230',
            'moneda' => 'PEN'
        ]);

        ProductAccount::create([
            'product_id' => $tasaFija->id,
            'entidad' => 'Scotiabank',
            'numero_cuenta' => '004-3216549870',
            'moneda' => 'USD'
        ]);
    }
}
