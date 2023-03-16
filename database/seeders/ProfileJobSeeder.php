<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfileJobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $jobs = ['Administrative', 'Bioinformatician', 'Biologist', 'Clinician', 'Data Analyst', 'Data Scientist', 'Field Application Scientist', 'Graduate Student', 'Intern', 'Lab Director', 'Lab Manager', 'Lab Technician', 'Non-scientific', 'Pathologist', 'Physician', 'Post-Doctoral', 'Principal Investigator', 'Professor', 'Researcher', 'Scientist', 'Senior Scientist', 'Statistician', 'Student', 'Undergraduate Student', 'Other'];

        foreach ($jobs as $job) {
            DB::table('profile_jobs')->insert([
                'name' => $job,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

    }
}
