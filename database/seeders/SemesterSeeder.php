<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $semesters = [
            [
                'name' => 'Spring',
                'code' => '1'
            ],
            [
                'name' => 'Summer',
                'code' => '2'
            ],
            [
                'name' => 'Fall',
                'code' => '3'
            ],
        ];

        Semester::upsert(
            $semesters, 
            ['code'],
            ['name']
        );
    }
}
