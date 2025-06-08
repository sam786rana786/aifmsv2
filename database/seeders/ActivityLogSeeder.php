<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ActivityLog;
use App\Models\School;
use App\Models\User;
use App\Models\Student;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::take(2)->get();
        $users = User::take(3)->get();
        $students = Student::take(5)->get();

        foreach ($schools as $school) {
            foreach ($users->take(2) as $user) {
                if ($user->school_id === $school->id) {
                    // User login activity
                    ActivityLog::create([
                        'user_id' => $user->id,
                        'school_id' => $school->id,
                        'action' => 'login',
                        'description' => 'User logged into the system',
                        'ip_address' => '192.168.1.100',
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'performed_at' => now()->subHours(2)
                    ]);

                    // Student creation activity
                    if ($students->isNotEmpty()) {
                        $student = $students->first();
                        ActivityLog::create([
                            'user_id' => $user->id,
                            'school_id' => $school->id,
                            'action' => 'create',
                            'model_type' => Student::class,
                            'model_id' => $student->id,
                            'description' => 'Created new student: ' . $student->full_name,
                            'new_values' => [
                                'admission_no' => $student->admission_no,
                                'first_name' => $student->first_name,
                                'last_name' => $student->last_name,
                                'class_id' => $student->class_id
                            ],
                            'ip_address' => '192.168.1.100',
                            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                            'performed_at' => now()->subHour()
                        ]);
                    }
                }
            }
        }
    }
}
