# Laravel Controller Middleware & Authorization Patterns

Based on Context7 documentation for Laravel and Laravel Permission package, here are the recommended patterns for implementing middleware and authorization in AIFMS v2 controllers.

## Modern Laravel 11+ Middleware Pattern

### 1. Using HasMiddleware Interface (Recommended)

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ExampleController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            // Apply auth middleware to all actions
            'auth',
            
            // Apply permission-based middleware to specific actions
            new Middleware('permission:resource.view', only: ['index', 'show']),
            new Middleware('permission:resource.create', only: ['create', 'store']),
            new Middleware('permission:resource.edit', only: ['edit', 'update']),
            new Middleware('permission:resource.delete', only: ['destroy']),
            
            // Apply model authorization middleware using can middleware
            new Middleware('can:view,model_name', only: ['show']),
            new Middleware('can:update,model_name', only: ['edit', 'update']),
            new Middleware('can:delete,model_name', only: ['destroy']),
        ];
    }
}
```

### 2. Alternative: Traditional __construct Pattern (Legacy)

```php
public function __construct()
{
    $this->middleware('auth');
    $this->middleware('permission:resource.view')->only(['index', 'show']);
    $this->middleware('permission:resource.create')->only(['create', 'store']);
    $this->middleware('permission:resource.edit')->only(['edit', 'update']);
    $this->middleware('permission:resource.delete')->only(['destroy']);
}
```

## Authorization Patterns

### 1. Policy-Based Authorization with before() Method

#### Policy Implementation:
```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Resource;
use Illuminate\Auth\Access\Response;

class ResourcePolicy
{
    /**
     * Perform pre-authorization checks.
     * Grant super-admin all permissions before other checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
 
        return null; // Let other authorization methods handle the check
    }

    public function viewAny(User $user): bool
    {
        return $user->can('resource.view');
    }

    public function view(User $user, Resource $resource): bool
    {
        if (!$user->can('resource.view')) {
            return false;
        }

        // School-based access control
        return $user->school_id === $resource->school_id;
    }

    public function create(User $user): bool
    {
        return $user->can('resource.create') && $user->school_id !== null;
    }

    public function update(User $user, Resource $resource): bool
    {
        if (!$user->can('resource.edit')) {
            return false;
        }

        return $user->school_id === $resource->school_id;
    }

    public function delete(User $user, Resource $resource): bool
    {
        if (!$user->can('resource.delete')) {
            return false;
        }

        return $user->school_id === $resource->school_id;
    }
}
```

### 2. Controller Authorization Methods

```php
// Using Gate facade (explicit)
use Illuminate\Support\Facades\Gate;

public function index(): Response
{
    Gate::authorize('viewAny', Resource::class);
    // ... rest of method
}

// Using AuthorizesRequests trait (cleaner)
public function index(): Response
{
    $this->authorize('viewAny', Resource::class);
    // ... rest of method
}

// Model-specific authorization
public function show(Resource $resource): Response
{
    $this->authorize('view', $resource);
    // ... rest of method
}
```

## Permission-Based Middleware Configuration

### 1. Laravel Permission Package Middleware

Register in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})
```

### 2. Usage in Controllers

```php
// Single permission
new Middleware('permission:edit articles', only: ['edit', 'update']),

// Multiple permissions (OR logic)
new Middleware('permission:edit articles|delete articles', only: ['update']),

// Role-based
new Middleware('role:admin', only: ['destroy']),

// Combined role or permission
new Middleware('role_or_permission:admin|edit articles', only: ['update']),

// With specific guard
new Middleware('permission:edit articles,api', only: ['update']),
```

## Super Admin Implementation

### 1. Using Gate::before in AppServiceProvider

```php
use Illuminate\Support\Facades\Gate;

public function boot()
{
    // Grant Super Admin all permissions
    Gate::before(function ($user, $ability) {
        return $user->hasRole('Super Admin') ? true : null;
    });
}
```

### 2. Policy-based Super Admin (Recommended)

Use the `before()` method in policies as shown above.

## Data Filtering Based on User Role

### 1. In Controller Methods

```php
public function index(): Response
{
    $user = Auth::user();
    
    $resources = Resource::query()
        ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
            $query->where('school_id', $user->school_id);
        })
        ->paginate(15);

    return Inertia::render('Resources/Index', [
        'resources' => $resources,
    ]);
}
```

### 2. Using Query Scopes (Alternative)

```php
// In Model
public function scopeForUser($query, User $user)
{
    if ($user->hasRole('Super Admin')) {
        return $query;
    }
    
    return $query->where('school_id', $user->school_id);
}

// In Controller
$resources = Resource::forUser(Auth::user())->paginate(15);
```

## Route-Level Authorization

### 1. Using can Middleware

```php
Route::group(['middleware' => ['can:create,App\Models\Resource']], function () {
    Route::post('/resources', [ResourceController::class, 'store']);
});
```

### 2. Using Permission Middleware

```php
Route::group(['middleware' => ['permission:resource.create']], function () {
    Route::get('/resources/create', [ResourceController::class, 'create']);
    Route::post('/resources', [ResourceController::class, 'store']);
});
```

## Best Practices Summary

1. **Use HasMiddleware Interface** for Laravel 11+ controllers
2. **Implement before() method** in policies for Super Admin access
3. **Combine permission middleware with model authorization** for comprehensive security
4. **Use explicit authorization calls** in controller methods for clarity
5. **Filter data based on user context** (school_id, role, etc.)
6. **Leverage Laravel Permission package** for complex permission scenarios
7. **Document authorization logic** clearly in policies and controllers

## AIFMS v2 Specific Patterns

### School-Based Multi-Tenancy

```php
// In controllers, always filter by school unless Super Admin
$query->when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
    $q->where('school_id', $user->school_id);
});

// In policies, check school ownership
return $user->school_id === $resource->school_id;
```

### Permission Naming Convention

```php
// Format: {module}.{action}
'academic_years.view'
'academic_years.create'
'academic_years.edit'
'academic_years.delete'
'students.view'
'payments.create'
// etc.
```

This documentation provides the foundation for implementing consistent, secure, and maintainable authorization patterns across all AIFMS v2 controllers. 