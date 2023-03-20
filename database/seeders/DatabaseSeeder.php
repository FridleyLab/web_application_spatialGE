<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run() : void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            UserSeeder::class,
            ProjectStatusSeeder::class,
            ProjectStepSeeder::class,
            ProfileIndustrySeeder::class,
            ProfileJobSeeder::class,
            ProfileInterestSeeder::class
        ]);


        if(!app()->isProduction()) {
            DB::unprepared(file_get_contents(dirname(__FILE__) . '/dev.sql'));

            /*DB::table('users')->insert([
                'id' => '9999',
                'first_name' => 'TestFirstName',
                'last_name' => 'TestLastName',
                'email' => 'test@moffitt.org',
                'password' => '$2y$10$4jUWqrhPUAAPPDt8EfLLl.15IWBQIBs4pjl.j.pJO4EDnzQiD8Tou', //12345678
                'email_verification_code' => 'verified',
                'email_verified_at' => Carbon::now(),
                'industry' => 'test',
                'job' => 'test',
                'interest' => 'test'
            ]);*/

        }
    }
}
