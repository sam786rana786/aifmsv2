<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Student;
use App\Models\TransportRoute;
use App\Models\TransportAssignment;
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

class TransportAssignmentController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:transport_assignments.view', only: ['index', 'show', 'routeStudents', 'statistics']),
            new Middleware('permission:transport_assignments.create', only: ['create', 'store', 'bulkAssign']),
            new Middleware('permission:transport_assignments.edit', only: ['edit', 'update', 'transfer', 'bulkUpdate']),
            new Middleware('permission:transport_assignments.delete', only: ['destroy', 'bulkDelete']),
            new Middleware('can:viewAny,transport_assignment', only: ['index', 'show', 'routeStudents', 'statistics']),
            new Middleware('can:create,transport_assignment', only: ['create', 'store', 'bulkAssign']),
            new Middleware('can:update,transport_assignment', only: ['edit', 'update', 'transfer', 'bulkUpdate']),
            new Middleware('can:delete,transport_assignment', only: ['destroy', 'bulkDelete']),
        ];
    }

    /**
     * Display a listing of transport assignments.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', TransportAssignment::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = TransportAssignment::query()
            ->with([
                'student:id,first_name,last_name,admission_no,class',
                'transportRoute:id,route_name,route_code,fare_amount',
                'academicYear:id,name'
            ])
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('admission_no', 'like', "%{$search}%");
                })->orWhereHas('transportRoute', function ($q) use ($search) {
                    $q->where('route_name', 'like', "%{$search}%")
                      ->orWhere('route_code', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('transport_route_id')) {
            $query->where('transport_route_id', $request->get('transport_route_id'));
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

        if ($request->filled('pickup_point')) {
            $query->where('pickup_point', 'like', '%' . $request->get('pickup_point') . '%');
        }

        $assignments = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get filter options
        $transportRoutes = TransportRoute::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('route_name')
            ->get(['id', 'route_name', 'route_code']);

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
            'total_assignments' => $query->count(),
            'active_assignments' => $query->where('status', 'active')->count(),
            'inactive_assignments' => $query->where('status', 'inactive')->count(),
            'suspended_assignments' => $query->where('status', 'suspended')->count(),
            'total_monthly_revenue' => $query->where('status', 'active')->sum('monthly_fee'),
        ];

        return Inertia::render('TransportAssignments/Index', [
            'assignments' => $assignments,
            'summary' => $summary,
            'filters' => $request->only([
                'search', 'transport_route_id', 'academic_year_id', 'class', 'status', 'pickup_point'
            ]),
            'filterOptions' => [
                'transportRoutes' => $transportRoutes,
                'academicYears' => $academicYears,
                'classes' => $classes,
            ],
        ]);
    }

    /**
     * Show the form for creating a new assignment.
     */
    public function create(): Response
    {
        Gate::authorize('create', TransportAssignment::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Get students who don't have active transport assignments
        $students = Student::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->whereDoesntHave('transportAssignments', function ($q) {
                $q->where('status', 'active');
            })
            ->orderBy('class')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no', 'class']);

        $transportRoutes = TransportRoute::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->with('stops:id,transport_route_id,stop_name,pickup_time,drop_time')
            ->orderBy('route_name')
            ->get();

        $academicYears = AcademicYear::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name']);

        return Inertia::render('TransportAssignments/Create', [
            'students' => $students,
            'transportRoutes' => $transportRoutes,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Store a newly created assignment.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', TransportAssignment::class);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'transport_route_id' => 'required|exists:transport_routes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'pickup_point' => 'required|string|max:255',
            'drop_point' => 'nullable|string|max:255',
            'monthly_fee' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'remarks' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $validated['school_id'] = $user->school_id;
        $validated['assigned_by'] = $user->id;
        $validated['status'] = 'active';

        // Check for existing active assignment
        $existingAssignment = TransportAssignment::where([
            'student_id' => $validated['student_id'],
            'academic_year_id' => $validated['academic_year_id'],
            'status' => 'active',
            'school_id' => $user->school_id,
        ])->first();

        if ($existingAssignment) {
            throw ValidationException::withMessages([
                'student_id' => 'Student already has an active transport assignment for this academic year.',
            ]);
        }

        TransportAssignment::create($validated);

        return redirect()->route('transport-assignments.index')
            ->with('success', 'Transport assignment created successfully.');
    }

    /**
     * Display the specified assignment.
     */
    public function show(TransportAssignment $transportAssignment): Response
    {
        Gate::authorize('view', $transportAssignment);

        $transportAssignment->load([
            'student:id,first_name,last_name,admission_no,class,phone,parent_phone',
            'transportRoute:id,route_name,route_code,vehicle_number,driver_name,driver_phone',
            'academicYear:id,name,start_date,end_date',
            'assignedBy:id,name',
            'transportRoute.stops'
        ]);

        return Inertia::render('TransportAssignments/Show', [
            'assignment' => $transportAssignment,
        ]);
    }

    /**
     * Show the form for editing the assignment.
     */
    public function edit(TransportAssignment $transportAssignment): Response
    {
        Gate::authorize('update', $transportAssignment);

        $transportAssignment->load(['student:id,first_name,last_name,admission_no']);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $transportRoutes = TransportRoute::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->with('stops:id,transport_route_id,stop_name,pickup_time,drop_time')
            ->orderBy('route_name')
            ->get();

        $academicYears = AcademicYear::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name']);

        return Inertia::render('TransportAssignments/Edit', [
            'assignment' => $transportAssignment,
            'transportRoutes' => $transportRoutes,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Update the specified assignment.
     */
    public function update(Request $request, TransportAssignment $transportAssignment): RedirectResponse
    {
        Gate::authorize('update', $transportAssignment);

        $validated = $request->validate([
            'transport_route_id' => 'required|exists:transport_routes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'pickup_point' => 'required|string|max:255',
            'drop_point' => 'nullable|string|max:255',
            'monthly_fee' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:active,inactive,suspended',
            'remarks' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        $transportAssignment->update($validated);

        return redirect()->route('transport-assignments.index')
            ->with('success', 'Transport assignment updated successfully.');
    }

    /**
     * Remove the specified assignment.
     */
    public function destroy(TransportAssignment $transportAssignment): RedirectResponse
    {
        Gate::authorize('delete', $transportAssignment);

        $transportAssignment->delete();

        return redirect()->route('transport-assignments.index')
            ->with('success', 'Transport assignment deleted successfully.');
    }

    /**
     * Bulk assign students to transport routes.
     */
    public function bulkAssign(Request $request): RedirectResponse
    {
        Gate::authorize('create', TransportAssignment::class);

        $validated = $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.student_id' => 'required|exists:students,id',
            'assignments.*.transport_route_id' => 'required|exists:transport_routes,id',
            'assignments.*.academic_year_id' => 'required|exists:academic_years,id',
            'assignments.*.pickup_point' => 'required|string|max:255',
            'assignments.*.drop_point' => 'nullable|string|max:255',
            'assignments.*.monthly_fee' => 'required|numeric|min:0',
            'assignments.*.start_date' => 'required|date',
            'assignments.*.end_date' => 'nullable|date',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($validated, $user, &$created, &$skipped) {
            foreach ($validated['assignments'] as $assignmentData) {
                // Check for existing active assignment
                $exists = TransportAssignment::where([
                    'student_id' => $assignmentData['student_id'],
                    'academic_year_id' => $assignmentData['academic_year_id'],
                    'status' => 'active',
                    'school_id' => $user->school_id,
                ])->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $assignmentData['school_id'] = $user->school_id;
                $assignmentData['assigned_by'] = $user->id;
                $assignmentData['status'] = 'active';

                TransportAssignment::create($assignmentData);
                $created++;
            }
        });

        $message = "Created {$created} transport assignments.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} duplicates.";
        }

        return redirect()->route('transport-assignments.index')
            ->with('success', $message);
    }

    /**
     * Bulk update assignments.
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        Gate::authorize('update', TransportAssignment::class);

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:transport_assignments,id',
            'update_data' => 'required|array',
            'update_data.status' => 'nullable|in:active,inactive,suspended',
            'update_data.monthly_fee' => 'nullable|numeric|min:0',
            'update_data.transport_route_id' => 'nullable|exists:transport_routes,id',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $updateData = array_filter($validated['update_data']);

        $query = TransportAssignment::whereIn('id', $validated['ids']);

        // Apply school filter for non-super admins
        if ($user->school_id && !$user->hasRole('Super Admin')) {
            $query->where('school_id', $user->school_id);
        }

        $updatedCount = $query->update($updateData);

        return redirect()->route('transport-assignments.index')
            ->with('success', "Successfully updated {$updatedCount} transport assignments.");
    }

    /**
     * Bulk delete assignments.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('delete', TransportAssignment::class);

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:transport_assignments,id',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = TransportAssignment::whereIn('id', $validated['ids']);

        // Apply school filter for non-super admins
        if ($user->school_id && !$user->hasRole('Super Admin')) {
            $query->where('school_id', $user->school_id);
        }

        $deletedCount = $query->count();
        $query->delete();

        return redirect()->route('transport-assignments.index')
            ->with('success', "Successfully deleted {$deletedCount} transport assignments.");
    }

    /**
     * Transfer student to a different route.
     */
    public function transfer(Request $request, TransportAssignment $transportAssignment): RedirectResponse
    {
        Gate::authorize('update', $transportAssignment);

        $validated = $request->validate([
            'new_transport_route_id' => 'required|exists:transport_routes,id|different:transport_route_id',
            'new_pickup_point' => 'required|string|max:255',
            'new_drop_point' => 'nullable|string|max:255',
            'new_monthly_fee' => 'required|numeric|min:0',
            'transfer_date' => 'required|date',
            'transfer_reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($transportAssignment, $validated) {
            // Create a history record of the transfer
            $transferHistory = [
                'student_id' => $transportAssignment->student_id,
                'from_route_id' => $transportAssignment->transport_route_id,
                'to_route_id' => $validated['new_transport_route_id'],
                'from_pickup_point' => $transportAssignment->pickup_point,
                'to_pickup_point' => $validated['new_pickup_point'],
                'from_monthly_fee' => $transportAssignment->monthly_fee,
                'to_monthly_fee' => $validated['new_monthly_fee'],
                'transfer_date' => $validated['transfer_date'],
                'transfer_reason' => $validated['transfer_reason'],
                'transferred_by' => Auth::id(),
            ];

            // Update the assignment
            $transportAssignment->update([
                'transport_route_id' => $validated['new_transport_route_id'],
                'pickup_point' => $validated['new_pickup_point'],
                'drop_point' => $validated['new_drop_point'],
                'monthly_fee' => $validated['new_monthly_fee'],
                'remarks' => ($transportAssignment->remarks ? $transportAssignment->remarks . "\n" : '') 
                    . "Transferred on {$validated['transfer_date']}: {$validated['transfer_reason']}",
            ]);

            // Store transfer history (would require a separate model)
            // TransportTransferHistory::create($transferHistory);
        });

        return redirect()->route('transport-assignments.show', $transportAssignment)
            ->with('success', 'Student successfully transferred to new route.');
    }

    /**
     * Get students assigned to a specific route.
     */
    public function routeStudents(Request $request, TransportRoute $transportRoute)
    {
        Gate::authorize('viewAny', TransportAssignment::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $assignments = TransportAssignment::where('transport_route_id', $transportRoute->id)
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->with(['student:id,first_name,last_name,admission_no,class,phone'])
            ->where('status', 'active')
            ->orderBy('pickup_point')
            ->get();

        $summary = [
            'total_students' => $assignments->count(),
            'total_monthly_revenue' => $assignments->sum('monthly_fee'),
            'pickup_points' => $assignments->pluck('pickup_point')->unique()->sort()->values(),
        ];

        return response()->json([
            'assignments' => $assignments,
            'summary' => $summary,
            'route' => $transportRoute->load('stops'),
        ]);
    }

    /**
     * Get transport assignment statistics.
     */
    public function statistics(Request $request)
    {
        Gate::authorize('viewAny', TransportAssignment::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $academicYearId = $request->get('academic_year_id');

        $query = TransportAssignment::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $stats = [
            'total_assignments' => $query->count(),
            'active_assignments' => (clone $query)->where('status', 'active')->count(),
            'inactive_assignments' => (clone $query)->where('status', 'inactive')->count(),
            'suspended_assignments' => (clone $query)->where('status', 'suspended')->count(),
            'total_monthly_revenue' => (clone $query)->where('status', 'active')->sum('monthly_fee'),
            'average_monthly_fee' => (clone $query)->where('status', 'active')->avg('monthly_fee'),
        ];

        // Assignments by route
        $assignmentsByRoute = (clone $query)
            ->where('status', 'active')
            ->join('transport_routes', 'transport_assignments.transport_route_id', '=', 'transport_routes.id')
            ->groupBy('transport_routes.id', 'transport_routes.route_name')
            ->selectRaw('
                transport_routes.id,
                transport_routes.route_name,
                COUNT(*) as student_count,
                SUM(transport_assignments.monthly_fee) as monthly_revenue
            ')
            ->orderBy('student_count', 'desc')
            ->get();

        // Monthly trends
        $monthlyTrends = (clone $query)
            ->where('status', 'active')
            ->groupBy(DB::raw('YEAR(start_date), MONTH(start_date)'))
            ->selectRaw('
                YEAR(start_date) as year,
                MONTH(start_date) as month,
                COUNT(*) as new_assignments,
                SUM(monthly_fee) as revenue
            ')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Class-wise distribution
        $classwiseDistribution = (clone $query)
            ->where('status', 'active')
            ->join('students', 'transport_assignments.student_id', '=', 'students.id')
            ->groupBy('students.class')
            ->selectRaw('students.class, COUNT(*) as count')
            ->orderBy('students.class')
            ->get();

        return response()->json([
            'stats' => $stats,
            'assignmentsByRoute' => $assignmentsByRoute,
            'monthlyTrends' => $monthlyTrends,
            'classwiseDistribution' => $classwiseDistribution,
            'academic_year_id' => $academicYearId,
        ]);
    }

    /**
     * Get available pickup points for a route.
     */
    public function getPickupPoints(TransportRoute $transportRoute)
    {
        $stops = $transportRoute->stops()
            ->orderBy('stop_order')
            ->get(['id', 'stop_name', 'pickup_time', 'drop_time']);

        return response()->json([
            'stops' => $stops,
            'route' => $transportRoute->only(['id', 'route_name', 'fare_amount']),
        ]);
    }

    /**
     * Generate assignment report.
     */
    public function generateReport(Request $request)
    {
        Gate::authorize('viewAny', TransportAssignment::class);

        $validated = $request->validate([
            'format' => 'required|in:csv,excel,pdf',
            'transport_route_id' => 'nullable|exists:transport_routes,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'status' => 'nullable|in:active,inactive,suspended',
            'class' => 'nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = TransportAssignment::with([
                'student:id,first_name,last_name,admission_no,class',
                'transportRoute:id,route_name,route_code',
                'academicYear:id,name'
            ])
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });

        // Apply filters
        if (!empty($validated['transport_route_id'])) {
            $query->where('transport_route_id', $validated['transport_route_id']);
        }

        if (!empty($validated['academic_year_id'])) {
            $query->where('academic_year_id', $validated['academic_year_id']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['class'])) {
            $query->whereHas('student', function ($q) use ($validated) {
                $q->where('class', $validated['class']);
            });
        }

        $assignments = $query->orderBy('created_at', 'desc')->get();

        $fileName = 'transport_assignments_' . now()->format('Y-m-d_H-i-s');

        switch ($validated['format']) {
            case 'csv':
                return $this->exportToCsv($assignments, $fileName);
            case 'excel':
                return $this->exportToExcel($assignments, $fileName);
            case 'pdf':
                return $this->exportToPdf($assignments, $fileName);
        }
    }

    /**
     * Export to CSV.
     */
    private function exportToCsv($assignments, $fileName)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '.csv"',
        ];

        $callback = function () use ($assignments) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Student Name', 'Admission No', 'Class', 'Route Name', 'Route Code',
                'Pickup Point', 'Drop Point', 'Monthly Fee', 'Status', 'Start Date', 'End Date'
            ]);

            foreach ($assignments as $assignment) {
                fputcsv($file, [
                    $assignment->student->first_name . ' ' . $assignment->student->last_name,
                    $assignment->student->admission_no,
                    $assignment->student->class,
                    $assignment->transportRoute->route_name,
                    $assignment->transportRoute->route_code,
                    $assignment->pickup_point,
                    $assignment->drop_point,
                    $assignment->monthly_fee,
                    $assignment->status,
                    $assignment->start_date->format('Y-m-d'),
                    $assignment->end_date?->format('Y-m-d') ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to Excel (placeholder).
     */
    private function exportToExcel($assignments, $fileName)
    {
        return $this->exportToCsv($assignments, $fileName);
    }

    /**
     * Export to PDF (placeholder).
     */
    private function exportToPdf($assignments, $fileName)
    {
        return response()->json($assignments);
    }
}
