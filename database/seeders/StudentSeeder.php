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
    private $categories = ['General', 'OBC', 'SC', 'ST', 'Other'];
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

            $classes = SchoolClass::where('school_id', $school->id)
                ->where('academic_year_id', $activeYear->id)
                ->get();

            foreach ($classes as $class) {
                // Create 20 students per class
                for ($i = 1; $i <= 20; $i++) {
                    $admissionNo = $school->code . '/' . date('Y') . '/' . str_pad($i, 4, '0', STR_PAD_LEFT);
                    $rollNo = str_pad($i, 2, '0', STR_PAD_LEFT);
                    $gender = $i % 2 == 0 ? 'Male' : 'Female';

                    Student::create([
                        'admission_no' => $admissionNo,
                        'roll_no' => $rollNo,
                        'first_name' => $gender == 'Male' ? $this->getMaleFirstName() : $this->getFemaleFirstName(),
                        'last_name' => $this->getLastName(),
                        'gender' => $gender,
                        'date_of_birth' => now()->subYears(rand(5, 15))->subMonths(rand(1, 12))->format('Y-m-d'),
                        'blood_group' => $this->bloodGroups[array_rand($this->bloodGroups)],
                        'religion' => $this->religions[array_rand($this->religions)],
                        'caste' => 'N/A',
                        'category' => $this->categories[array_rand($this->categories)],
                        'house_name' => $this->houses[array_rand($this->houses)],
                        'nationality' => 'Indian',
                        'admission_date' => now()->format('Y-m-d'),
                        
                        'permanent_address' => $this->getAddress(),
                        'current_address' => $this->getAddress(),
                        'phone' => '+91' . rand(7000000000, 9999999999),
                        'email' => Str::slug($admissionNo) . '@example.com',
                        
                        'father_name' => $this->getMaleFirstName() . ' ' . $this->getLastName(),
                        'father_occupation' => $this->getOccupation(),
                        'father_phone' => '+91' . rand(7000000000, 9999999999),
                        'father_email' => 'father.' . Str::slug($admissionNo) . '@example.com',
                        
                        'mother_name' => $this->getFemaleFirstName() . ' ' . $this->getLastName(),
                        'mother_occupation' => $this->getOccupation(),
                        'mother_phone' => '+91' . rand(7000000000, 9999999999),
                        'mother_email' => 'mother.' . Str::slug($admissionNo) . '@example.com',
                        
                        'guardian_name' => null,
                        'guardian_occupation' => null,
                        'guardian_phone' => null,
                        'guardian_email' => null,
                        'guardian_relation' => null,
                        
                        'previous_school' => null,
                        'previous_class' => null,
                        'transfer_certificate' => null,
                        
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
        $cities = ['Bangalore', 'Mysore', 'Hubli', 'Mangalore', 'Belgaum'];
        
        return rand(1, 999) . ', ' . 
               $streets[array_rand($streets)] . ', ' . 
               $areas[array_rand($areas)] . ', ' . 
               $cities[array_rand($cities)] . ' - ' . 
               rand(560001, 560100);
    }

    private function getOccupation(): string
    {
        $occupations = ['Business', 'Service', 'Doctor', 'Engineer', 'Teacher', 'Lawyer', 'Accountant', 'Consultant'];
        return $occupations[array_rand($occupations)];
    }
}
