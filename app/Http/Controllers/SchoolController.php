<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SchoolController extends Controller implements HasMiddleware
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
            new Middleware('permission:schools.view', only: ['index', 'show']),
            new Middleware('permission:schools.create', only: ['create', 'store']),
            new Middleware('permission:schools.edit', only: ['edit', 'update']),
            new Middleware('permission:schools.delete', only: ['destroy']),
            
            // Apply model authorization middleware
            new Middleware('can:view,school', only: ['show']),
            new Middleware('can:update,school', only: ['edit', 'update']),
            new Middleware('can:delete,school', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of schools.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', School::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $schools = School::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('id', $user->school_id);
            })
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('Schools/Index', [
            'schools' => $schools,
        ]);
    }

    /**
     * Show the form for creating a new school.
     */
    public function create(): Response
    {
        Gate::authorize('create', School::class);

        return Inertia::render('Schools/Create');
    }

    /**
     * Store a newly created school.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', School::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:schools,code',
            'affiliation_number' => 'nullable|string|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'pincode' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|unique:schools,email',
            'website' => 'nullable|url',
            'currency_code' => 'required|string|max:3',
            'is_active' => 'boolean',
        ]);

        $school = School::create($validated);

        return redirect()->route('schools.index')
            ->with('success', 'School created successfully.');
    }

    /**
     * Display the specified school.
     */
    public function show(School $school): Response
    {
        Gate::authorize('view', $school);

        $school->load(['academicYears', 'users', 'students', 'classes']);

        return Inertia::render('Schools/Show', [
            'school' => $school,
        ]);
    }

    /**
     * Show the form for editing the specified school.
     */
    public function edit(School $school): Response
    {
        Gate::authorize('update', $school);

        return Inertia::render('Schools/Edit', [
            'school' => $school,
        ]);
    }

    /**
     * Update the specified school.
     */
    public function update(Request $request, School $school): RedirectResponse
    {
        Gate::authorize('update', $school);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:schools,code,' . $school->id,
            'affiliation_number' => 'nullable|string|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'pincode' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|unique:schools,email,' . $school->id,
            'website' => 'nullable|url',
            'currency_code' => 'required|string|max:3',
            'is_active' => 'boolean',
        ]);

        $school->update($validated);

        return redirect()->route('schools.index')
            ->with('success', 'School updated successfully.');
    }

    /**
     * Remove the specified school.
     */
    public function destroy(School $school): RedirectResponse
    {
        Gate::authorize('delete', $school);

        // Check if school has related data
        if ($school->students()->exists() || $school->users()->exists()) {
            return redirect()->route('schools.index')
                ->with('error', 'Cannot delete school that has students or users associated with it.');
        }

        $school->delete();

        return redirect()->route('schools.index')
            ->with('success', 'School deleted successfully.');
    }
}
