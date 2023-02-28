<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if(!app()->isProduction()) {
            DB::table('users')->insert([
                'name' => 'Roberto Manjarres',
                'email' => 'roberto.manjarres-betancur@moffitt.org',
                'password' => '$2y$10$4jUWqrhPUAAPPDt8EfLLl.15IWBQIBs4pjl.j.pJO4EDnzQiD8Tou', //12345678
            ]);
        }
    }
}
