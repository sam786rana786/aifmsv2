<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\SchoolClass;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SchoolClassController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:school_classes.view', only: ['index', 'show']),
            new Middleware('permission:school_classes.create', only: ['create', 'store']),
            new Middleware('permission:school_classes.edit', only: ['edit', 'update']),
            new Middleware('permission:school_classes.delete', only: ['destroy']),
            new Middleware('can:view,schoolClass', only: ['show']),
            new Middleware('can:update,schoolClass', only: ['edit', 'update']),
            new Middleware('can:delete,schoolClass', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of school classes.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', SchoolClass::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $classes = SchoolClass::query()
            ->with(['school'])
            ->withCount(['students'])
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SchoolClasses/Index', [
            'classes' => $classes,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new school class.
     */
    public function create(): Response
    {
        Gate::authorize('create', SchoolClass::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schools = $user->hasRole('Super Admin') 
            ? School::where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : collect([$user->school]);

        return Inertia::render('SchoolClasses/Create', [
            'schools' => $schools,
        ]);
    }

    /**
     * Store a newly created school class.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', SchoolClass::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'section' => 'nullable|string|max:10',
            'capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'school_id' => 'sometimes|exists:schools,id',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Determine school_id based on user role
        $schoolId = $user->hasRole('Super Admin') && $request->has('school_id')
            ? $validated['school_id']
            : $user->school_id;

        $validated['school_id'] = $schoolId;

        // Check for duplicate class name in the same school
        $exists = SchoolClass::where('school_id', $schoolId)
            ->where('name', $validated['name'])
            ->when($validated['section'], function ($query, $section) {
                $query->where('section', $section);
            })
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'name' => 'A class with this name' . ($validated['section'] ? ' and section' : '') . ' already exists in this school.'
            ]);
        }

        $class = SchoolClass::create($validated);

        return redirect()->route('school-classes.index')
            ->with('success', 'School class created successfully.');
    }

    /**
     * Display the specified school class.
     */
    public function show(SchoolClass $schoolClass): Response
    {
        Gate::authorize('view', $schoolClass);

        $schoolClass->load(['school', 'students', 'feeStructures']);

        return Inertia::render('SchoolClasses/Show', [
            'schoolClass' => $schoolClass,
        ]);
    }

    /**
     * Show the form for editing the specified school class.
     */
    public function edit(SchoolClass $schoolClass): Response
    {
        Gate::authorize('update', $schoolClass);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schools = $user->hasRole('Super Admin') 
            ? School::where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : collect([$schoolClass->school]);

        return Inertia::render('SchoolClasses/Edit', [
            'schoolClass' => $schoolClass,
            'schools' => $schools,
        ]);
    }

    /**
     * Update the specified school class.
     */
    public function update(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        Gate::authorize('update', $schoolClass);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'section' => 'nullable|string|max:10',
            'capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
        ]);

        // Check for duplicate class name in the same school (excluding current class)
        $exists = SchoolClass::where('school_id', $schoolClass->school_id)
            ->where('name', $validated['name'])
            ->when($validated['section'], function ($query, $section) {
                $query->where('section', $section);
            })
            ->where('id', '!=', $schoolClass->id)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'name' => 'A class with this name' . ($validated['section'] ? ' and section' : '') . ' already exists in this school.'
            ]);
        }

        $schoolClass->update($validated);

        return redirect()->route('school-classes.index')
            ->with('success', 'School class updated successfully.');
    }

    /**
     * Remove the specified school class.
     */
    public function destroy(SchoolClass $schoolClass): RedirectResponse
    {
        Gate::authorize('delete', $schoolClass);

        // Check if class has students
        if ($schoolClass->students()->exists()) {
            return redirect()->route('school-classes.index')
                ->with('error', 'Cannot delete class that has students enrolled.');
        }

        $schoolClass->delete();

        return redirect()->route('school-classes.index')
            ->with('success', 'School class deleted successfully.');
    }
}
