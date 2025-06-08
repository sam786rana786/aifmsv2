<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\TransportRoute;
use Illuminate\Database\Seeder;

class TransportRouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();
        
        $standardRoutes = [
            [
                'name' => 'Route 1 - North Area',
                'code' => 'R1N',
                'description' => 'Covering northern residential areas',
                'distance' => 8.5,
                'monthly_fee' => 800.00,
                'quarterly_fee' => 2200.00,
                'annual_fee' => 8000.00,
                'capacity' => 40,
                'vehicle_number' => 'KA01AB1234',
                'vehicle_model' => 'Ashok Leyland 40 Seater',
                'driver_name' => 'Mr. Rajesh Kumar',
                'driver_phone' => '+91-9876543240',
                'driver_license' => 'DL98765432109876',
                'pickup_time' => '07:00:00',
                'drop_time' => '16:00:00',
                'is_active' => true,
            ],
            [
                'name' => 'Route 2 - South Area',
                'code' => 'R2S',
                'description' => 'Covering southern residential areas',
                'distance' => 7.0,
                'monthly_fee' => 750.00,
                'quarterly_fee' => 2100.00,
                'annual_fee' => 7500.00,
                'capacity' => 40,
                'vehicle_number' => 'KA01AB1235',
                'vehicle_model' => 'Ashok Leyland 40 Seater',
                'driver_name' => 'Mr. Suresh Singh',
                'driver_phone' => '+91-9876543241',
                'driver_license' => 'DL98765432109877',
                'pickup_time' => '07:15:00',
                'drop_time' => '16:15:00',
                'is_active' => true,
            ],
            [
                'name' => 'Route 3 - East Area',
                'code' => 'R3E',
                'description' => 'Covering eastern residential areas',
                'distance' => 6.0,
                'monthly_fee' => 700.00,
                'quarterly_fee' => 2000.00,
                'annual_fee' => 7000.00,
                'capacity' => 32,
                'vehicle_number' => 'KA01AB1236',
                'vehicle_model' => 'Tata 32 Seater',
                'driver_name' => 'Mr. Ramesh Patil',
                'driver_phone' => '+91-9876543242',
                'driver_license' => 'DL98765432109878',
                'pickup_time' => '07:30:00',
                'drop_time' => '16:30:00',
                'is_active' => true,
            ],
            [
                'name' => 'Route 4 - West Area',
                'code' => 'R4W',
                'description' => 'Covering western residential areas',
                'distance' => 5.5,
                'monthly_fee' => 650.00,
                'quarterly_fee' => 1800.00,
                'annual_fee' => 6500.00,
                'capacity' => 32,
                'vehicle_number' => 'KA01AB1237',
                'vehicle_model' => 'Tata 32 Seater',
                'driver_name' => 'Mr. Mahesh Kumar',
                'driver_phone' => '+91-9876543243',
                'driver_license' => 'DL98765432109879',
                'pickup_time' => '07:45:00',
                'drop_time' => '16:45:00',
                'is_active' => true,
            ],
        ];

        foreach ($schools as $school) {
            foreach ($standardRoutes as $route) {
                TransportRoute::create(array_merge($route, [
                    'school_id' => $school->id,
                ]));
            }
        }
    }
}
