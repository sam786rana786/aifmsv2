<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = [
            [
                'name' => 'Global International School',
                'code' => 'GIS001',
                'address' => '123 Education Street, Knowledge City',
                'phone' => '+91-9876543210',
                'email' => 'info@globalschool.edu',
                'website' => 'www.globalschool.edu',
                'principal_name' => 'Dr. Sarah Johnson',
                'principal_phone' => '+91-9876543211',
                'principal_email' => 'principal@globalschool.edu',
                'is_active' => true,
            ],
            [
                'name' => 'New Horizon Public School',
                'code' => 'NHPS001',
                'address' => '456 Learning Avenue, Education Park',
                'phone' => '+91-9876543220',
                'email' => 'contact@newhorizon.edu',
                'website' => 'www.newhorizon.edu',
                'principal_name' => 'Dr. Michael Brown',
                'principal_phone' => '+91-9876543221',
                'principal_email' => 'principal@newhorizon.edu',
                'is_active' => true,
            ],
            [
                'name' => 'Excellence Academy',
                'code' => 'EA001',
                'address' => '789 Success Road, Achievement District',
                'phone' => '+91-9876543230',
                'email' => 'info@excellenceacademy.edu',
                'website' => 'www.excellenceacademy.edu',
                'principal_name' => 'Dr. Emily Wilson',
                'principal_phone' => '+91-9876543231',
                'principal_email' => 'principal@excellenceacademy.edu',
                'is_active' => true,
            ],
        ];

        foreach ($schools as $school) {
            School::create($school);
        }
    }
}
