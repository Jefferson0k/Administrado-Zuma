<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['alias' => 'puma',      'document' => '10000001', 'monto' => 1500],
            ['alias' => 'condor',    'document' => '10000002', 'monto' => 1200],
            ['alias' => 'zorro',     'document' => '10000003', 'monto' => 1800],
            ['alias' => 'tigre',     'document' => '10000004', 'monto' => 950],
            ['alias' => 'jaguar',    'document' => '10000005', 'monto' => 2100],
            ['alias' => 'halcon',    'document' => '10000006', 'monto' => 3000],
            ['alias' => 'oso',       'document' => '10000007', 'monto' => 500],
            ['alias' => 'lobo',      'document' => '10000008', 'monto' => 1750],
            ['alias' => 'pantera',   'document' => '10000009', 'monto' => 600],
            ['alias' => 'serpiente', 'document' => '10000010', 'monto' => 2000],
        ];

        foreach ($customers as $i => $data) {
            Customer::create([
                'name' => 'Usuario',
                'first_last_name' => 'Demo',
                'second_last_name' => 'Test',
                'alias' => $data['alias'],
                'document' => $data['document'],
                'email' => $data['alias'] . '@example.com',
                'password' => Hash::make('password123'),
                'telephone' => '90000000' . $i,
                'document_front' => null,
                'document_back' => null,
                'status' => 'validated',
                'email_verified_at' => now(),
                'monto' => $data['monto'],
            ]);
        }
    }
}
