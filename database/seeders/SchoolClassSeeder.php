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
            ['name' => 'LKG', 'code' => 'LKG', 'capacity' => 30],
            ['name' => 'UKG', 'code' => 'UKG', 'capacity' => 30],
            ['name' => 'Class 1', 'code' => 'C01', 'capacity' => 35],
            ['name' => 'Class 2', 'code' => 'C02', 'capacity' => 35],
            ['name' => 'Class 3', 'code' => 'C03', 'capacity' => 35],
            ['name' => 'Class 4', 'code' => 'C04', 'capacity' => 35],
            ['name' => 'Class 5', 'code' => 'C05', 'capacity' => 35],
            ['name' => 'Class 6', 'code' => 'C06', 'capacity' => 40],
            ['name' => 'Class 7', 'code' => 'C07', 'capacity' => 40],
            ['name' => 'Class 8', 'code' => 'C08', 'capacity' => 40],
            ['name' => 'Class 9', 'code' => 'C09', 'capacity' => 40],
            ['name' => 'Class 10', 'code' => 'C10', 'capacity' => 40],
        ];

        foreach ($schools as $school) {
            // Get active academic year for the school
            $activeAcademicYear = AcademicYear::where('school_id', $school->id)
                ->where('is_active', true)
                ->first();

            foreach ($standardClasses as $class) {
                // Create class A
                SchoolClass::create([
                    'name' => $class['name'] . ' A',
                    'code' => $class['code'] . 'A',
                    'description' => $class['name'] . ' Section A',
                    'capacity' => $class['capacity'],
                    'school_id' => $school->id,
                    'academic_year_id' => $activeAcademicYear->id,
                    'is_active' => true,
                ]);

                // Create class B
                SchoolClass::create([
                    'name' => $class['name'] . ' B',
                    'code' => $class['code'] . 'B',
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
