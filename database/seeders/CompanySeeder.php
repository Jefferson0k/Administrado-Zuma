<?php

namespace Database\Seeders;

use App\Enums\Risk;
use App\Models\Company;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Factory::create();
        $faker_locale = Factory::create("es_PE");
        $scores = Risk::cases();

        // Get all sector IDs
        $sectorIds = DB::table('sectors')->pluck('id')->toArray();

        for ($i = 0; $i < 20; $i++) {
            // Get a random sector ID
            $sectorId = $faker->randomElement($sectorIds);

            // Get subsector IDs for this sector
            $subsectorIds = DB::table('subsectors')
                ->where('sector_id', $sectorId)
                ->pluck('id')
                ->toArray();

            Company::create([
                'name' => $faker_locale->company(),
                'risk' => $faker_locale->randomElement($scores)->value,
                'business_name' => $faker_locale->company(),
                'sector_id' => $sectorId,
                'subsector_id' => $subsectorIds ? $faker->randomElement($subsectorIds) : null,
                'incorporation_year' => $faker->year(),
                'sales_volume' => $faker->randomFloat(2, 100000, 10000000),
                'document' => $faker_locale->ruc(false),
                'link_web_page' => $faker_locale->url(),
                'description' => $faker_locale->paragraph(),
                'created_by' => 1,
                'updated_by' => 1
            ]);
        }
    }
}
