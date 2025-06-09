<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Student;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\User;
use App\Models\FeeStructure;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReportsController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:reports.view', only: ['index', 'feeReport', 'studentReport', 'paymentReport', 'financialReport', 'customReport']),
            new Middleware('permission:reports.export', only: ['export', 'download']),
            new Middleware('can:viewAny,reports', only: ['index', 'feeReport', 'studentReport', 'paymentReport', 'financialReport', 'customReport']),
            new Middleware('can:export,reports', only: ['export', 'download']),
        ];
    }

    /**
     * Display reports dashboard.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', 'reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schoolFilter = $user->school_id && !$user->hasRole('Super Admin') 
            ? ['school_id' => $user->school_id] 
            : [];

        // Quick stats for dashboard
        $stats = [
            'total_students' => Student::where($schoolFilter)->where('is_active', true)->count(),
            'total_fees_this_month' => Fee::where($schoolFilter)
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'total_payments_this_month' => Payment::where($schoolFilter)
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'pending_fees' => Fee::where($schoolFilter)
                ->where('status', 'pending')
                ->count(),
        ];

        // Available report types
        $reportTypes = [
            [
                'id' => 'fee_collection',
                'name' => 'Fee Collection Report',
                'description' => 'Detailed fee collection analysis',
                'icon' => 'money',
                'route' => 'reports.fee',
            ],
            [
                'id' => 'student_report',
                'name' => 'Student Report',
                'description' => 'Student enrollment and demographic reports',
                'icon' => 'users',
                'route' => 'reports.student',
            ],
            [
                'id' => 'payment_analysis',
                'name' => 'Payment Analysis',
                'description' => 'Payment trends and gateway analysis',
                'icon' => 'credit-card',
                'route' => 'reports.payment',
            ],
            [
                'id' => 'financial_summary',
                'name' => 'Financial Summary',
                'description' => 'Overall financial performance reports',
                'icon' => 'chart-bar',
                'route' => 'reports.financial',
            ],
            [
                'id' => 'custom_report',
                'name' => 'Custom Reports',
                'description' => 'Build custom reports with filters',
                'icon' => 'cog',
                'route' => 'reports.custom',
            ],
        ];

        return Inertia::render('Reports/Index', [
            'stats' => $stats,
            'reportTypes' => $reportTypes,
        ]);
    }

    /**
     * Fee collection reports.
     */
    public function feeReport(Request $request): Response
    {
        Gate::authorize('viewAny', 'reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schoolFilter = $user->school_id && !$user->hasRole('Super Admin') 
            ? ['school_id' => $user->school_id] 
            : [];

        // Date range filter
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

        // Fee collection summary
        $collectionSummary = [
            'total_fees_generated' => Fee::where($schoolFilter)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'total_collected' => Payment::where($schoolFilter)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'pending_amount' => Fee::where($schoolFilter)
                ->where('status', 'pending')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'overdue_amount' => Fee::where($schoolFilter)
                ->where('status', 'overdue')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
        ];

        // Collection by fee structure
        $collectionByStructure = Fee::where($schoolFilter)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->join('fee_structures', 'fees.fee_structure_id', '=', 'fee_structures.id')
            ->groupBy('fee_structures.id', 'fee_structures.name')
            ->selectRaw('
                fee_structures.id,
                fee_structures.name,
                COUNT(*) as total_fees,
                SUM(fees.amount) as total_amount,
                SUM(CASE WHEN fees.status = "completed" THEN fees.amount ELSE 0 END) as collected_amount,
                SUM(CASE WHEN fees.status = "pending" THEN fees.amount ELSE 0 END) as pending_amount
            ')
            ->get();

        // Class-wise collection
        $classWiseCollection = Fee::where($schoolFilter)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->join('students', 'fees.student_id', '=', 'students.id')
            ->groupBy('students.class')
            ->selectRaw('
                students.class,
                COUNT(*) as total_fees,
                SUM(fees.amount) as total_amount,
                SUM(CASE WHEN fees.status = "completed" THEN fees.amount ELSE 0 END) as collected_amount,
                COUNT(CASE WHEN fees.status = "pending" THEN 1 END) as pending_count
            ')
            ->orderBy('students.class')
            ->get();

        // Daily collection trend
        $dailyTrend = Payment::where($schoolFilter)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as amount, COUNT(*) as count')
            ->orderBy('date')
            ->get();

        // Defaulter list
        $defaulters = Student::where($schoolFilter)
            ->whereHas('fees', function ($query) {
                $query->where('status', 'overdue');
            })
            ->withSum(['fees as overdue_amount' => function ($query) {
                $query->where('status', 'overdue');
            }], 'amount')
            ->orderBy('overdue_amount', 'desc')
            ->limit(50)
            ->get();

        return Inertia::render('Reports/FeeReport', [
            'collectionSummary' => $collectionSummary,
            'collectionByStructure' => $collectionByStructure,
            'classWiseCollection' => $classWiseCollection,
            'dailyTrend' => $dailyTrend,
            'defaulters' => $defaulters,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'fee_structure_id' => $request->get('fee_structure_id'),
                'class' => $request->get('class'),
            ],
            'filterOptions' => [
                'feeStructures' => FeeStructure::where($schoolFilter)->get(['id', 'name']),
                'classes' => Student::where($schoolFilter)->distinct()->pluck('class')->filter()->sort()->values(),
            ],
        ]);
    }

    /**
     * Student reports.
     */
    public function studentReport(Request $request): Response
    {
        Gate::authorize('viewAny', 'reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schoolFilter = $user->school_id && !$user->hasRole('Super Admin') 
            ? ['school_id' => $user->school_id] 
            : [];

        // Student demographics
        $demographics = [
            'total_students' => Student::where($schoolFilter)->count(),
            'active_students' => Student::where($schoolFilter)->where('is_active', true)->count(),
            'inactive_students' => Student::where($schoolFilter)->where('is_active', false)->count(),
            'new_admissions_this_year' => Student::where($schoolFilter)
                ->whereYear('admission_date', now()->year)
                ->count(),
        ];

        // Gender distribution
        $genderDistribution = Student::where($schoolFilter)
            ->where('is_active', true)
            ->groupBy('gender')
            ->selectRaw('gender, COUNT(*) as count')
            ->get();

        // Class-wise enrollment
        $classEnrollment = Student::where($schoolFilter)
            ->where('is_active', true)
            ->groupBy('class')
            ->selectRaw('class, COUNT(*) as count')
            ->orderBy('class')
            ->get();

        // Age distribution
        $ageGroups = Student::where($schoolFilter)
            ->where('is_active', true)
            ->whereNotNull('date_of_birth')
            ->get()
            ->groupBy(function ($student) {
                $age = now()->diffInYears($student->date_of_birth);
                if ($age <= 5) return '5 and below';
                if ($age <= 10) return '6-10';
                if ($age <= 15) return '11-15';
                if ($age <= 18) return '16-18';
                return '18+';
            })
            ->map->count();

        // Monthly admission trends
        $admissionTrends = Student::where($schoolFilter)
            ->whereYear('admission_date', now()->year)
            ->groupBy(DB::raw('MONTH(admission_date)'))
            ->selectRaw('MONTH(admission_date) as month, COUNT(*) as count')
            ->orderBy('month')
            ->get();

        // Transport usage
        $transportStats = Student::where($schoolFilter)
            ->where('is_active', true)
            ->selectRaw('
                COUNT(*) as total_students,
                SUM(CASE WHEN transport_required = 1 THEN 1 ELSE 0 END) as transport_users,
                SUM(CASE WHEN transport_required = 0 THEN 1 ELSE 0 END) as non_transport_users
            ')
            ->first();

        // Fee payment status
        $feePaymentStatus = Student::where($schoolFilter)
            ->where('is_active', true)
            ->withCount([
                'fees as total_fees',
                'fees as paid_fees' => function ($query) {
                    $query->where('status', 'completed');
                },
                'fees as pending_fees' => function ($query) {
                    $query->where('status', 'pending');
                },
                'fees as overdue_fees' => function ($query) {
                    $query->where('status', 'overdue');
                },
            ])
            ->get()
            ->groupBy(function ($student) {
                if ($student->overdue_fees > 0) return 'defaulters';
                if ($student->pending_fees > 0) return 'partial_payment';
                if ($student->total_fees > 0) return 'fully_paid';
                return 'no_fees';
            })
            ->map->count();

        return Inertia::render('Reports/StudentReport', [
            'demographics' => $demographics,
            'genderDistribution' => $genderDistribution,
            'classEnrollment' => $classEnrollment,
            'ageGroups' => $ageGroups,
            'admissionTrends' => $admissionTrends,
            'transportStats' => $transportStats,
            'feePaymentStatus' => $feePaymentStatus,
            'filters' => $request->only(['class', 'gender', 'status', 'admission_year']),
        ]);
    }

    /**
     * Payment analysis reports.
     */
    public function paymentReport(Request $request): Response
    {
        Gate::authorize('viewAny', 'reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schoolFilter = $user->school_id && !$user->hasRole('Super Admin') 
            ? ['school_id' => $user->school_id] 
            : [];

        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

        // Payment summary
        $paymentSummary = [
            'total_payments' => Payment::where($schoolFilter)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'successful_payments' => Payment::where($schoolFilter)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'failed_payments' => Payment::where($schoolFilter)
                ->where('status', 'failed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'total_amount' => Payment::where($schoolFilter)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
        ];

        // Payment method analysis
        $paymentMethods = Payment::where($schoolFilter)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('payment_method', 'status')
            ->selectRaw('
                payment_method,
                status,
                COUNT(*) as count,
                SUM(amount) as total_amount
            ')
            ->get()
            ->groupBy('payment_method');

        // Gateway performance
        $gatewayPerformance = Payment::where($schoolFilter)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('gateway')
            ->selectRaw('
                gateway,
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful_transactions,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_transactions,
                SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_amount
            ')
            ->get();

        // Daily payment trends
        $dailyTrends = Payment::where($schoolFilter)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as amount')
            ->orderBy('date')
            ->get();

        // Hourly distribution
        $hourlyDistribution = Payment::where($schoolFilter)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->orderBy('hour')
            ->get();

        return Inertia::render('Reports/PaymentReport', [
            'paymentSummary' => $paymentSummary,
            'paymentMethods' => $paymentMethods,
            'gatewayPerformance' => $gatewayPerformance,
            'dailyTrends' => $dailyTrends,
            'hourlyDistribution' => $hourlyDistribution,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'payment_method' => $request->get('payment_method'),
                'gateway' => $request->get('gateway'),
            ],
        ]);
    }

    /**
     * Financial summary reports.
     */
    public function financialReport(Request $request): Response
    {
        Gate::authorize('viewAny', 'reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schoolFilter = $user->school_id && !$user->hasRole('Super Admin') 
            ? ['school_id' => $user->school_id] 
            : [];

        $year = $request->get('year', now()->year);
        $month = $request->get('month');

        // Revenue summary
        $revenueSummary = [
            'total_revenue_ytd' => Payment::where($schoolFilter)
                ->where('status', 'completed')
                ->whereYear('created_at', $year)
                ->sum('amount'),
            'total_revenue_mtd' => Payment::where($schoolFilter)
                ->where('status', 'completed')
                ->whereYear('created_at', $year)
                ->when($month, function ($query) use ($month) {
                    $query->whereMonth('created_at', $month);
                })
                ->sum('amount'),
            'outstanding_fees' => Fee::where($schoolFilter)
                ->whereIn('status', ['pending', 'overdue'])
                ->sum('amount'),
            'collection_efficiency' => 0, // Will calculate below
        ];

        // Calculate collection efficiency
        $totalFeesGenerated = Fee::where($schoolFilter)->whereYear('created_at', $year)->sum('amount');
        $totalCollected = Payment::where($schoolFilter)
            ->where('status', 'completed')
            ->whereYear('created_at', $year)
            ->sum('amount');

        $revenueSummary['collection_efficiency'] = $totalFeesGenerated > 0 
            ? round(($totalCollected / $totalFeesGenerated) * 100, 2) 
            : 0;

        // Monthly revenue trend
        $monthlyRevenue = Payment::where($schoolFilter)
            ->where('status', 'completed')
            ->whereYear('created_at', $year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as revenue')
            ->orderBy('month')
            ->get();

        // Revenue by fee category
        $revenueByCategory = Fee::where($schoolFilter)
            ->whereYear('created_at', $year)
            ->join('fee_structures', 'fees.fee_structure_id', '=', 'fee_structures.id')
            ->groupBy('fee_structures.category')
            ->selectRaw('
                fee_structures.category,
                SUM(CASE WHEN fees.status = "completed" THEN fees.amount ELSE 0 END) as collected,
                SUM(fees.amount) as total_fees
            ')
            ->get();

        // Top fee collectors (students with highest payments)
        $topCollectors = Student::where($schoolFilter)
            ->withSum(['payments as total_paid' => function ($query) use ($year) {
                $query->where('status', 'completed')->whereYear('created_at', $year);
            }], 'amount')
            ->having('total_paid', '>', 0)
            ->orderBy('total_paid', 'desc')
            ->limit(10)
            ->get();

        // Payment failure analysis
        $failureAnalysis = Payment::where($schoolFilter)
            ->where('status', 'failed')
            ->whereYear('created_at', $year)
            ->groupBy('failure_reason')
            ->selectRaw('failure_reason, COUNT(*) as count, SUM(amount) as lost_revenue')
            ->orderBy('count', 'desc')
            ->get();

        return Inertia::render('Reports/FinancialReport', [
            'revenueSummary' => $revenueSummary,
            'monthlyRevenue' => $monthlyRevenue,
            'revenueByCategory' => $revenueByCategory,
            'topCollectors' => $topCollectors,
            'failureAnalysis' => $failureAnalysis,
            'filters' => [
                'year' => $year,
                'month' => $month,
            ],
        ]);
    }

    /**
     * Custom report builder.
     */
    public function customReport(Request $request): Response
    {
        Gate::authorize('viewAny', 'reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'report_type' => 'nullable|in:student,fee,payment,financial',
            'fields' => 'nullable|array',
            'filters' => 'nullable|array',
            'group_by' => 'nullable|string',
            'sort_by' => 'nullable|string',
            'sort_direction' => 'nullable|in:asc,desc',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $data = [];
        $availableFields = [];
        $availableFilters = [];

        if ($validated['report_type']) {
            [$data, $availableFields, $availableFilters] = $this->buildCustomReport($validated, $user);
        }

        return Inertia::render('Reports/CustomReport', [
            'data' => $data,
            'availableFields' => $availableFields,
            'availableFilters' => $availableFilters,
            'filters' => $validated,
        ]);
    }

    /**
     * Export report data.
     */
    public function export(Request $request)
    {
        Gate::authorize('export', 'reports');

        $validated = $request->validate([
            'report_type' => 'required|in:fee,student,payment,financial,custom',
            'format' => 'required|in:csv,excel,pdf',
            'filters' => 'nullable|array',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $fileName = "report_{$validated['report_type']}_" . now()->format('Y-m-d_H-i-s');

        switch ($validated['report_type']) {
            case 'fee':
                return $this->exportFeeReport($validated, $fileName, $user);
            case 'student':
                return $this->exportStudentReport($validated, $fileName, $user);
            case 'payment':
                return $this->exportPaymentReport($validated, $fileName, $user);
            case 'financial':
                return $this->exportFinancialReport($validated, $fileName, $user);
            case 'custom':
                return $this->exportCustomReport($validated, $fileName, $user);
        }
    }

    /**
     * Download saved report.
     */
    public function download(Request $request, $reportId)
    {
        Gate::authorize('export', 'reports');

        // This would implement downloading previously generated reports
        // For now, return a placeholder response

        return response()->json(['message' => 'Download functionality not yet implemented']);
    }

    /**
     * Build custom report based on parameters.
     */
    private function buildCustomReport($validated, $user)
    {
        $schoolFilter = $user->school_id && !$user->hasRole('Super Admin') 
            ? ['school_id' => $user->school_id] 
            : [];

        $data = [];
        $availableFields = [];
        $availableFilters = [];

        switch ($validated['report_type']) {
            case 'student':
                $availableFields = [
                    'first_name' => 'First Name',
                    'last_name' => 'Last Name',
                    'admission_no' => 'Admission No',
                    'class' => 'Class',
                    'gender' => 'Gender',
                    'date_of_birth' => 'Date of Birth',
                    'admission_date' => 'Admission Date',
                    'is_active' => 'Status',
                ];

                $availableFilters = [
                    'class' => 'Class',
                    'gender' => 'Gender',
                    'is_active' => 'Status',
                    'admission_date' => 'Admission Date Range',
                ];

                // Build student query based on filters
                $query = Student::where($schoolFilter);
                $data = $query->limit(100)->get(); // Limit for performance
                break;

            case 'fee':
                $availableFields = [
                    'student_name' => 'Student Name',
                    'fee_structure_name' => 'Fee Structure',
                    'amount' => 'Amount',
                    'status' => 'Status',
                    'due_date' => 'Due Date',
                    'created_at' => 'Created Date',
                ];

                $query = Fee::where($schoolFilter)->with(['student', 'feeStructure']);
                $data = $query->limit(100)->get();
                break;

            // Add more cases for other report types
        }

        return [$data, $availableFields, $availableFilters];
    }

    /**
     * Export methods (placeholders - would be implemented based on requirements).
     */
    private function exportFeeReport($validated, $fileName, $user)
    {
        return response()->json(['message' => 'Fee report export not yet implemented']);
    }

    private function exportStudentReport($validated, $fileName, $user)
    {
        return response()->json(['message' => 'Student report export not yet implemented']);
    }

    private function exportPaymentReport($validated, $fileName, $user)
    {
        return response()->json(['message' => 'Payment report export not yet implemented']);
    }

    private function exportFinancialReport($validated, $fileName, $user)
    {
        return response()->json(['message' => 'Financial report export not yet implemented']);
    }

    private function exportCustomReport($validated, $fileName, $user)
    {
        return response()->json(['message' => 'Custom report export not yet implemented']);
    }
}
