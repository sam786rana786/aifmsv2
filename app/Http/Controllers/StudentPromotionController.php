<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Student;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use App\Models\StudentPromotion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StudentPromotionController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:student_promotions.view', only: ['index', 'show', 'preview']),
            new Middleware('permission:student_promotions.create', only: ['create', 'store', 'bulkPromote', 'process']),
            new Middleware('permission:student_promotions.edit', only: ['edit', 'update', 'rollback']),
            new Middleware('permission:student_promotions.delete', only: ['destroy']),
            new Middleware('can:viewAny,student_promotion', only: ['index', 'show', 'preview']),
            new Middleware('can:create,student_promotion', only: ['create', 'store', 'bulkPromote', 'process']),
            new Middleware('can:update,student_promotion', only: ['edit', 'update', 'rollback']),
            new Middleware('can:delete,student_promotion', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of student promotions.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', StudentPromotion::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = StudentPromotion::query()
            ->with([
                'student:id,first_name,last_name,admission_no',
                'fromAcademicYear:id,name',
                'toAcademicYear:id,name',
                'processedBy:id,name'
            ])
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
            $academicYearId = $request->get('academic_year_id');
            $query->where(function ($q) use ($academicYearId) {
                $q->where('from_academic_year_id', $academicYearId)
                  ->orWhere('to_academic_year_id', $academicYearId);
            });
        }

        if ($request->filled('from_class')) {
            $query->where('from_class', $request->get('from_class'));
        }

        if ($request->filled('to_class')) {
            $query->where('to_class', $request->get('to_class'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('promotion_type')) {
            $query->where('promotion_type', $request->get('promotion_type'));
        }

        $promotions = $query->orderBy('created_at', 'desc')
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
            'total_promotions' => $query->count(),
            'pending_promotions' => $query->where('status', 'pending')->count(),
            'completed_promotions' => $query->where('status', 'completed')->count(),
            'failed_promotions' => $query->where('status', 'failed')->count(),
            'automatic_promotions' => $query->where('promotion_type', 'automatic')->count(),
            'manual_promotions' => $query->where('promotion_type', 'manual')->count(),
        ];

        return Inertia::render('StudentPromotions/Index', [
            'promotions' => $promotions,
            'summary' => $summary,
            'filters' => $request->only([
                'search', 'academic_year_id', 'from_class', 'to_class', 'status', 'promotion_type'
            ]),
            'filterOptions' => [
                'academicYears' => $academicYears,
                'classes' => $classes,
            ],
        ]);
    }

    /**
     * Show the form for creating promotions.
     */
    public function create(): Response
    {
        Gate::authorize('create', StudentPromotion::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $academicYears = AcademicYear::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name', 'start_date', 'end_date']);

        $students = Student::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('class')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no', 'class']);

        $classes = Student::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->distinct()
            ->pluck('class')
            ->filter()
            ->sort()
            ->values();

        return Inertia::render('StudentPromotions/Create', [
            'academicYears' => $academicYears,
            'students' => $students,
            'classes' => $classes,
        ]);
    }

    /**
     * Store a new promotion record.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', StudentPromotion::class);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_academic_year_id' => 'required|exists:academic_years,id|different:from_academic_year_id',
            'from_class' => 'required|string|max:50',
            'to_class' => 'required|string|max:50|different:from_class',
            'promotion_type' => 'required|in:automatic,manual,special',
            'promotion_criteria' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:1000',
            'effective_date' => 'required|date',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $validated['school_id'] = $user->school_id;
        $validated['processed_by'] = $user->id;
        $validated['status'] = 'pending';

        // Check for duplicate promotion
        $exists = StudentPromotion::where([
            'student_id' => $validated['student_id'],
            'from_academic_year_id' => $validated['from_academic_year_id'],
            'to_academic_year_id' => $validated['to_academic_year_id'],
            'school_id' => $user->school_id,
        ])->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'student_id' => 'Promotion record already exists for this student and academic year transition.',
            ]);
        }

        $promotion = StudentPromotion::create($validated);

        // Process the promotion if requested
        if ($request->boolean('process_immediately')) {
            $this->processPromotion($promotion);
        }

        return redirect()->route('student-promotions.index')
            ->with('success', 'Student promotion record created successfully.');
    }

    /**
     * Display the specified promotion.
     */
    public function show(StudentPromotion $studentPromotion): Response
    {
        Gate::authorize('view', $studentPromotion);

        $studentPromotion->load([
            'student:id,first_name,last_name,admission_no,class,date_of_birth',
            'fromAcademicYear:id,name,start_date,end_date',
            'toAcademicYear:id,name,start_date,end_date',
            'processedBy:id,name,email'
        ]);

        return Inertia::render('StudentPromotions/Show', [
            'promotion' => $studentPromotion,
        ]);
    }

    /**
     * Show the form for editing the promotion.
     */
    public function edit(StudentPromotion $studentPromotion): Response
    {
        Gate::authorize('update', $studentPromotion);

        $studentPromotion->load([
            'student:id,first_name,last_name,admission_no',
            'fromAcademicYear:id,name',
            'toAcademicYear:id,name'
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

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

        return Inertia::render('StudentPromotions/Edit', [
            'promotion' => $studentPromotion,
            'academicYears' => $academicYears,
            'classes' => $classes,
        ]);
    }

    /**
     * Update the specified promotion.
     */
    public function update(Request $request, StudentPromotion $studentPromotion): RedirectResponse
    {
        Gate::authorize('update', $studentPromotion);

        // Only allow editing if promotion is not completed
        if ($studentPromotion->status === 'completed') {
            throw ValidationException::withMessages([
                'status' => 'Cannot edit a completed promotion.',
            ]);
        }

        $validated = $request->validate([
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_academic_year_id' => 'required|exists:academic_years,id|different:from_academic_year_id',
            'from_class' => 'required|string|max:50',
            'to_class' => 'required|string|max:50|different:from_class',
            'promotion_type' => 'required|in:automatic,manual,special',
            'promotion_criteria' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:1000',
            'effective_date' => 'required|date',
        ]);

        $studentPromotion->update($validated);

        return redirect()->route('student-promotions.index')
            ->with('success', 'Student promotion updated successfully.');
    }

    /**
     * Remove the specified promotion.
     */
    public function destroy(StudentPromotion $studentPromotion): RedirectResponse
    {
        Gate::authorize('delete', $studentPromotion);

        // Only allow deletion if promotion is not completed
        if ($studentPromotion->status === 'completed') {
            throw ValidationException::withMessages([
                'status' => 'Cannot delete a completed promotion.',
            ]);
        }

        $studentPromotion->delete();

        return redirect()->route('student-promotions.index')
            ->with('success', 'Student promotion deleted successfully.');
    }

    /**
     * Bulk promotion of students.
     */
    public function bulkPromote(Request $request): RedirectResponse
    {
        Gate::authorize('create', StudentPromotion::class);

        $validated = $request->validate([
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_academic_year_id' => 'required|exists:academic_years,id|different:from_academic_year_id',
            'class_promotions' => 'required|array|min:1',
            'class_promotions.*.from_class' => 'required|string|max:50',
            'class_promotions.*.to_class' => 'required|string|max:50',
            'class_promotions.*.student_ids' => 'required|array|min:1',
            'class_promotions.*.student_ids.*' => 'exists:students,id',
            'promotion_type' => 'required|in:automatic,manual,special',
            'promotion_criteria' => 'nullable|string|max:500',
            'effective_date' => 'required|date',
            'process_immediately' => 'boolean',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $created = 0;
        $skipped = 0;
        $processed = 0;

        DB::transaction(function () use ($validated, $user, &$created, &$skipped, &$processed) {
            foreach ($validated['class_promotions'] as $classPromotion) {
                foreach ($classPromotion['student_ids'] as $studentId) {
                    // Check for duplicate
                    $exists = StudentPromotion::where([
                        'student_id' => $studentId,
                        'from_academic_year_id' => $validated['from_academic_year_id'],
                        'to_academic_year_id' => $validated['to_academic_year_id'],
                        'school_id' => $user->school_id,
                    ])->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    $promotionData = [
                        'student_id' => $studentId,
                        'school_id' => $user->school_id,
                        'from_academic_year_id' => $validated['from_academic_year_id'],
                        'to_academic_year_id' => $validated['to_academic_year_id'],
                        'from_class' => $classPromotion['from_class'],
                        'to_class' => $classPromotion['to_class'],
                        'promotion_type' => $validated['promotion_type'],
                        'promotion_criteria' => $validated['promotion_criteria'],
                        'effective_date' => $validated['effective_date'],
                        'processed_by' => $user->id,
                        'status' => 'pending',
                    ];

                    $promotion = StudentPromotion::create($promotionData);
                    $created++;

                    // Process immediately if requested
                    if ($validated['process_immediately'] ?? false) {
                        if ($this->processPromotion($promotion)) {
                            $processed++;
                        }
                    }
                }
            }
        });

        $message = "Created {$created} promotion records.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} duplicates.";
        }
        if ($processed > 0) {
            $message .= " Processed {$processed} promotions.";
        }

        return redirect()->route('student-promotions.index')
            ->with('success', $message);
    }

    /**
     * Preview bulk promotion.
     */
    public function preview(Request $request)
    {
        Gate::authorize('create', StudentPromotion::class);

        $validated = $request->validate([
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_academic_year_id' => 'required|exists:academic_years,id|different:from_academic_year_id',
            'from_class' => 'required|string|max:50',
            'to_class' => 'required|string|max:50',
            'criteria' => 'nullable|array',
            'criteria.min_attendance' => 'nullable|numeric|min:0|max:100',
            'criteria.min_marks' => 'nullable|numeric|min:0|max:100',
            'criteria.exclude_failed' => 'boolean',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = Student::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->where('class', $validated['from_class'])
            ->where('is_active', true);

        // Apply criteria filters
        if (!empty($validated['criteria']['min_attendance'])) {
            // This would require an attendance system
            // $query->where('attendance_percentage', '>=', $validated['criteria']['min_attendance']);
        }

        if (!empty($validated['criteria']['min_marks'])) {
            // This would require a marks/grades system
            // $query->where('average_marks', '>=', $validated['criteria']['min_marks']);
        }

        if ($validated['criteria']['exclude_failed'] ?? false) {
            // This would require a results system
            // $query->where('result_status', '!=', 'failed');
        }

        $eligibleStudents = $query->get(['id', 'first_name', 'last_name', 'admission_no', 'class']);

        // Check for existing promotions
        $existingPromotions = StudentPromotion::where([
            'from_academic_year_id' => $validated['from_academic_year_id'],
            'to_academic_year_id' => $validated['to_academic_year_id'],
            'school_id' => $user->school_id,
        ])->pluck('student_id')->toArray();

        $eligibleStudents = $eligibleStudents->filter(function ($student) use ($existingPromotions) {
            return !in_array($student->id, $existingPromotions);
        });

        return response()->json([
            'eligible_students' => $eligibleStudents->values(),
            'total_eligible' => $eligibleStudents->count(),
            'existing_promotions' => count($existingPromotions),
            'criteria_applied' => $validated['criteria'] ?? [],
        ]);
    }

    /**
     * Process promotion (update student's class).
     */
    public function process(Request $request, StudentPromotion $studentPromotion): RedirectResponse
    {
        Gate::authorize('update', $studentPromotion);

        if ($studentPromotion->status === 'completed') {
            return redirect()->back()
                ->with('info', 'Promotion has already been processed.');
        }

        $success = $this->processPromotion($studentPromotion);

        if ($success) {
            return redirect()->route('student-promotions.show', $studentPromotion)
                ->with('success', 'Student promotion processed successfully.');
        } else {
            return redirect()->back()
                ->with('error', 'Failed to process student promotion.');
        }
    }

    /**
     * Rollback a completed promotion.
     */
    public function rollback(Request $request, StudentPromotion $studentPromotion): RedirectResponse
    {
        Gate::authorize('update', $studentPromotion);

        if ($studentPromotion->status !== 'completed') {
            throw ValidationException::withMessages([
                'status' => 'Can only rollback completed promotions.',
            ]);
        }

        $validated = $request->validate([
            'rollback_reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($studentPromotion, $validated) {
            // Update student's class back to original
            $student = $studentPromotion->student;
            $student->update(['class' => $studentPromotion->from_class]);

            // Update promotion record
            $studentPromotion->update([
                'status' => 'rolled_back',
                'rollback_reason' => $validated['rollback_reason'],
                'rolled_back_at' => now(),
                'rolled_back_by' => Auth::id(),
            ]);
        });

        return redirect()->route('student-promotions.index')
            ->with('success', 'Student promotion rolled back successfully.');
    }

    /**
     * Get promotion statistics.
     */
    public function statistics(Request $request)
    {
        Gate::authorize('viewAny', StudentPromotion::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $academicYearId = $request->get('academic_year_id');

        $query = StudentPromotion::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });

        if ($academicYearId) {
            $query->where(function ($q) use ($academicYearId) {
                $q->where('from_academic_year_id', $academicYearId)
                  ->orWhere('to_academic_year_id', $academicYearId);
            });
        }

        $stats = [
            'total_promotions' => $query->count(),
            'pending_promotions' => (clone $query)->where('status', 'pending')->count(),
            'completed_promotions' => (clone $query)->where('status', 'completed')->count(),
            'failed_promotions' => (clone $query)->where('status', 'failed')->count(),
            'rolled_back_promotions' => (clone $query)->where('status', 'rolled_back')->count(),
        ];

        // Promotion by class
        $promotionsByClass = (clone $query)
            ->groupBy('from_class', 'to_class')
            ->selectRaw('from_class, to_class, COUNT(*) as count')
            ->orderBy('from_class')
            ->get();

        // Promotion trends (monthly)
        $promotionTrends = (clone $query)
            ->groupBy(DB::raw('YEAR(created_at), MONTH(created_at)'))
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return response()->json([
            'stats' => $stats,
            'promotionsByClass' => $promotionsByClass,
            'promotionTrends' => $promotionTrends,
            'academic_year_id' => $academicYearId,
        ]);
    }

    /**
     * Process a single promotion.
     */
    private function processPromotion(StudentPromotion $promotion): bool
    {
        try {
            DB::transaction(function () use ($promotion) {
                // Update student's class
                $student = $promotion->student;
                $student->update(['class' => $promotion->to_class]);

                // Update promotion status
                $promotion->update([
                    'status' => 'completed',
                    'processed_at' => now(),
                ]);
            });

            return true;
        } catch (\Exception $e) {
            // Log the error
            Log::error('Failed to process promotion: ' . $e->getMessage(), [
                'promotion_id' => $promotion->id,
                'student_id' => $promotion->student_id,
            ]);

            // Update promotion status to failed
            $promotion->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
