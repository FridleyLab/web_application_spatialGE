<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'id' => 0,
            'first_name' => 'spatielGE',
            'last_name' => 'spatielGE',
            'email' => 'roberto.manjarres-betancur@moffitt.org',
            'email_verification_code' => 'x',
            'password' => 'x',
            'industry' => '1',
            'is_admin' => '1',
            'job' => '1',
            'interest' => '1',

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

    }
}
