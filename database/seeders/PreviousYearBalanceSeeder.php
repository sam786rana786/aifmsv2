<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PreviousYearBalance;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;

class PreviousYearBalanceSeeder extends Seeder
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
            
            if ($academicYears->count() >= 2) {
                $previousYear = $academicYears->first();
                $currentYear = $academicYears->skip(1)->first();

                foreach ($students->take(3) as $student) {
                    if ($student->school_id === $school->id) {
                        $balanceAmount = rand(500, 2000);
                        $adjustmentAmount = rand(0, 200);
                        $finalBalance = $balanceAmount - $adjustmentAmount;

                        PreviousYearBalance::create([
                            'student_id' => $student->id,
                            'academic_year_id' => $currentYear->id,
                            'previous_academic_year_id' => $previousYear->id,
                            'school_id' => $school->id,
                            'balance_amount' => $balanceAmount,
                            'adjustment_amount' => $adjustmentAmount,
                            'final_balance' => $finalBalance,
                            'status' => 'processed',
                            'remarks' => 'Balance carried forward from previous academic year',
                            'processed_by' => $users->first()->id,
                            'processed_at' => now()->subMonths(8)
                        ]);
                    }
                }
            }
        }
    }
}
