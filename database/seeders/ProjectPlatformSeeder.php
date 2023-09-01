<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectPlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $platforms = ['Visium', 'GeoMx', 'CosMx/SMI', 'MERFISH/MERSCOPE', 'Molecular Cartography', 'STARmap', 'Spatial Transcriptomics (Pre-Visium)', 'Generic'];

        foreach ($platforms as $platform) {
            DB::table('project_platforms')->insert([

                'name' => $platform,

                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

    }
}
