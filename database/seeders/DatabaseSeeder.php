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
        $this->call([
            // Core setup
            SchoolSeeder::class,
            AcademicYearSeeder::class,
            
            // Permission system setup (must be before UserSeeder)
            PermissionRoleSeeder::class,
            
            // User setup
            UserSeeder::class,
            
            // Educational structure
            SchoolClassSeeder::class,
            FeeTypeSeeder::class,
            FeeStructureSeeder::class,
            
            // Students and related data
            StudentSeeder::class,
            
            // Transport
            TransportRouteSeeder::class,
            TransportAssignmentSeeder::class,
            
            // Financial operations
            PaymentSeeder::class,
            
            // System settings and features
            SettingSeeder::class,
            NotificationSeeder::class,
            ActivityLogSeeder::class,
            StudentPromotionSeeder::class,
            PreviousYearBalanceSeeder::class,
        ]);
    }
}
