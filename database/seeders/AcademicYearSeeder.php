<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all schools
        $schools = School::all();

        // Create academic years for each school
        foreach ($schools as $school) {
            // Current academic year (2023-2024)
            AcademicYear::create([
                'name' => '2023-2024',
                'start_date' => '2023-06-01',
                'end_date' => '2024-03-31',
                'school_id' => $school->id,
                'is_active' => true,
            ]);

            // Next academic year (2024-2025)
            AcademicYear::create([
                'name' => '2024-2025',
                'start_date' => '2024-06-01',
                'end_date' => '2025-03-31',
                'school_id' => $school->id,
                'is_active' => false,
            ]);

            // Previous academic year (2022-2023)
            AcademicYear::create([
                'name' => '2022-2023',
                'start_date' => '2022-06-01',
                'end_date' => '2023-03-31',
                'school_id' => $school->id,
                'is_active' => false,
            ]);
        }
    }
}
