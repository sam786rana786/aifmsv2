<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\FeeType;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class FeeStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();

        foreach ($schools as $school) {
            $activeYear = AcademicYear::where('school_id', $school->id)
                ->where('is_active', true)
                ->first();

            $classes = SchoolClass::where('school_id', $school->id)
                ->where('academic_year_id', $activeYear->id)
                ->get();

            $feeTypes = FeeType::where('school_id', $school->id)->get();

            foreach ($classes as $class) {
                foreach ($feeTypes as $feeType) {
                    // Base amount varies by class level
                    $baseAmount = $this->getBaseAmount($class->name, $feeType->code);

                    FeeStructure::create([
                        'fee_type_id' => $feeType->id,
                        'class_id' => $class->id,
                        'academic_year_id' => $activeYear->id,
                        'school_id' => $school->id,
                        'amount' => $baseAmount,
                        'due_date' => $this->getDueDate($feeType->frequency),
                        'description' => $feeType->name . ' for ' . $class->name,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    private function getBaseAmount(string $className, string $feeTypeCode): float
    {
        // Extract class level for calculation
        $classLevel = 0;
        if (strpos($className, 'LKG') !== false) $classLevel = 1;
        elseif (strpos($className, 'UKG') !== false) $classLevel = 2;
        else {
            preg_match('/Class (\d+)/', $className, $matches);
            $classLevel = isset($matches[1]) ? (int)$matches[1] + 2 : 1;
        }

        // Base amounts for different fee types
        $baseAmounts = [
            'TF' => 2000 + ($classLevel * 200),  // Tuition Fee increases by 200 per level
            'AF' => 5000 + ($classLevel * 500),  // Admission Fee increases by 500 per level
            'EF' => 500 + ($classLevel * 50),    // Exam Fee increases by 50 per level
            'LF' => 1000,                        // Library Fee fixed
            'CLF' => 1500,                       // Computer Lab Fee fixed
            'SF' => 1000,                        // Sports Fee fixed
        ];

        return $baseAmounts[$feeTypeCode] ?? 1000;
    }

    private function getDueDate(string $frequency): string
    {
        $currentYear = date('Y');
        $nextYear = $currentYear + 1;

        $dueDates = [
            'monthly' => date('Y-m-d', strtotime('first day of next month')),
            'term' => date('Y-m-d', strtotime($currentYear . '-07-01')),
            'annual' => date('Y-m-d', strtotime($currentYear . '-06-01')),
            'one_time' => date('Y-m-d', strtotime($currentYear . '-06-01')),
        ];

        return $dueDates[$frequency] ?? date('Y-m-d', strtotime('first day of next month'));
    }
}
