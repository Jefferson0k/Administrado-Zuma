<?php

namespace Database\Seeders;

use App\Models\Cargo;
use Illuminate\Database\Seeder;
use Spatie\Permission\Contracts\Role;

class DatabaseSeeder extends Seeder{
    public function run(): void{
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            CargoSeeder::class,
            UserSeeder::class,
            CurrencySeeder::class,
            SectorSeeder::class,
            SubsectorSeeder::class,
            #TermSeeder::class,
            BankSeeder::class,
            #CompanySeeder::class,
            #InvestorSeeder::class,
            #BankAccountSeeder::class,
            #ExchangeSeeder::class,
            #InvoiceSeeder::class,
            #InvestmentSeeder::class,
            #PropertySeeder::class,
            #CoopacSanCristobalSeeder::class,
            #PaymentFrequencySeeder::class,
            #CoopacSanMiguelSeeder::class,
            #CoopacInclusivaSeeder::class,
            #CoopacLosAndesSeeder::class,
            #ProductSeeder::class,
            TipoDocumentoSeeder::class,
        ]);
    }
}
