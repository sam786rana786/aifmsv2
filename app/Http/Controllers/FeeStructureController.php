<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\FeeType;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\FeeStructure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use App\Services\FeeCalculationService;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class FeeStructureController extends Controller  implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:view fee structures', only: ['index', 'show']),
            new Middleware('permission:create fee structures', only: ['create', 'store']),
            new Middleware('permission:edit fee structures', only: ['edit', 'update']), 
            new Middleware('permission:delete fee structures', only: ['destroy']),
            new Middleware('can:view,feeStructure', only: ['show']),
            new Middleware('can:update,feeStructure', only: ['edit', 'update']),
            new Middleware('can:delete,feeStructure', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of fee structures.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', FeeStructure::class);

        $query = FeeStructure::with(['academicYear', 'feeType', 'schoolClass'])
            ->where('school_id', Auth::user()->school_id);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('feeType', function ($feeTypeQuery) use ($search) {
                    $feeTypeQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('schoolClass', function ($classQuery) use ($search) {
                    $classQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('academicYear', function ($yearQuery) use ($search) {
                    $yearQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Filter by academic year
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->get('academic_year_id'));
        }

        // Filter by fee type
        if ($request->filled('fee_type_id')) {
            $query->where('fee_type_id', $request->get('fee_type_id'));
        }

        // Filter by class
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->get('class_id'));
        }

        $feeStructures = $query->orderBy('created_at', 'desc')->paginate(15);

        $academicYears = AcademicYear::where('school_id', Auth::user()->school_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $feeTypes = FeeType::where('school_id', Auth::user()->school_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $schoolClasses = SchoolClass::where('school_id', Auth::user()->school_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('FeeStructures/Index', [
            'feeStructures' => $feeStructures,
            'academicYears' => $academicYears,
            'feeTypes' => $feeTypes,
            'schoolClasses' => $schoolClasses,
            'filters' => $request->only(['search', 'academic_year_id', 'fee_type_id', 'class_id']),
        ]);
    }

    /**
     * Show the form for creating a new fee structure.
     */
    public function create()
    {
        $this->authorize('create', FeeStructure::class);

        $academicYears = AcademicYear::where('school_id', Auth::user()->school_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $feeTypes = FeeType::where('school_id', Auth::user()->school_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $schoolClasses = SchoolClass::where('school_id', Auth::user()->school_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('FeeStructures/Create', [
            'academicYears' => $academicYears,
            'feeTypes' => $feeTypes,
            'schoolClasses' => $schoolClasses,
        ]);
    }

    /**
     * Store a newly created fee structure.
     */
    public function store(Request $request)
    {
        $this->authorize('create', FeeStructure::class);

        $validated = $request->validate([
            'academic_year_id' => [
                'required',
                'exists:academic_years,id',
                function ($attribute, $value, $fail) {
                    $academicYear = AcademicYear::find($value);
                    if (!$academicYear || $academicYear->school_id !== Auth::user()->school_id) {
                        $fail('The selected academic year is invalid.');
                    }
                },
            ],
            'fee_type_id' => [
                'required',
                'exists:fee_types,id',
                function ($attribute, $value, $fail) {
                    $feeType = FeeType::find($value);
                    if (!$feeType || $feeType->school_id !== Auth::user()->school_id) {
                        $fail('The selected fee type is invalid.');
                    }
                },
            ],
            'class_id' => [
                'required',
                'exists:school_classes,id',
                function ($attribute, $value, $fail) {
                    $schoolClass = SchoolClass::find($value);
                    if (!$schoolClass || $schoolClass->school_id !== Auth::user()->school_id) {
                        $fail('The selected class is invalid.');
                    }
                },
            ],
            'amount' => 'required|numeric|min:0|max:999999.99',
            'late_fee_amount' => 'nullable|numeric|min:0|max:999999.99',
            'due_date' => 'required|date|after_or_equal:today',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        // Check for duplicate fee structure
        $existingStructure = FeeStructure::where('school_id', Auth::user()->school_id)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('fee_type_id', $validated['fee_type_id'])
            ->where('class_id', $validated['class_id'])
            ->first();

        if ($existingStructure) {
            return back()->withErrors([
                'fee_type_id' => 'A fee structure for this combination already exists.'
            ])->withInput();
        }

        $validated['school_id'] = Auth::user()->school_id;
        $validated['is_active'] = $request->boolean('is_active', true);

        FeeStructure::create($validated);

        return redirect()->route('fee-structures.index')
            ->with('success', 'Fee structure created successfully.');
    }

    /**
     * Display the specified fee structure.
     */
    public function show(FeeStructure $feeStructure)
    {
        $this->authorize('view', $feeStructure);

        $feeStructure->load(['academicYear', 'feeType', 'schoolClass', 'school']);

        return Inertia::render('FeeStructures/Show', [
            'feeStructure' => $feeStructure,
        ]);
    }

    /**
     * Show the form for editing the specified fee structure.
     */
    public function edit(FeeStructure $feeStructure)
    {
        $this->authorize('update', $feeStructure);

        $feeStructure->load(['academicYear', 'feeType', 'schoolClass']);

        $academicYears = AcademicYear::where('school_id', Auth::user()->school_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $feeTypes = FeeType::where('school_id', Auth::user()->school_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $schoolClasses = SchoolClass::where('school_id', Auth::user()->school_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('FeeStructures/Edit', [
            'feeStructure' => $feeStructure,
            'academicYears' => $academicYears,
            'feeTypes' => $feeTypes,
            'schoolClasses' => $schoolClasses,
        ]);
    }

    /**
     * Update the specified fee structure.
     */
    public function update(Request $request, FeeStructure $feeStructure)
    {
        $this->authorize('update', $feeStructure);

        $validated = $request->validate([
            'academic_year_id' => [
                'required',
                'exists:academic_years,id',
                function ($attribute, $value, $fail) {
                    $academicYear = AcademicYear::find($value);
                    if (!$academicYear || $academicYear->school_id !== Auth::user()->school_id) {
                        $fail('The selected academic year is invalid.');
                    }
                },
            ],
            'fee_type_id' => [
                'required',
                'exists:fee_types,id',
                function ($attribute, $value, $fail) {
                    $feeType = FeeType::find($value);
                    if (!$feeType || $feeType->school_id !== Auth::user()->school_id) {
                        $fail('The selected fee type is invalid.');
                    }
                },
            ],
            'class_id' => [
                'required',
                'exists:school_classes,id',
                function ($attribute, $value, $fail) {
                    $schoolClass = SchoolClass::find($value);
                    if (!$schoolClass || $schoolClass->school_id !== Auth::user()->school_id) {
                        $fail('The selected class is invalid.');
                    }
                },
            ],
            'amount' => 'required|numeric|min:0|max:999999.99',
            'late_fee_amount' => 'nullable|numeric|min:0|max:999999.99',
            'due_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        // Check for duplicate fee structure (excluding current record)
        $existingStructure = FeeStructure::where('school_id', Auth::user()->school_id)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('fee_type_id', $validated['fee_type_id'])
            ->where('class_id', $validated['class_id'])
            ->where('id', '!=', $feeStructure->id)
            ->first();

        if ($existingStructure) {
            return back()->withErrors([
                'fee_type_id' => 'A fee structure for this combination already exists.'
            ])->withInput();
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $feeStructure->update($validated);

        return redirect()->route('fee-structures.index')
            ->with('success', 'Fee structure updated successfully.');
    }

    /**
     * Remove the specified fee structure.
     */
    public function destroy(FeeStructure $feeStructure)
    {
        $this->authorize('delete', $feeStructure);

        // Check if fee structure has associated payments
        if ($feeStructure->payments()->exists()) {
            return back()->withErrors([
                'error' => 'Cannot delete fee structure that has associated payments.'
            ]);
        }

        $feeStructure->delete();

        return redirect()->route('fee-structures.index')
            ->with('success', 'Fee structure deleted successfully.');
    }

    /**
     * Toggle the active status of a fee structure.
     */
    public function toggleActive(FeeStructure $feeStructure)
    {
        $this->authorize('update', $feeStructure);

        $feeStructure->update([
            'is_active' => !$feeStructure->is_active
        ]);

        $status = $feeStructure->is_active ? 'activated' : 'deactivated';

        return redirect()->route('fee-structures.index')
            ->with('success', "Fee structure {$status} successfully.");
    }
}
