<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\School;
use App\Models\Student;
use App\Models\User;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some sample data
        $schools = School::take(2)->get();
        $users = User::take(5)->get();
        $students = Student::take(10)->get();

        // Create sample notifications
        foreach ($schools as $school) {
            // System notifications
            Notification::create([
                'type' => 'in_app',
                'title' => 'Welcome to AIFMS v2',
                'message' => 'Your fee management system has been successfully set up.',
                'recipient_type' => User::class,
                'recipient_id' => $users->first()->id,
                'school_id' => $school->id,
                'status' => 'sent',
                'priority' => 'normal',
                'sent_at' => now(),
                'metadata' => [
                    'category' => 'system',
                    'action_url' => '/dashboard'
                ]
            ]);

            // Fee reminder notifications for students
            foreach ($students->take(3) as $student) {
                if ($student->school_id === $school->id) {
                    Notification::create([
                        'type' => 'sms',
                        'title' => 'Fee Payment Reminder',
                        'message' => 'Dear Parent, this is a reminder that fee payment for ' . $student->full_name . ' is due.',
                        'recipient_type' => Student::class,
                        'recipient_id' => $student->id,
                        'school_id' => $school->id,
                        'status' => 'pending',
                        'priority' => 'high',
                        'metadata' => [
                            'category' => 'fee_reminder',
                            'student_id' => $student->id,
                            'amount_due' => 1500.00
                        ]
                    ]);
                }
            }
        }
    }
}
