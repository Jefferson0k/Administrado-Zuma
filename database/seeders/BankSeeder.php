<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bank;
use Illuminate\Support\Str;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = config('banks');
        // delete all banks from the database
        Bank::query()->delete();

        // insert $banks into the database
        foreach ($banks as $key => $bank) {
            Bank::create([
                'id' => Str::ulid(),
                'code' => $key,
                'name' => $bank,
            ]);
        }
    }
}
