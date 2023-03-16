<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfileIndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $industries = ['Biotech', 'Contract Research Organization', 'Government', 'Hospital/Medical Center', 'Institute', 'Pharma', 'Service', 'University', 'Vendor'];

        foreach ($industries as $industry) {
            DB::table('profile_industries')->insert([
                'name' => $industry,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

    }
}
