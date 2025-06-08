<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();
        
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => $this->getAuthData($user, $request),
            'settings' => $this->getSettings($user),
            'notifications' => $this->getNotifications($user),
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
    
    /**
     * Get comprehensive authentication data for the frontend
     */
    private function getAuthData($user, Request $request): array
    {
        if (!$user) {
            return ['user' => null];
        }

        // Get all permissions and roles
        $permissions = $user->getAllPermissions();
        $roles = $user->roles;
        
        // Create permission arrays for easy frontend checking
        $permissionNames = $permissions->pluck('name')->toArray();
        $roleNames = $roles->pluck('name')->toArray();
        
        // Group permissions by module for easier frontend access
        $permissionsByModule = [];
        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            if (count($parts) >= 2) {
                $module = $parts[0];
                $action = $parts[1];
                $permissionsByModule[$module][$action] = true;
            }
        }
        
        // Check specific common permissions for frontend UI decisions
        $canPermissions = [
            // Academic Management
            'academic_years' => [
                'view' => $user->can('view_academic_years'),
                'create' => $user->can('create_academic_years'),
                'edit' => $user->can('edit_academic_years'),
                'delete' => $user->can('delete_academic_years'),
            ],
            'classes' => [
                'view' => $user->can('view_classes'),
                'create' => $user->can('create_classes'),
                'edit' => $user->can('edit_classes'),
                'delete' => $user->can('delete_classes'),
            ],
            'students' => [
                'view' => $user->can('view_students'),
                'create' => $user->can('create_students'),
                'edit' => $user->can('edit_students'),
                'delete' => $user->can('delete_students'),
            ],
            // Fee Management
            'fee_types' => [
                'view' => $user->can('view_fee_types'),
                'create' => $user->can('create_fee_types'),
                'edit' => $user->can('edit_fee_types'),
                'delete' => $user->can('delete_fee_types'),
            ],
            'payments' => [
                'view' => $user->can('view_payments'),
                'create' => $user->can('create_payments'),
                'edit' => $user->can('edit_payments'),
                'delete' => $user->can('delete_payments'),
            ],
            // Transport Management
            'transport_routes' => [
                'view' => $user->can('view_transport_routes'),
                'create' => $user->can('create_transport_routes'),
                'edit' => $user->can('edit_transport_routes'),
                'delete' => $user->can('delete_transport_routes'),
            ],
            // User Management
            'users' => [
                'view' => $user->can('view_users'),
                'create' => $user->can('create_users'),
                'edit' => $user->can('edit_users'),
                'delete' => $user->can('delete_users'),
            ],
            // Reports & Analytics
            'reports' => [
                'view' => $user->can('view_financial_reports'),
                'export' => $user->can('export_reports'),
            ],
            'analytics' => [
                'view' => $user->can('view_analytics'),
            ],
            // System Settings
            'settings' => [
                'view' => $user->can('view_settings'),
                'edit' => $user->can('edit_settings'),
            ],
        ];

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'school_id' => $user->school_id,
                'employee_id' => $user->employee_id,
                'phone' => $user->phone,
                'profile_picture' => $user->profile_picture,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                // Add school relationship if needed
                'school' => $user->school ? [
                    'id' => $user->school->id,
                    'name' => $user->school->name,
                    'code' => $user->school->code,
                ] : null,
            ],
            'permissions' => [
                'all' => $permissionNames,
                'by_module' => $permissionsByModule,
                'can' => $canPermissions,
            ],
            'roles' => [
                'all' => $roleNames,
                'is_super_admin' => $user->hasRole('Super Admin'),
                'is_school_admin' => $user->hasRole('School Admin'),
                'is_accountant' => $user->hasRole('Accountant'),
                'is_receptionist' => $user->hasRole('Receptionist'),
                'is_teacher' => $user->hasRole('Teacher'),
                'is_transport_manager' => $user->hasRole('Transport Manager'),
                'is_fee_manager' => $user->hasRole('Fee Manager'),
                'is_data_entry_operator' => $user->hasRole('Data Entry Operator'),
            ],
        ];
    }
    
    /**
     * Get settings for the current user
     */
    private function getSettings($user): array
    {
        if (!$user) {
            return [];
        }
        
        $settings = [];
        
        // Get system settings
        $systemSettings = \App\Models\Setting::whereNull('school_id')->get();
        foreach ($systemSettings as $setting) {
            $settings["system.{$setting->key}"] = $setting->value;
        }
        
        // Get school-specific settings if user belongs to a school
        if ($user->school_id) {
            $schoolSettings = \App\Models\Setting::where('school_id', $user->school_id)->get();
            foreach ($schoolSettings as $setting) {
                $settings["school.{$user->school_id}.{$setting->key}"] = $setting->value;
            }
        }
        
        return $settings;
    }
    
    /**
     * Get recent notifications for the current user
     */
    private function getNotifications($user): array
    {
        if (!$user) {
            return [];
        }
        
        return \App\Models\Notification::where('sender_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
