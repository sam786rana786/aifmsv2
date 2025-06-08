<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\FeeType;
use Illuminate\Database\Seeder;

class FeeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();
        
        $standardFeeTypes = [
            [
                'name' => 'Tuition Fee',
                'code' => 'TF',
                'description' => 'Monthly tuition fee',
                'frequency' => 'monthly',
                'is_optional' => false,
                'late_fee' => 100.00,
                'late_fee_frequency' => 'per_day',
                'grace_period' => 5,
            ],
            [
                'name' => 'Admission Fee',
                'code' => 'AF',
                'description' => 'One-time admission fee for new students',
                'frequency' => 'one_time',
                'is_optional' => false,
                'late_fee' => 0.00,
                'late_fee_frequency' => null,
                'grace_period' => 0,
            ],
            [
                'name' => 'Exam Fee',
                'code' => 'EF',
                'description' => 'Term examination fee',
                'frequency' => 'term',
                'is_optional' => false,
                'late_fee' => 50.00,
                'late_fee_frequency' => 'fixed',
                'grace_period' => 3,
            ],
            [
                'name' => 'Library Fee',
                'code' => 'LF',
                'description' => 'Annual library fee',
                'frequency' => 'annual',
                'is_optional' => false,
                'late_fee' => 0.00,
                'late_fee_frequency' => null,
                'grace_period' => 0,
            ],
            [
                'name' => 'Computer Lab Fee',
                'code' => 'CLF',
                'description' => 'Computer laboratory fee',
                'frequency' => 'term',
                'is_optional' => true,
                'late_fee' => 0.00,
                'late_fee_frequency' => null,
                'grace_period' => 0,
            ],
            [
                'name' => 'Sports Fee',
                'code' => 'SF',
                'description' => 'Annual sports and games fee',
                'frequency' => 'annual',
                'is_optional' => false,
                'late_fee' => 0.00,
                'late_fee_frequency' => null,
                'grace_period' => 0,
            ],
        ];

        foreach ($schools as $school) {
            foreach ($standardFeeTypes as $feeType) {
                FeeType::create(array_merge($feeType, [
                    'school_id' => $school->id,
                    'is_active' => true,
                ]));
            }
        }
    }
}
