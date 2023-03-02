<?php

namespace Database\Seeders;

use Carbon\Carbon;
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
                'name' => 'Test user',
                'email' => 'test@moffitt.org',
                'password' => '$2y$10$4jUWqrhPUAAPPDt8EfLLl.15IWBQIBs4pjl.j.pJO4EDnzQiD8Tou', //12345678
                'email_verification_code' => 'verified',
                'email_verified_at' => Carbon::now()
            ]);
        }
    }
}
