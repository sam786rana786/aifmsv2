<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Student;
use App\Models\TransportRoute;
use App\Models\TransportAssignment;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class TransportAssignmentSeeder extends Seeder
{
    private $paymentFrequencies = ['monthly', 'quarterly', 'annually'];
    private $pickupPoints = [
        'Main Gate', 'Central Park', 'Market Square', 'Temple Road',
        'Hospital Junction', 'Railway Colony', 'Bus Stand', 'Police Station'
    ];

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

            $routes = TransportRoute::where('school_id', $school->id)->get();
            if ($routes->isEmpty()) continue;

            // Get all students for this school
            $students = Student::where('school_id', $school->id)
                ->where('academic_year_id', $activeYear->id)
                ->get();

            // Assign transport to 40% of students randomly
            $transportStudents = $students->random((int)($students->count() * 0.4));

            foreach ($transportStudents as $student) {
                // Randomly select a route
                $route = $routes->random();
                
                // Create transport assignment
                TransportAssignment::create([
                    'student_id' => $student->id,
                    'transport_route_id' => $route->id,
                    'academic_year_id' => $activeYear->id,
                    'school_id' => $school->id,
                    'payment_frequency' => $this->paymentFrequencies[array_rand($this->paymentFrequencies)],
                    'start_date' => $activeYear->start_date,
                    'end_date' => $activeYear->end_date,
                    'pickup_point' => $this->pickupPoints[array_rand($this->pickupPoints)],
                    'drop_point' => $this->pickupPoints[array_rand($this->pickupPoints)],
                    'notes' => 'Regular transport service',
                    'is_active' => true,
                ]);
            }
        }
    }
}
