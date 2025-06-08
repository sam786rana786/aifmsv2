<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\School;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();

        // Global system settings
        $globalSettings = [
            [
                'key' => 'system_name',
                'value' => 'AIFMS v2',
                'type' => 'string',
                'category' => 'system',
                'description' => 'System name displayed throughout the application',
                'is_public' => true
            ],
            [
                'key' => 'default_currency',
                'value' => 'INR',
                'type' => 'string',
                'category' => 'system',
                'description' => 'Default currency for the system',
                'is_public' => true
            ],
            [
                'key' => 'date_format',
                'value' => 'Y-m-d',
                'type' => 'string',
                'category' => 'system',
                'description' => 'Default date format',
                'is_public' => true
            ],
            [
                'key' => 'timezone',
                'value' => 'Asia/Kolkata',
                'type' => 'string',
                'category' => 'system',
                'description' => 'System timezone',
                'is_public' => true
            ]
        ];

        foreach ($globalSettings as $setting) {
            Setting::create($setting);
        }

        // School-specific settings
        foreach ($schools as $school) {
            $schoolSettings = [
                [
                    'key' => 'school_logo',
                    'value' => '',
                    'type' => 'file',
                    'school_id' => $school->id,
                    'category' => 'appearance',
                    'description' => 'School logo image',
                    'is_public' => true
                ],
                [
                    'key' => 'school_favicon',
                    'value' => '',
                    'type' => 'file',
                    'school_id' => $school->id,
                    'category' => 'appearance',
                    'description' => 'School favicon',
                    'is_public' => true
                ],
                [
                    'key' => 'late_fee_percentage',
                    'value' => '10',
                    'type' => 'integer',
                    'school_id' => $school->id,
                    'category' => 'fees',
                    'description' => 'Late fee percentage',
                    'is_public' => false
                ],
                [
                    'key' => 'grace_period_days',
                    'value' => '7',
                    'type' => 'integer',
                    'school_id' => $school->id,
                    'category' => 'fees',
                    'description' => 'Grace period for fee payment in days',
                    'is_public' => false
                ],
                [
                    'key' => 'enable_sms_notifications',
                    'value' => 'true',
                    'type' => 'boolean',
                    'school_id' => $school->id,
                    'category' => 'notifications',
                    'description' => 'Enable SMS notifications',
                    'is_public' => false
                ],
                [
                    'key' => 'enable_email_notifications',
                    'value' => 'true',
                    'type' => 'boolean',
                    'school_id' => $school->id,
                    'category' => 'notifications',
                    'description' => 'Enable email notifications',
                    'is_public' => false
                ]
            ];

            foreach ($schoolSettings as $setting) {
                Setting::create($setting);
            }
        }
    }
}
