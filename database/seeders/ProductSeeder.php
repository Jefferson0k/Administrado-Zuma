<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductAccount;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Producto 1: Factoring
        $factoring = Product::create([
            'nombre' => 'Factoring',
            'descripcion' => 'Financiamiento a través de la compra de facturas por cobrar.'
        ]);

        ProductAccount::create([
            'product_id' => $factoring->id,
            'entidad' => 'BCP',
            'numero_cuenta' => '001-1111111111',
            'moneda' => 'PEN'
        ]);

        ProductAccount::create([
            'product_id' => $factoring->id,
            'entidad' => 'Interbank',
            'numero_cuenta' => '002-2222222222',
            'moneda' => 'USD'
        ]);

        // Producto 2: Hipotecas
        $hipoteca = Product::create([
            'nombre' => 'Hipotecas',
            'descripcion' => 'Producto de inversión respaldado por hipotecas.'
        ]);

        ProductAccount::create([
            'product_id' => $hipoteca->id,
            'entidad' => 'BBVA',
            'numero_cuenta' => '003-3333333333',
            'moneda' => 'PEN'
        ]);

        ProductAccount::create([
            'product_id' => $hipoteca->id,
            'entidad' => 'Scotiabank',
            'numero_cuenta' => '004-4444444444',
            'moneda' => 'USD'
        ]);

        // Producto 3: Tasa Fija
        $tasaFija = Product::create([
            'nombre' => 'Tasa Fija',
            'descripcion' => 'Inversión con tasa fija mensual garantizada.'
        ]);

        ProductAccount::create([
            'product_id' => $tasaFija->id,
            'entidad' => 'Banco Pichincha',
            'numero_cuenta' => '005-5555555555',
            'moneda' => 'PEN'
        ]);

        ProductAccount::create([
            'product_id' => $tasaFija->id,
            'entidad' => 'MiBanco',
            'numero_cuenta' => '006-6666666666',
            'moneda' => 'USD'
        ]);
    }
}
