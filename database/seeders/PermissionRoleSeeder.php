<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // School Management
            'manage_schools',
            'view_schools',
            'create_schools',
            'edit_schools',
            'delete_schools',

            // User Management
            'manage_users',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'assign_roles',

            // Student Management
            'manage_students',
            'view_students',
            'create_students',
            'edit_students',
            'delete_students',
            'promote_students',
            'transfer_students',
            'view_student_documents',
            'manage_student_documents',

            // Class Management
            'manage_classes',
            'view_classes',
            'create_classes',
            'edit_classes',
            'delete_classes',

            // Academic Year Management
            'manage_academic_years',
            'view_academic_years',
            'create_academic_years',
            'edit_academic_years',
            'delete_academic_years',

            // Fee Management
            'manage_fee_types',
            'view_fee_types',
            'create_fee_types',
            'edit_fee_types',
            'delete_fee_types',
            'manage_fee_structures',
            'view_fee_structures',
            'create_fee_structures',
            'edit_fee_structures',
            'delete_fee_structures',

            // Payment Management
            'manage_payments',
            'view_payments',
            'create_payments',
            'edit_payments',
            'delete_payments',
            'collect_payments',
            'refund_payments',
            'view_payment_reports',

            // Concession Management
            'manage_concessions',
            'view_concessions',
            'create_concessions',
            'edit_concessions',
            'delete_concessions',
            'approve_concessions',
            'reject_concessions',

            // Transport Management
            'manage_transport_routes',
            'view_transport_routes',
            'create_transport_routes',
            'edit_transport_routes',
            'delete_transport_routes',
            'manage_transport_assignments',
            'view_transport_assignments',
            'create_transport_assignments',
            'edit_transport_assignments',
            'delete_transport_assignments',

            // Reports
            'view_financial_reports',
            'view_student_reports',
            'view_fee_reports',
            'view_transport_reports',
            'view_administrative_reports',
            'export_reports',

            // Settings
            'manage_settings',
            'view_settings',
            'edit_settings',
            'manage_notifications',
            'view_notifications',

            // Activity Logs
            'view_activity_logs',
            'manage_activity_logs',

            // Dashboard Access
            'view_dashboard',
            'view_analytics',

            // Previous Year Balance
            'manage_previous_balances',
            'view_previous_balances',
            'adjust_previous_balances',

            // Student Promotions
            'manage_promotions',
            'view_promotions',
            'bulk_promote_students',
            'rollback_promotions',

            // Advanced Features
            'manage_backup_restore',
            'view_system_info',
            'manage_database',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Super Admin Role - Has all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(Permission::all());

        // School Admin Role - Can manage most things within their school
        $schoolAdmin = Role::firstOrCreate(['name' => 'School Admin']);
        $schoolAdmin->syncPermissions([
            'view_schools', 'edit_schools',
            'manage_users', 'view_users', 'create_users', 'edit_users', 'assign_roles',
            'manage_students', 'view_students', 'create_students', 'edit_students', 'promote_students', 'transfer_students', 'view_student_documents', 'manage_student_documents',
            'manage_classes', 'view_classes', 'create_classes', 'edit_classes',
            'manage_academic_years', 'view_academic_years', 'create_academic_years', 'edit_academic_years',
            'manage_fee_types', 'view_fee_types', 'create_fee_types', 'edit_fee_types',
            'manage_fee_structures', 'view_fee_structures', 'create_fee_structures', 'edit_fee_structures',
            'manage_payments', 'view_payments', 'create_payments', 'edit_payments', 'collect_payments', 'refund_payments', 'view_payment_reports',
            'manage_concessions', 'view_concessions', 'create_concessions', 'edit_concessions', 'approve_concessions', 'reject_concessions',
            'manage_transport_routes', 'view_transport_routes', 'create_transport_routes', 'edit_transport_routes',
            'manage_transport_assignments', 'view_transport_assignments', 'create_transport_assignments', 'edit_transport_assignments',
            'view_financial_reports', 'view_student_reports', 'view_fee_reports', 'view_payment_reports', 'view_transport_reports', 'view_administrative_reports', 'export_reports',
            'manage_settings', 'view_settings', 'edit_settings', 'manage_notifications', 'view_notifications',
            'view_activity_logs',
            'view_dashboard', 'view_analytics',
            'manage_previous_balances', 'view_previous_balances', 'adjust_previous_balances',
            'manage_promotions', 'view_promotions', 'bulk_promote_students', 'rollback_promotions',
        ]);

        // Accountant Role - Focused on financial operations
        $accountant = Role::firstOrCreate(['name' => 'Accountant']);
        $accountant->syncPermissions([
            'view_students',
            'view_classes',
            'view_fee_types', 'view_fee_structures',
            'manage_payments', 'view_payments', 'create_payments', 'edit_payments', 'collect_payments', 'refund_payments', 'view_payment_reports',
            'manage_concessions', 'view_concessions', 'create_concessions', 'edit_concessions', 'approve_concessions',
            'view_financial_reports', 'view_fee_reports', 'view_payment_reports', 'export_reports',
            'view_dashboard',
            'manage_previous_balances', 'view_previous_balances', 'adjust_previous_balances',
        ]);

        // Receptionist Role - Student admission and basic operations
        $receptionist = Role::firstOrCreate(['name' => 'Receptionist']);
        $receptionist->syncPermissions([
            'view_students', 'create_students', 'edit_students', 'view_student_documents', 'manage_student_documents',
            'view_classes',
            'view_fee_types', 'view_fee_structures',
            'view_payments', 'create_payments', 'collect_payments',
            'view_concessions', 'create_concessions',
            'view_transport_routes', 'view_transport_assignments', 'create_transport_assignments', 'edit_transport_assignments',
            'view_dashboard',
        ]);

        // Teacher Role - Basic viewing permissions
        $teacher = Role::firstOrCreate(['name' => 'Teacher']);
        $teacher->syncPermissions([
            'view_students',
            'view_classes',
            'view_dashboard',
        ]);

        // Transport Manager Role - Transport related operations
        $transportManager = Role::firstOrCreate(['name' => 'Transport Manager']);
        $transportManager->syncPermissions([
            'view_students',
            'manage_transport_routes', 'view_transport_routes', 'create_transport_routes', 'edit_transport_routes',
            'manage_transport_assignments', 'view_transport_assignments', 'create_transport_assignments', 'edit_transport_assignments',
            'view_transport_reports', 'export_reports',
            'view_dashboard',
        ]);

        // Fee Manager Role - Fee structure and concession management
        $feeManager = Role::firstOrCreate(['name' => 'Fee Manager']);
        $feeManager->syncPermissions([
            'view_students',
            'view_classes',
            'manage_fee_types', 'view_fee_types', 'create_fee_types', 'edit_fee_types',
            'manage_fee_structures', 'view_fee_structures', 'create_fee_structures', 'edit_fee_structures',
            'view_payments', 'view_payment_reports',
            'manage_concessions', 'view_concessions', 'create_concessions', 'edit_concessions', 'approve_concessions', 'reject_concessions',
            'view_financial_reports', 'view_fee_reports', 'export_reports',
            'view_dashboard',
        ]);

        // Data Entry Operator Role - Limited data entry permissions
        $dataEntry = Role::firstOrCreate(['name' => 'Data Entry Operator']);
        $dataEntry->syncPermissions([
            'view_students', 'create_students', 'edit_students',
            'view_classes',
            'view_payments', 'create_payments',
            'view_dashboard',
        ]);

        echo "Roles and permissions created successfully!\n";
        echo "Created roles: Super Admin, School Admin, Accountant, Receptionist, Teacher, Transport Manager, Fee Manager, Data Entry Operator\n";
        echo "Created " . count($permissions) . " permissions\n";
    }
} 