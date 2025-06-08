<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Student;
use App\Models\Payment;
use App\Models\FeeType;
use App\Models\User;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    private $paymentMethods = ['cash', 'online', 'cheque', 'bank_transfer'];
    private $bankNames = ['SBI', 'HDFC', 'ICICI', 'Axis', 'Canara'];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();

        foreach ($schools as $school) {
            // Get active academic year
            $activeYear = AcademicYear::where('school_id', $school->id)
                ->where('is_active', true)
                ->first();

            // Get a staff user for collected_by
            $staffUser = User::where('school_id', $school->id)->first();
            if (!$staffUser) continue;

            // Get all students and fee types for this school
            $students = Student::where('school_id', $school->id)
                ->where('academic_year_id', $activeYear->id)
                ->get();
            $feeTypes = FeeType::where('school_id', $school->id)->get();

            foreach ($students as $student) {
                // Create 3-5 payments per student
                $numPayments = rand(3, 5);
                
                for ($i = 0; $i < $numPayments; $i++) {
                    $feeType = $feeTypes->random();
                    $amount = rand(500, 5000);
                    $paymentMethod = $this->paymentMethods[array_rand($this->paymentMethods)];
                    
                    $payment = [
                        'receipt_number' => $school->code . '/' . date('Y') . '/' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                        'amount' => $amount,
                        'late_fee' => rand(0, 1) ? rand(50, 200) : 0,
                        'payment_method' => $paymentMethod,
                        'payment_date' => now()->subDays(rand(1, 90))->format('Y-m-d'),
                        'remarks' => 'Payment for ' . $feeType->name,
                        'student_id' => $student->id,
                        'fee_type_id' => $feeType->id,
                        'academic_year_id' => $activeYear->id,
                        'school_id' => $school->id,
                        'collected_by' => $staffUser->id,
                        'status' => 'completed',
                        'processed_at' => now(),
                    ];

                    // Add payment method specific details
                    switch ($paymentMethod) {
                        case 'cheque':
                            $payment['cheque_number'] = 'CHQ' . rand(100000, 999999);
                            $payment['bank_name'] = $this->bankNames[array_rand($this->bankNames)];
                            break;
                        case 'online':
                        case 'bank_transfer':
                            $payment['transaction_id'] = 'TXN' . rand(1000000000, 9999999999);
                            $payment['bank_name'] = $this->bankNames[array_rand($this->bankNames)];
                            break;
                    }

                    Payment::create($payment);
                }
            }
        }
    }
}
