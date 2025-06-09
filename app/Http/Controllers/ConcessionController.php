<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Concession;
use App\Models\Student;
use App\Models\FeeType;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ConcessionController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:concessions.view', only: ['index', 'show']),
            new Middleware('permission:concessions.create', only: ['create', 'store']),
            new Middleware('permission:concessions.edit', only: ['edit', 'update']),
            new Middleware('permission:concessions.delete', only: ['destroy']),
            new Middleware('can:view,concession', only: ['show']),
            new Middleware('can:update,concession', only: ['edit', 'update']),
            new Middleware('can:delete,concession', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of concessions.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Concession::class);
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $concessions = Concession::query()
            ->with(['student', 'feeType', 'academicYear', 'createdBy'])
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->when($request->search, function ($query, $search) {
                $query->whereHas('student', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('admission_no', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Concessions/Index', [
            'concessions' => $concessions,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new concession.
     */
    public function create(): Response
    {
        Gate::authorize('create', Concession::class);
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $students = Student::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no']);

        $feeTypes = FeeType::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'amount']);

        $academicYears = AcademicYear::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name']);

        return Inertia::render('Concessions/Create', [
            'students' => $students,
            'feeTypes' => $feeTypes,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Store a newly created concession.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Concession::class);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $user = Auth::user();
        $validated['school_id'] = $user->school_id;
        $validated['created_by'] = $user->id;

        $concession = Concession::create($validated);

        return redirect()->route('concessions.index')
            ->with('success', 'Concession created successfully.');
    }

    /**
     * Display the specified concession.
     */
    public function show(Concession $concession): Response
    {
        Gate::authorize('view', $concession);

        $concession->load(['student', 'feeType', 'academicYear', 'school', 'createdBy']);

        return Inertia::render('Concessions/Show', [
            'concession' => $concession,
        ]);
    }

    /**
     * Show the form for editing the specified concession.
     */
    public function edit(Concession $concession): Response
    {
        Gate::authorize('update', $concession);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $students = Student::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no']);

        $feeTypes = FeeType::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'amount']);

        $academicYears = AcademicYear::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name']);

        return Inertia::render('Concessions/Edit', [
            'concession' => $concession,
            'students' => $students,
            'feeTypes' => $feeTypes,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Update the specified concession.
     */
    public function update(Request $request, Concession $concession): RedirectResponse
    {
        Gate::authorize('update', $concession);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $concession->update($validated);

        return redirect()->route('concessions.index')
            ->with('success', 'Concession updated successfully.');
    }

    /**
     * Remove the specified concession.
     */
    public function destroy(Concession $concession): RedirectResponse
    {
        Gate::authorize('delete', $concession);

        $concession->delete();

        return redirect()->route('concessions.index')
            ->with('success', 'Concession deleted successfully.');
    }
}
