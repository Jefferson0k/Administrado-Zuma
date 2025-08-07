<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder{
    public function run(): void{
        $this->call([
            PermissionSeeder::class,
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
            CurrencySeeder::class,
            SectorSeeder::class,
            SubsectorSeeder::class,
            CompanySeeder::class,
            BankSeeder::class,
            InvestorSeeder::class,
            BankAccountSeeder::class,
            ExchangeSeeder::class,
            #InvoiceSeeder::class,
            #InvestmentSeeder::class,
            TermSeeder::class,
            #PropertySeeder::class,
            CoopacSanCristobalSeeder::class,
            PaymentFrequencySeeder::class,
            CoopacSanMiguelSeeder::class,
            CoopacInclusivaSeeder::class,
            CoopacLosAndesSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
