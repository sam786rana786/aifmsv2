<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\PreviousYearBalance;
use App\Models\Student;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PreviousYearBalanceController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:previous_year_balances.view', only: ['index', 'show', 'export']),
            new Middleware('permission:previous_year_balances.create', only: ['create', 'store', 'import', 'bulkCreate']),
            new Middleware('permission:previous_year_balances.edit', only: ['edit', 'update', 'bulkUpdate']),
            new Middleware('permission:previous_year_balances.delete', only: ['destroy', 'bulkDelete']),
            new Middleware('can:viewAny,previous_year_balance', only: ['index', 'show', 'export']),
            new Middleware('can:create,previous_year_balance', only: ['create', 'store', 'import', 'bulkCreate']),
            new Middleware('can:update,previous_year_balance', only: ['edit', 'update', 'bulkUpdate']),
            new Middleware('can:delete,previous_year_balance', only: ['destroy', 'bulkDelete']),
        ];
    }

    /**
     * Display a listing of previous year balances.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', PreviousYearBalance::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = PreviousYearBalance::query()
            ->with(['student:id,first_name,last_name,admission_no,class', 'academicYear:id,name'])
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('admission_no', 'like', "%{$search}%");
            });
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->get('academic_year_id'));
        }

        if ($request->filled('class')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('class', $request->get('class'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('balance_type')) {
            $balanceType = $request->get('balance_type');
            if ($balanceType === 'positive') {
                $query->where('balance_amount', '>', 0);
            } elseif ($balanceType === 'negative') {
                $query->where('balance_amount', '<', 0);
            } elseif ($balanceType === 'zero') {
                $query->where('balance_amount', '=', 0);
            }
        }

        $balances = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get filter options
        $academicYears = AcademicYear::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name']);

        $classes = Student::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->distinct()
            ->pluck('class')
            ->filter()
            ->sort()
            ->values();

        // Summary statistics
        $summary = [
            'total_balances' => $query->count(),
            'total_amount' => $query->sum('balance_amount'),
            'positive_balances' => $query->where('balance_amount', '>', 0)->count(),
            'negative_balances' => $query->where('balance_amount', '<', 0)->count(),
            'zero_balances' => $query->where('balance_amount', '=', 0)->count(),
            'pending_balances' => $query->where('status', 'pending')->count(),
            'cleared_balances' => $query->where('status', 'cleared')->count(),
        ];

        return Inertia::render('PreviousYearBalances/Index', [
            'balances' => $balances,
            'summary' => $summary,
            'filters' => $request->only([
                'search', 'academic_year_id', 'class', 'status', 'balance_type'
            ]),
            'filterOptions' => [
                'academicYears' => $academicYears,
                'classes' => $classes,
            ],
        ]);
    }

    /**
     * Show the form for creating a new balance.
     */
    public function create(): Response
    {
        Gate::authorize('create', PreviousYearBalance::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Get students without current year balance records
        $students = Student::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no', 'class']);

        $academicYears = AcademicYear::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name']);

        return Inertia::render('PreviousYearBalances/Create', [
            'students' => $students,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Store a newly created balance.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', PreviousYearBalance::class);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'balance_amount' => 'required|numeric',
            'description' => 'nullable|string|max:500',
            'carry_forward_date' => 'required|date',
            'adjustment_reason' => 'nullable|string|max:255',
            'status' => 'required|in:pending,cleared,adjusted',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $validated['school_id'] = $user->school_id;
        $validated['created_by'] = $user->id;

        // Check for duplicate entry
        $exists = PreviousYearBalance::where([
            'student_id' => $validated['student_id'],
            'academic_year_id' => $validated['academic_year_id'],
            'school_id' => $user->school_id,
        ])->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'student_id' => 'Balance already exists for this student and academic year.',
            ]);
        }

        PreviousYearBalance::create($validated);

        return redirect()->route('previous-year-balances.index')
            ->with('success', 'Previous year balance created successfully.');
    }

    /**
     * Display the specified balance.
     */
    public function show(PreviousYearBalance $previousYearBalance): Response
    {
        Gate::authorize('view', $previousYearBalance);

        $previousYearBalance->load([
            'student:id,first_name,last_name,admission_no,class,date_of_birth',
            'academicYear:id,name,start_date,end_date',
            'createdBy:id,name',
            'updatedBy:id,name'
        ]);

        // Get related transactions/adjustments if any
        $relatedTransactions = collect([]); // Would be populated with actual transaction data

        return Inertia::render('PreviousYearBalances/Show', [
            'balance' => $previousYearBalance,
            'relatedTransactions' => $relatedTransactions,
        ]);
    }

    /**
     * Show the form for editing the balance.
     */
    public function edit(PreviousYearBalance $previousYearBalance): Response
    {
        Gate::authorize('update', $previousYearBalance);

        $previousYearBalance->load(['student:id,first_name,last_name,admission_no', 'academicYear:id,name']);

        $academicYears = AcademicYear::when($previousYearBalance->school_id, function ($q) use ($previousYearBalance) {
                $q->where('school_id', $previousYearBalance->school_id);
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name']);

        return Inertia::render('PreviousYearBalances/Edit', [
            'balance' => $previousYearBalance,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Update the specified balance.
     */
    public function update(Request $request, PreviousYearBalance $previousYearBalance): RedirectResponse
    {
        Gate::authorize('update', $previousYearBalance);

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'balance_amount' => 'required|numeric',
            'description' => 'nullable|string|max:500',
            'carry_forward_date' => 'required|date',
            'adjustment_reason' => 'nullable|string|max:255',
            'status' => 'required|in:pending,cleared,adjusted',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $validated['updated_by'] = $user->id;

        $previousYearBalance->update($validated);

        return redirect()->route('previous-year-balances.index')
            ->with('success', 'Previous year balance updated successfully.');
    }

    /**
     * Remove the specified balance.
     */
    public function destroy(PreviousYearBalance $previousYearBalance): RedirectResponse
    {
        Gate::authorize('delete', $previousYearBalance);

        $previousYearBalance->delete();

        return redirect()->route('previous-year-balances.index')
            ->with('success', 'Previous year balance deleted successfully.');
    }

    /**
     * Bulk create balances.
     */
    public function bulkCreate(Request $request): RedirectResponse
    {
        Gate::authorize('create', PreviousYearBalance::class);

        $validated = $request->validate([
            'balances' => 'required|array|min:1',
            'balances.*.student_id' => 'required|exists:students,id',
            'balances.*.academic_year_id' => 'required|exists:academic_years,id',
            'balances.*.balance_amount' => 'required|numeric',
            'balances.*.description' => 'nullable|string|max:500',
            'balances.*.carry_forward_date' => 'required|date',
            'balances.*.adjustment_reason' => 'nullable|string|max:255',
            'balances.*.status' => 'required|in:pending,cleared,adjusted',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($validated, $user, &$created, &$skipped) {
            foreach ($validated['balances'] as $balanceData) {
                // Check for duplicate
                $exists = PreviousYearBalance::where([
                    'student_id' => $balanceData['student_id'],
                    'academic_year_id' => $balanceData['academic_year_id'],
                    'school_id' => $user->school_id,
                ])->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $balanceData['school_id'] = $user->school_id;
                $balanceData['created_by'] = $user->id;

                PreviousYearBalance::create($balanceData);
                $created++;
            }
        });

        $message = "Created {$created} balance records.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} duplicates.";
        }

        return redirect()->route('previous-year-balances.index')
            ->with('success', $message);
    }

    /**
     * Bulk update balances.
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        Gate::authorize('update', PreviousYearBalance::class);

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:previous_year_balances,id',
            'update_data' => 'required|array',
            'update_data.status' => 'nullable|in:pending,cleared,adjusted',
            'update_data.adjustment_reason' => 'nullable|string|max:255',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $updateData = array_filter($validated['update_data']);
        $updateData['updated_by'] = $user->id;

        $query = PreviousYearBalance::whereIn('id', $validated['ids']);

        // Apply school filter for non-super admins
        if ($user->school_id && !$user->hasRole('Super Admin')) {
            $query->where('school_id', $user->school_id);
        }

        $updatedCount = $query->update($updateData);

        return redirect()->route('previous-year-balances.index')
            ->with('success', "Successfully updated {$updatedCount} balance records.");
    }

    /**
     * Bulk delete balances.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('delete', PreviousYearBalance::class);

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:previous_year_balances,id',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = PreviousYearBalance::whereIn('id', $validated['ids']);

        // Apply school filter for non-super admins
        if ($user->school_id && !$user->hasRole('Super Admin')) {
            $query->where('school_id', $user->school_id);
        }

        $deletedCount = $query->count();
        $query->delete();

        return redirect()->route('previous-year-balances.index')
            ->with('success', "Successfully deleted {$deletedCount} balance records.");
    }

    /**
     * Import balances from CSV/Excel.
     */
    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', PreviousYearBalance::class);

        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:2048',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // This would require a proper CSV/Excel import service
        // For now, return a placeholder response
        
        return redirect()->route('previous-year-balances.index')
            ->with('info', 'Import functionality will be implemented with proper CSV/Excel processing.');
    }

    /**
     * Export balances.
     */
    public function export(Request $request)
    {
        Gate::authorize('viewAny', PreviousYearBalance::class);

        $validated = $request->validate([
            'format' => 'required|in:csv,excel,pdf',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'class' => 'nullable|string',
            'status' => 'nullable|in:pending,cleared,adjusted',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = PreviousYearBalance::with(['student:id,first_name,last_name,admission_no,class', 'academicYear:id,name'])
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });

        // Apply filters
        if (!empty($validated['academic_year_id'])) {
            $query->where('academic_year_id', $validated['academic_year_id']);
        }

        if (!empty($validated['class'])) {
            $query->whereHas('student', function ($q) use ($validated) {
                $q->where('class', $validated['class']);
            });
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $balances = $query->orderBy('created_at', 'desc')->get();

        $fileName = 'previous_year_balances_' . now()->format('Y-m-d_H-i-s');

        switch ($validated['format']) {
            case 'csv':
                return $this->exportToCsv($balances, $fileName);
            case 'excel':
                return $this->exportToExcel($balances, $fileName);
            case 'pdf':
                return $this->exportToPdf($balances, $fileName);
        }
    }

    /**
     * Carry forward balances to new academic year.
     */
    public function carryForward(Request $request): RedirectResponse
    {
        Gate::authorize('create', PreviousYearBalance::class);

        $validated = $request->validate([
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_academic_year_id' => 'required|exists:academic_years,id|different:from_academic_year_id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
            'balance_threshold' => 'nullable|numeric',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // This would implement the logic to carry forward balances
        // For now, return a placeholder response

        return redirect()->route('previous-year-balances.index')
            ->with('info', 'Carry forward functionality will be implemented based on business requirements.');
    }

    /**
     * Generate balance reconciliation report.
     */
    public function reconciliation(Request $request)
    {
        Gate::authorize('viewAny', PreviousYearBalance::class);

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // This would implement reconciliation logic
        // For now, return basic data

        $reconciliationData = [
            'total_students' => 0,
            'students_with_balances' => 0,
            'total_balance_amount' => 0,
            'discrepancies' => [],
        ];

        return response()->json([
            'reconciliation' => $reconciliationData,
            'academic_year_id' => $validated['academic_year_id'],
        ]);
    }

    /**
     * Export to CSV.
     */
    private function exportToCsv($balances, $fileName)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '.csv"',
        ];

        $callback = function () use ($balances) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Student Name', 'Admission No', 'Class', 'Academic Year', 
                'Balance Amount', 'Status', 'Description', 'Carry Forward Date'
            ]);

            foreach ($balances as $balance) {
                fputcsv($file, [
                    $balance->student->first_name . ' ' . $balance->student->last_name,
                    $balance->student->admission_no,
                    $balance->student->class,
                    $balance->academicYear->name,
                    $balance->balance_amount,
                    $balance->status,
                    $balance->description,
                    $balance->carry_forward_date->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to Excel (placeholder).
     */
    private function exportToExcel($balances, $fileName)
    {
        return $this->exportToCsv($balances, $fileName);
    }

    /**
     * Export to PDF (placeholder).
     */
    private function exportToPdf($balances, $fileName)
    {
        return response()->json($balances);
    }
}
