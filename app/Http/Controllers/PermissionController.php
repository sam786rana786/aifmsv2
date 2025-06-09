<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Services\PermissionManagementService;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PermissionController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:manage permissions', only: ['index', 'show', 'create', 'store', 'edit', 'update', 'destroy', 'sync'  ]),
            new Middleware('can:viewAny,permission', only: ['index', 'show', 'create', 'store', 'edit', 'update', 'destroy', 'sync'  ]),
            new Middleware('can:create,permission', only: ['create', 'store']),
            new Middleware('can:update,permission', only: ['edit', 'update']),
            new Middleware('can:delete,permission', only: ['destroy']),
            new Middleware('can:sync,permission', only: ['sync']),
        ];
    }

    /**
     * Display a listing of permissions.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Permission::class);

        $query = Permission::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('guard_name', 'like', "%{$search}%");
            });
        }

        // Group permissions by category for better organization
        $permissions = $query->orderBy('name')->get()->groupBy(function ($permission) {
            return explode(' ', $permission->name)[0] ?? 'general';
        });

        return Inertia::render('Permissions/Index', [
            'permissions' => $permissions,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new permission.
     */
    public function create()
    {
        $this->authorize('create', Permission::class);

        return Inertia::render('Permissions/Create');
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Permission::class);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:permissions,name',
                'regex:/^[a-z0-9\s\-_]+$/i'
            ],
            'guard_name' => [
                'required',
                'string',
                'in:web,api'
            ],
        ]);

        Permission::create($validated);

        return redirect()->route('permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission)
    {
        $this->authorize('view', $permission);

        // Load roles that have this permission
        $permission->load('roles:id,name');

        // Get users who have this permission (either directly or through roles)
        $usersWithPermission = collect();
        
        // Users with direct permission
        $directUsers = $permission->users()->get(['id', 'name', 'email']);
        
        // Users through roles
        foreach ($permission->roles as $role) {
            $roleUsers = $role->users()->get(['id', 'name', 'email']);
            $usersWithPermission = $usersWithPermission->merge($roleUsers);
        }
        
        $usersWithPermission = $usersWithPermission->merge($directUsers)->unique('id');

        return Inertia::render('Permissions/Show', [
            'permission' => $permission,
            'usersWithPermission' => $usersWithPermission->values(),
        ]);
    }

    /**
     * Show the form for editing the specified permission.
     */
    public function edit(Permission $permission)
    {
        $this->authorize('update', $permission);

        return Inertia::render('Permissions/Edit', [
            'permission' => $permission,
        ]);
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, Permission $permission)
    {
        $this->authorize('update', $permission);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->ignore($permission->id),
                'regex:/^[a-z0-9\s\-_]+$/i'
            ],
            'guard_name' => [
                'required',
                'string',
                'in:web,api'
            ],
        ]);

        $permission->update($validated);

        return redirect()->route('permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(Permission $permission)
    {
        $this->authorize('delete', $permission);

        // Check if permission is assigned to any roles or users
        if ($permission->roles()->exists() || $permission->users()->exists()) {
            return back()->withErrors([
                'error' => 'Cannot delete permission that is assigned to roles or users. Remove all assignments first.'
            ]);
        }

        $permission->delete();

        return redirect()->route('permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }

    /**
     * Sync permissions - create default permissions if they don't exist.
     */
    public function sync()
    {
        $this->authorize('create', Permission::class);

        $defaultPermissions = [
            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Role management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'assign roles',
            
            // Permission management
            'manage permissions',
            
            // School management
            'view schools',
            'create schools',
            'edit schools',
            'delete schools',
            
            // Student management
            'view students',
            'create students',
            'edit students',
            'delete students',
            
            // Fee management
            'view fee types',
            'create fee types',
            'edit fee types',
            'delete fee types',
            'view fee structures',
            'create fee structures',
            'edit fee structures',
            'delete fee structures',
            
            // Payment management
            'view payments',
            'create payments',
            'edit payments',
            'delete payments',
            
            // Class management
            'view classes',
            'create classes',
            'edit classes',
            'delete classes',
            
            // Concession management
            'view concessions',
            'create concessions',
            'edit concessions',
            'delete concessions',
            
            // Transport management
            'view transport routes',
            'create transport routes',
            'edit transport routes',
            'delete transport routes',
            'view transport assignments',
            'create transport assignments',
            'edit transport assignments',
            'delete transport assignments',
            
            // Notification management
            'view notifications',
            'create notifications',
            'edit notifications',
            'delete notifications',
            
            // Report management
            'view reports',
            'generate reports',
            
            // Analytics
            'view analytics',
            
            // Settings
            'view settings',
            'edit settings',
            
            // Activity logs
            'view activity logs',
        ];

        $createdCount = 0;
        foreach ($defaultPermissions as $permissionName) {
            if (!Permission::where('name', $permissionName)->exists()) {
                Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'web'
                ]);
                $createdCount++;
            }
        }

        return redirect()->route('permissions.index')
            ->with('success', "Permissions synced successfully. Created {$createdCount} new permissions.");
    }
}
