<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectStepSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('project_steps')->insert([
            'id' => 1,
            'name' => 'Importing data',

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('project_steps')->insert([
            'id' => 2,
            'name' => 'QC & data Transformation',

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('project_steps')->insert([
            'id' => 3,
            'name' => 'STplot - Visualization',

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('project_steps')->insert([
            'id' => 4,
            'name' => 'SThet - Spatial heterogen.',

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('project_steps')->insert([
            'id' => 5,
            'name' => 'STDE - STclust - Niche detection',

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('project_steps')->insert([
            'id' => 6,
            'name' => 'STDE - Differential expr.',

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('project_steps')->insert([
            'id' => 7,
            'name' => 'STenrich - Spatial gene set',

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

    }
}
