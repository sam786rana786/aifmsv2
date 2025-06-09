<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;
use App\Models\School;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:users.view', only: ['index', 'show']),
            new Middleware('permission:users.create', only: ['create', 'store']),
            new Middleware('permission:users.edit', only: ['edit', 'update']),
            new Middleware('permission:users.delete', only: ['destroy']),
            new Middleware('can:view,user', only: ['show']),
            new Middleware('can:update,user', only: ['edit', 'update']),
            new Middleware('can:delete,user', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $users = User::query()
            ->with(['school', 'roles'])
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->when($request->is_active !== null, function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // Get filter options
        $roles = Role::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only(['search', 'role', 'is_active']),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        Gate::authorize('create', User::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schools = $user->hasRole('Super Admin') 
            ? School::where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : collect([$user->school]);

        $roles = Role::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Users/Create', [
            'schools' => $schools,
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id',
            'is_active' => 'boolean',
            'school_id' => 'sometimes|exists:schools,id',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Determine school_id based on user role
        $schoolId = $user->hasRole('Super Admin') && $request->has('school_id')
            ? $validated['school_id']
            : $user->school_id;

        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'employee_id' => $validated['employee_id'],
            'is_active' => $validated['is_active'] ?? true,
            'school_id' => $schoolId,
        ]);

        // Assign roles
        $newUser->assignRole($validated['roles']);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        Gate::authorize('view', $user);

        $user->load(['school', 'roles', 'permissions']);

        return Inertia::render('Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        Gate::authorize('update', $user);

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $schools = $currentUser->hasRole('Super Admin') 
            ? School::where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : collect([$user->school]);

        $roles = Role::orderBy('name')->get(['id', 'name']);
        $userRoles = $user->roles->pluck('name')->toArray();

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'schools' => $schools,
            'roles' => $roles,
            'userRoles' => $userRoles,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id,' . $user->id,
            'is_active' => 'boolean',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'employee_id' => $validated['employee_id'],
            'is_active' => $validated['is_active'] ?? true,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Sync roles
        $user->syncRoles($validated['roles']);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        // Prevent deletion of current user
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
