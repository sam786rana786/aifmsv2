<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class SchoolClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();
        
        $standardClasses = [
            ['name' => 'LKG', 'capacity' => 30],
            ['name' => 'UKG', 'capacity' => 30],
            ['name' => 'Class 1', 'capacity' => 35],
            ['name' => 'Class 2', 'capacity' => 35],
            ['name' => 'Class 3', 'capacity' => 35],
            ['name' => 'Class 4', 'capacity' => 35],
            ['name' => 'Class 5', 'capacity' => 35],
            ['name' => 'Class 6', 'capacity' => 40],
            ['name' => 'Class 7', 'capacity' => 40],
            ['name' => 'Class 8', 'capacity' => 40],
            ['name' => 'Class 9', 'capacity' => 40],
            ['name' => 'Class 10', 'capacity' => 40],
        ];

        foreach ($schools as $school) {
            // Get active academic year for the school
            $activeAcademicYear = AcademicYear::where('school_id', $school->id)
                ->where('is_active', true)
                ->first();

            if ($activeAcademicYear) {
                foreach ($standardClasses as $class) {
                    // Create class A
                    SchoolClass::create([
                        'name' => $class['name'],
                        'section' => 'A',
                        'description' => $class['name'] . ' Section A',
                        'capacity' => $class['capacity'],
                        'school_id' => $school->id,
                        'academic_year_id' => $activeAcademicYear->id,
                        'is_active' => true,
                    ]);

                    // Create class B
                    SchoolClass::create([
                        'name' => $class['name'],
                        'section' => 'B',
                        'description' => $class['name'] . ' Section B',
                        'capacity' => $class['capacity'],
                        'school_id' => $school->id,
                        'academic_year_id' => $activeAcademicYear->id,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
