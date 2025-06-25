<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void{
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            CurrencySeeder::class,
            TermSeeder::class,
            PropertySeeder::class,
            CoopacSanCristobalSeeder::class,
            PaymentFrequencySeeder::class,
            CustomerSeeder::class,
            CoopacSanMiguelSeeder::class,
            CoopacInclusivaSeeder::class,
            CoopacLosAndesSeeder::class,
            #CoopacInclusivaSeeder::class,
            #CoopacLosAndesSeeder::class,
        ]);
    }
}
