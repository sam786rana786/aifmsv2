<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\StudentPromotion;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;

class StudentPromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::take(2)->get();
        $students = Student::take(5)->get();
        $users = User::take(2)->get();

        foreach ($schools as $school) {
            $academicYears = AcademicYear::where('school_id', $school->id)->get();
            $classes = SchoolClass::where('school_id', $school->id)->get();
            
            if ($academicYears->count() >= 2 && $classes->count() >= 2) {
                $fromYear = $academicYears->first();
                $toYear = $academicYears->skip(1)->first();
                $fromClass = $classes->first();
                $toClass = $classes->skip(1)->first();

                foreach ($students->take(3) as $student) {
                    if ($student->school_id === $school->id) {
                        StudentPromotion::create([
                            'student_id' => $student->id,
                            'from_class_id' => $fromClass->id,
                            'to_class_id' => $toClass->id,
                            'from_academic_year_id' => $fromYear->id,
                            'to_academic_year_id' => $toYear->id,
                            'promotion_date' => now()->subMonths(6),
                            'status' => 'promoted',
                            'remarks' => 'Student promoted based on academic performance',
                            'promoted_by' => $users->first()->id,
                            'school_id' => $school->id
                        ]);
                    }
                }
            }
        }
    }
}
