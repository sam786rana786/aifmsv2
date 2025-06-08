<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    private $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
    private $religions = ['Hindu', 'Muslim', 'Christian', 'Sikh', 'Buddhist', 'Jain', 'Other'];
    private $houses = ['Red', 'Blue', 'Green', 'Yellow'];

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

            if (!$activeYear) continue;

            $classes = SchoolClass::where('school_id', $school->id)
                ->where('academic_year_id', $activeYear->id)
                ->get();

            foreach ($classes as $class) {
                // Create 10 students per class
                for ($i = 1; $i <= 10; $i++) {
                    $admissionNo = $school->code . '/' . date('Y') . '/' . str_pad(($class->id * 100 + $i), 4, '0', STR_PAD_LEFT);
                    $rollNo = str_pad($i, 2, '0', STR_PAD_LEFT);
                    $gender = $i % 2 == 0 ? 'male' : 'female';

                    Student::create([
                        'admission_no' => $admissionNo,
                        'roll_no' => $rollNo,
                        'first_name' => $gender == 'male' ? $this->getMaleFirstName() : $this->getFemaleFirstName(),
                        'last_name' => $this->getLastName(),
                        'gender' => $gender,
                        'date_of_birth' => now()->subYears(rand(5, 15))->subMonths(rand(1, 12))->format('Y-m-d'),
                        'blood_group' => $this->bloodGroups[array_rand($this->bloodGroups)],
                        'religion' => $this->religions[array_rand($this->religions)],
                        'caste' => 'N/A',
                        'nationality' => 'Indian',
                        'aadhar_number' => null,
                        'house_name' => $this->houses[array_rand($this->houses)],
                        'address' => $this->getAddress(),
                        'city' => 'Bangalore',
                        'state' => 'Karnataka',
                        'country' => 'India',
                        'pincode' => (string) rand(560001, 560100),
                        'phone' => '+91' . rand(7000000000, 9999999999),
                        'email' => Str::slug($admissionNo) . '@example.com',
                        
                        'father_name' => $this->getMaleFirstName() . ' ' . $this->getLastName(),
                        'father_phone' => '+91' . rand(7000000000, 9999999999),
                        'father_occupation' => $this->getOccupation(),
                        
                        'mother_name' => $this->getFemaleFirstName() . ' ' . $this->getLastName(),
                        'mother_phone' => '+91' . rand(7000000000, 9999999999),
                        'mother_occupation' => $this->getOccupation(),
                        
                        'guardian_name' => null,
                        'guardian_phone' => null,
                        'guardian_occupation' => null,
                        'guardian_relation' => null,
                        'photo_path' => null,
                        
                        'admission_date' => now()->format('Y-m-d'),
                        'previous_school' => null,
                        'previous_qualification' => null,
                        'documents' => null,
                        
                        'school_id' => $school->id,
                        'academic_year_id' => $activeYear->id,
                        'class_id' => $class->id,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    private function getMaleFirstName(): string
    {
        $names = ['Aarav', 'Vihaan', 'Arjun', 'Vivaan', 'Aditya', 'Rayan', 'Reyansh', 'Krishna', 'Ishaan', 'Shaurya'];
        return $names[array_rand($names)];
    }

    private function getFemaleFirstName(): string
    {
        $names = ['Aaradhya', 'Ananya', 'Diya', 'Saanvi', 'Pari', 'Anika', 'Riya', 'Angel', 'Shreya', 'Zara'];
        return $names[array_rand($names)];
    }

    private function getLastName(): string
    {
        $names = ['Sharma', 'Verma', 'Kumar', 'Singh', 'Patel', 'Rao', 'Reddy', 'Nair', 'Pillai', 'Iyer'];
        return $names[array_rand($names)];
    }

    private function getAddress(): string
    {
        $streets = ['MG Road', 'Gandhi Street', 'Nehru Road', 'Temple Street', 'Church Road'];
        $areas = ['Jayanagar', 'Indiranagar', 'Koramangala', 'HSR Layout', 'BTM Layout'];
        
        return rand(1, 999) . ', ' . 
               $streets[array_rand($streets)] . ', ' . 
               $areas[array_rand($areas)];
    }

    private function getOccupation(): string
    {
        $occupations = ['Business', 'Service', 'Doctor', 'Engineer', 'Teacher', 'Lawyer', 'Accountant', 'Consultant'];
        return $occupations[array_rand($occupations)];
    }
}
