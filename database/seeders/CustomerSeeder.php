<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder{
    public function run(): void{
        Customer::create([
            'name' => 'Jefferson',
            'first_last_name' => 'Coveñas',
            'second_last_name' => 'Ramírez',
            'alias' => 'jeff',
            'document' => '12345678',
            'email' => 'jeffersonkk997@gmail.com',
            'password' => Hash::make('password123'),
            'telephone' => '999999999',
            'document_front' => null,
            'document_back' => null,
            'status' => 'validated',
            'email_verified_at' => now(),
        ]);
    }
}
