<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AcademicYearController extends Controller implements HasMiddleware
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
            new Middleware('permission:academic_years.view', only: ['index', 'show']),
            new Middleware('permission:academic_years.create', only: ['create', 'store']),
            new Middleware('permission:academic_years.edit', only: ['edit', 'update']),
            new Middleware('permission:academic_years.delete', only: ['destroy']),
            
            // Apply model authorization middleware using can middleware
            new Middleware('can:view,academic_year', only: ['show']),
            new Middleware('can:update,academic_year', only: ['edit', 'update']),
            new Middleware('can:delete,academic_year', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of academic years.
     */
    public function index(): Response
    {
        // Use policy for additional authorization if needed
        Gate::authorize('viewAny', AcademicYear::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $academicYears = AcademicYear::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->orderBy('start_date', 'desc')
            ->paginate(15);

        return Inertia::render('AcademicYears/Index', [
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Show the form for creating a new academic year.
     */
    public function create(): Response
    {
        // Authorization handled by middleware and policy
        Gate::authorize('create', AcademicYear::class);

        return Inertia::render('AcademicYears/Create');
    }

    /**
     * Store a newly created academic year.
     */
    public function store(Request $request): RedirectResponse
    {
        // Authorization handled by middleware and policy
        Gate::authorize('create', AcademicYear::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
            'school_id' => 'sometimes|exists:schools,id', // Allow Super Admin to specify school
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Determine school_id based on user role
        $schoolId = $user->hasRole('Super Admin') && $request->has('school_id')
            ? $validated['school_id']
            : $user->school_id;

        // If this is set as current, unset others for the target school
        if ($validated['is_current'] ?? false) {
            AcademicYear::where('school_id', $schoolId)
                ->update(['is_current' => false]);
        }

        $academicYear = AcademicYear::create([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_current' => $validated['is_current'] ?? false,
            'school_id' => $schoolId,
        ]);

        return redirect()->route('academic-years.index')
            ->with('success', 'Academic year created successfully.');
    }

    /**
     * Display the specified academic year.
     */
    public function show(AcademicYear $academicYear): Response
    {
        // Authorization handled by middleware and policy - but we can add extra checks
        // The 'can:view,academic_year' middleware already handles this, but we keep this for explicitness
        Gate::authorize('view', $academicYear);

        return Inertia::render('AcademicYears/Show', [
            'academicYear' => $academicYear,
        ]);
    }

    /**
     * Show the form for editing the specified academic year.
     */
    public function edit(AcademicYear $academicYear): Response
    {
        // Authorization handled by middleware and policy
        Gate::authorize('update', $academicYear);

        return Inertia::render('AcademicYears/Edit', [
            'academicYear' => $academicYear,
        ]);
    }

    /**
     * Update the specified academic year.
     */
    public function update(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        // Authorization handled by middleware and policy
        Gate::authorize('update', $academicYear);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
        ]);

        // If this is set as current, unset others in the same school
        if ($validated['is_current'] ?? false) {
            AcademicYear::where('school_id', $academicYear->school_id)
                ->where('id', '!=', $academicYear->id)
                ->update(['is_current' => false]);
        }

        $academicYear->update($validated);

        return redirect()->route('academic-years.index')
            ->with('success', 'Academic year updated successfully.');
    }

    /**
     * Remove the specified academic year.
     */
    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        // Authorization handled by middleware and policy
        Gate::authorize('delete', $academicYear);

        // Additional business logic check
        if ($academicYear->is_current) {
            return redirect()->route('academic-years.index')
                ->with('error', 'Cannot delete the current academic year.');
        }

        $academicYear->delete();

        return redirect()->route('academic-years.index')
            ->with('success', 'Academic year deleted successfully.');
    }
}
