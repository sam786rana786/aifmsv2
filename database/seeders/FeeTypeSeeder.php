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
                'has_late_fee' => true,
                'late_fee_amount' => 100.00,
                'late_fee_grace_days' => 5,
            ],
            [
                'name' => 'Admission Fee',
                'code' => 'AF',
                'description' => 'One-time admission fee for new students',
                'frequency' => 'one_time',
                'is_optional' => false,
                'has_late_fee' => false,
                'late_fee_amount' => null,
                'late_fee_grace_days' => null,
            ],
            [
                'name' => 'Exam Fee',
                'code' => 'EF',
                'description' => 'Term examination fee',
                'frequency' => 'quarterly',
                'is_optional' => false,
                'has_late_fee' => true,
                'late_fee_amount' => 50.00,
                'late_fee_grace_days' => 3,
            ],
            [
                'name' => 'Library Fee',
                'code' => 'LF',
                'description' => 'Annual library fee',
                'frequency' => 'annually',
                'is_optional' => false,
                'has_late_fee' => false,
                'late_fee_amount' => null,
                'late_fee_grace_days' => null,
            ],
            [
                'name' => 'Computer Lab Fee',
                'code' => 'CLF',
                'description' => 'Computer laboratory fee',
                'frequency' => 'quarterly',
                'is_optional' => true,
                'has_late_fee' => false,
                'late_fee_amount' => null,
                'late_fee_grace_days' => null,
            ],
            [
                'name' => 'Sports Fee',
                'code' => 'SF',
                'description' => 'Annual sports and games fee',
                'frequency' => 'annually',
                'is_optional' => false,
                'has_late_fee' => false,
                'late_fee_amount' => null,
                'late_fee_grace_days' => null,
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
