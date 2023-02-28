<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('project_status')->insert([
            'id' => 1,
            'name' => 'Staging',

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('project_status')->insert([
            'id' => 2,
            'name' => 'Active',

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('project_status')->insert([
            'id' => 3,
            'name' => 'Finished',

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
