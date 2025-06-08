<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call seeders in order of dependencies
        $this->call([
            SchoolSeeder::class,          // First, create schools
            AcademicYearSeeder::class,    // Then academic years (needs schools)
            SchoolClassSeeder::class,     // Classes (needs schools and academic years)
            FeeTypeSeeder::class,         // Fee types (needs schools)
            TransportRouteSeeder::class,  // Transport routes (needs schools)
            StudentSeeder::class,         // Students (needs schools, classes, academic years)
            FeeStructureSeeder::class,    // Fee structures (needs fee types, classes)
            PaymentSeeder::class,         // Payments (needs students, fee types)
            TransportAssignmentSeeder::class, // Transport assignments (needs students, routes)
        ]);
    }
}
