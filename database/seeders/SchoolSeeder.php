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
                'affiliation_number' => 'CBSE/GIS/2024/001',
                'address' => '123 Education Street, Knowledge City',
                'city' => 'Knowledge City',
                'state' => 'Karnataka',
                'country' => 'India',
                'pincode' => '560001',
                'phone' => '+91-9876543210',
                'email' => 'info@globalschool.edu',
                'website' => 'www.globalschool.edu',
                'logo_path' => null,
                'favicon_path' => null,
                'currency_code' => 'INR',
                'is_active' => true,
            ],
            [
                'name' => 'New Horizon Public School',
                'code' => 'NHPS001',
                'affiliation_number' => 'CBSE/NHPS/2024/002',
                'address' => '456 Learning Avenue, Education Park',
                'city' => 'Education Park',
                'state' => 'Karnataka',
                'country' => 'India',
                'pincode' => '560002',
                'phone' => '+91-9876543220',
                'email' => 'contact@newhorizon.edu',
                'website' => 'www.newhorizon.edu',
                'logo_path' => null,
                'favicon_path' => null,
                'currency_code' => 'INR',
                'is_active' => true,
            ],
            [
                'name' => 'Excellence Academy',
                'code' => 'EA001',
                'affiliation_number' => 'CBSE/EA/2024/003',
                'address' => '789 Success Road, Achievement District',
                'city' => 'Achievement District',
                'state' => 'Karnataka',
                'country' => 'India',
                'pincode' => '560003',
                'phone' => '+91-9876543230',
                'email' => 'info@excellenceacademy.edu',
                'website' => 'www.excellenceacademy.edu',
                'logo_path' => null,
                'favicon_path' => null,
                'currency_code' => 'INR',
                'is_active' => true,
            ],
        ];

        foreach ($schools as $school) {
            School::create($school);
        }
    }
}
