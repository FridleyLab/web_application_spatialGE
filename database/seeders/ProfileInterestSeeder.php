<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfileInterestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $interests = ['Agricultural Biotech', 'Biology', 'Cancer/Oncology', 'Cardiovascular', 'Development Biology', 'Diagnostics', 'Endocrine', 'Evolution', 'Gastroenterology', 'Genetics', 'Immunology', 'Infectious Disease', 'Metabolism', 'Microbiome', 'Molecular Biology', 'Multiple Interests', 'Neuroscience', 'Stem Cells', 'Synthetic Biology', 'Toxicology', 'Veterinary', 'Other'];

        foreach ($interests as $interest) {
            DB::table('profile_interests')->insert([
                'name' => $interest,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

    }
}
