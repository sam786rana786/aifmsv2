<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();

        // Create Super Admin (not tied to any specific school)
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@aifms.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '+91-9999999999',
            'employee_id' => 'SA001',
            'school_id' => null,
            'is_active' => true,
        ]);
        $superAdmin->assignRole('Super Admin');

        foreach ($schools as $school) {
            $schoolCode = strtolower($school->code);
            
            // School Admin
            $schoolAdmin = User::create([
                'name' => 'School Administrator - ' . $school->name,
                'email' => 'admin@' . $schoolCode . '.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '+91-' . rand(7000000000, 9999999999),
                'employee_id' => strtoupper($schoolCode) . '_ADM001',
                'school_id' => $school->id,
                'is_active' => true,
            ]);
            $schoolAdmin->assignRole('School Admin');

            // Accountant
            $accountant = User::create([
                'name' => 'Chief Accountant - ' . $school->name,
                'email' => 'accountant@' . $schoolCode . '.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '+91-' . rand(7000000000, 9999999999),
                'employee_id' => strtoupper($schoolCode) . '_ACC001',
                'school_id' => $school->id,
                'is_active' => true,
            ]);
            $accountant->assignRole('Accountant');

            // Receptionist
            $receptionist = User::create([
                'name' => 'Front Desk Receptionist - ' . $school->name,
                'email' => 'reception@' . $schoolCode . '.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '+91-' . rand(7000000000, 9999999999),
                'employee_id' => strtoupper($schoolCode) . '_REC001',
                'school_id' => $school->id,
                'is_active' => true,
            ]);
            $receptionist->assignRole('Receptionist');

            // Teacher
            $teacher = User::create([
                'name' => 'Senior Teacher - ' . $school->name,
                'email' => 'teacher@' . $schoolCode . '.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '+91-' . rand(7000000000, 9999999999),
                'employee_id' => strtoupper($schoolCode) . '_TCH001',
                'school_id' => $school->id,
                'is_active' => true,
            ]);
            $teacher->assignRole('Teacher');

            // Transport Manager
            $transportManager = User::create([
                'name' => 'Transport Manager - ' . $school->name,
                'email' => 'transport@' . $schoolCode . '.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '+91-' . rand(7000000000, 9999999999),
                'employee_id' => strtoupper($schoolCode) . '_TRM001',
                'school_id' => $school->id,
                'is_active' => true,
            ]);
            $transportManager->assignRole('Transport Manager');

            // Fee Manager
            $feeManager = User::create([
                'name' => 'Fee Manager - ' . $school->name,
                'email' => 'fees@' . $schoolCode . '.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '+91-' . rand(7000000000, 9999999999),
                'employee_id' => strtoupper($schoolCode) . '_FEM001',
                'school_id' => $school->id,
                'is_active' => true,
            ]);
            $feeManager->assignRole('Fee Manager');

            // Data Entry Operator
            $dataEntry = User::create([
                'name' => 'Data Entry Operator - ' . $school->name,
                'email' => 'dataentry@' . $schoolCode . '.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '+91-' . rand(7000000000, 9999999999),
                'employee_id' => strtoupper($schoolCode) . '_DEO001',
                'school_id' => $school->id,
                'is_active' => true,
            ]);
            $dataEntry->assignRole('Data Entry Operator');
        }

        echo "Users created successfully!\n";
        echo "Created 1 Super Admin and " . (count($schools) * 7) . " school-specific users\n";
        echo "Default password for all users: 'password'\n";
        echo "Users created per school: School Admin, Accountant, Receptionist, Teacher, Transport Manager, Fee Manager, Data Entry Operator\n";
    }
} 