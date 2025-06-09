<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Student;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\User;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AnalyticsController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:analytics.view', only: ['index', 'dashboard', 'fees', 'students', 'payments', 'users', 'export']),
            new Middleware('permission:analytics.export', only: ['export']),
            new Middleware('can:viewAny,analytics', only: ['index', 'dashboard', 'fees', 'students', 'payments', 'users', 'export']),
        ];
    }

    /**
     * Display analytics dashboard.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', 'analytics');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schoolFilter = $user->school_id && !$user->hasRole('Super Admin') 
            ? ['school_id' => $user->school_id] 
            : [];

        // Get date range filter
        $period = $request->get('period', '30'); // days
        $startDate = now()->subDays($period);
        $endDate = now();

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $startDate = $request->get('date_from');
            $endDate = $request->get('date_to');
        }

        // Key metrics
        $metrics = [
            'total_students' => Student::where($schoolFilter)->where('is_active', true)->count(),
            'total_fees_collected' => Payment::where($schoolFilter)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'pending_fees' => Fee::where($schoolFilter)
                ->where('status', 'pending')
                ->sum('amount'),
            'total_users' => User::where($schoolFilter)->where('is_active', true)->count(),
        ];

        // Revenue trends
        $revenueTrend = Payment::where($schoolFilter)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as amount')
            ->orderBy('date')
            ->get();

        // Payment methods distribution
        $paymentMethods = Payment::where($schoolFilter)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('payment_method')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
            ->get();

        // Fee categories breakdown
        $feeCategories = Fee::where($schoolFilter)
            ->join('fee_structures', 'fees.fee_structure_id', '=', 'fee_structures.id')
            ->whereBetween('fees.created_at', [$startDate, $endDate])
            ->groupBy('fee_structures.category')
            ->selectRaw('fee_structures.category, COUNT(*) as count, SUM(fees.amount) as total_amount')
            ->get();

        // Student enrollment by class
        $enrollmentByClass = Student::where($schoolFilter)
            ->where('is_active', true)
            ->groupBy('class')
            ->selectRaw('class, COUNT(*) as count')
            ->orderBy('class')
            ->get();

        // Recent activities (last 10)
        $recentActivities = collect([]); // Would integrate with ActivityLog if available

        return Inertia::render('Analytics/Index', [
            'metrics' => $metrics,
            'revenueTrend' => $revenueTrend,
            'paymentMethods' => $paymentMethods,
            'feeCategories' => $feeCategories,
            'enrollmentByClass' => $enrollmentByClass,
            'recentActivities' => $recentActivities,
            'filters' => [
                'period' => $period,
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ],
        ]);
    }

    /**
     * Fee analytics dashboard.
     */
    public function fees(Request $request): Response
    {
        Gate::authorize('viewAny', 'analytics');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schoolFilter = $user->school_id && !$user->hasRole('Super Admin') 
            ? ['school_id' => $user->school_id] 
            : [];

        $period = $request->get('period', '30');
        $startDate = now()->subDays($period);
        $endDate = now();

        // Fee collection stats
        $feeStats = [
            'total_fees_generated' => Fee::where($schoolFilter)->sum('amount'),
            'total_collected' => Payment::where($schoolFilter)
                ->where('status', 'completed')
                ->sum('amount'),
            'pending_amount' => Fee::where($schoolFilter)
                ->where('status', 'pending')
                ->sum('amount'),
            'overdue_amount' => Fee::where($schoolFilter)
                ->where('status', 'overdue')
                ->sum('amount'),
            'collection_rate' => 0, // Will calculate below
        ];

        // Calculate collection rate
        $totalGenerated = $feeStats['total_fees_generated'];
        $totalCollected = $feeStats['total_collected'];
        $feeStats['collection_rate'] = $totalGenerated > 0 
            ? round(($totalCollected / $totalGenerated) * 100, 2) 
            : 0;

        // Monthly collection trends
        $monthlyTrends = Payment::where($schoolFilter)
            ->where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as amount, COUNT(*) as count')
            ->orderBy('month')
            ->get();

        // Fee type performance
        $feeTypePerformance = Fee::where($schoolFilter)
            ->join('fee_structures', 'fees.fee_structure_id', '=', 'fee_structures.id')
            ->groupBy('fee_structures.name', 'fee_structures.category')
            ->selectRaw('
                fee_structures.name,
                fee_structures.category,
                COUNT(*) as total_fees,
                SUM(CASE WHEN fees.status = "completed" THEN fees.amount ELSE 0 END) as collected,
                SUM(CASE WHEN fees.status = "pending" THEN fees.amount ELSE 0 END) as pending,
                SUM(fees.amount) as total_amount
            ')
            ->get();

        // Class-wise collection
        $classWiseCollection = Fee::where($schoolFilter)
            ->join('students', 'fees.student_id', '=', 'students.id')
            ->groupBy('students.class')
            ->selectRaw('
                students.class,
                COUNT(*) as total_fees,
                SUM(CASE WHEN fees.status = "completed" THEN fees.amount ELSE 0 END) as collected,
                SUM(CASE WHEN fees.status = "pending" THEN fees.amount ELSE 0 END) as pending
            ')
            ->orderBy('students.class')
            ->get();

        // Defaulter analysis
        $defaulters = Student::where($schoolFilter)
            ->whereHas('fees', function ($query) {
                $query->where('status', 'overdue');
            })
            ->withSum(['fees as overdue_amount' => function ($query) {
                $query->where('status', 'overdue');
            }], 'amount')
            ->orderBy('overdue_amount', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('Analytics/Fees', [
            'feeStats' => $feeStats,
            'monthlyTrends' => $monthlyTrends,
            'feeTypePerformance' => $feeTypePerformance,
            'classWiseCollection' => $classWiseCollection,
            'defaulters' => $defaulters,
            'filters' => $request->only(['period', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Student analytics dashboard.
     */
    public function students(Request $request): Response
    {
        Gate::authorize('viewAny', 'analytics');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schoolFilter = $user->school_id && !$user->hasRole('Super Admin') 
            ? ['school_id' => $user->school_id] 
            : [];

        // Student statistics
        $studentStats = [
            'total_students' => Student::where($schoolFilter)->count(),
            'active_students' => Student::where($schoolFilter)->where('is_active', true)->count(),
            'inactive_students' => Student::where($schoolFilter)->where('is_active', false)->count(),
            'new_admissions_this_month' => Student::where($schoolFilter)
                ->whereMonth('admission_date', now()->month)
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
        $ageDistribution = Student::where($schoolFilter)
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
            ->map(function ($group) {
                return $group->count();
            });

        // Admission trends (last 12 months)
        $admissionTrends = Student::where($schoolFilter)
            ->where('admission_date', '>=', now()->subMonths(12))
            ->groupBy(DB::raw('YEAR(admission_date), MONTH(admission_date)'))
            ->selectRaw('YEAR(admission_date) as year, MONTH(admission_date) as month, COUNT(*) as count')
            ->orderBy('year')
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

        return Inertia::render('Analytics/Students', [
            'studentStats' => $studentStats,
            'genderDistribution' => $genderDistribution,
            'classEnrollment' => $classEnrollment,
            'ageDistribution' => $ageDistribution,
            'admissionTrends' => $admissionTrends,
            'transportStats' => $transportStats,
            'filters' => $request->only(['period', 'class', 'gender']),
        ]);
    }

    /**
     * Payment analytics dashboard.
     */
    public function payments(Request $request): Response
    {
        Gate::authorize('viewAny', 'analytics');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schoolFilter = $user->school_id && !$user->hasRole('Super Admin') 
            ? ['school_id' => $user->school_id] 
            : [];

        $period = $request->get('period', '30');
        $startDate = now()->subDays($period);

        // Payment statistics
        $paymentStats = [
            'total_payments' => Payment::where($schoolFilter)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'successful_payments' => Payment::where($schoolFilter)
                ->where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'failed_payments' => Payment::where($schoolFilter)
                ->where('status', 'failed')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'total_amount' => Payment::where($schoolFilter)
                ->where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
            'average_payment' => Payment::where($schoolFilter)
                ->where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->avg('amount'),
        ];

        // Payment method analysis
        $paymentMethodStats = Payment::where($schoolFilter)
            ->where('created_at', '>=', $startDate)
            ->groupBy('payment_method', 'status')
            ->selectRaw('
                payment_method,
                status,
                COUNT(*) as count,
                SUM(amount) as total_amount
            ')
            ->get()
            ->groupBy('payment_method');

        // Daily payment trends
        $dailyTrends = Payment::where($schoolFilter)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as amount')
            ->orderBy('date')
            ->get();

        // Peak payment hours
        $hourlyDistribution = Payment::where($schoolFilter)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->orderBy('hour')
            ->get();

        // Payment gateway performance
        $gatewayPerformance = Payment::where($schoolFilter)
            ->where('created_at', '>=', $startDate)
            ->groupBy('gateway', 'status')
            ->selectRaw('
                gateway,
                status,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_processing_time
            ')
            ->get()
            ->groupBy('gateway');

        return Inertia::render('Analytics/Payments', [
            'paymentStats' => $paymentStats,
            'paymentMethodStats' => $paymentMethodStats,
            'dailyTrends' => $dailyTrends,
            'hourlyDistribution' => $hourlyDistribution,
            'gatewayPerformance' => $gatewayPerformance,
            'filters' => $request->only(['period', 'payment_method', 'gateway']),
        ]);
    }

    /**
     * User analytics dashboard.
     */
    public function users(Request $request): Response
    {
        Gate::authorize('viewAny', 'analytics');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schoolFilter = $user->school_id && !$user->hasRole('Super Admin') 
            ? ['school_id' => $user->school_id] 
            : [];

        // User statistics
        $userStats = [
            'total_users' => User::where($schoolFilter)->count(),
            'active_users' => User::where($schoolFilter)->where('is_active', true)->count(),
            'inactive_users' => User::where($schoolFilter)->where('is_active', false)->count(),
            'users_logged_in_today' => User::where($schoolFilter)
                ->whereDate('last_login_at', today())
                ->count(),
            'new_users_this_month' => User::where($schoolFilter)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        // Role distribution
        $roleDistribution = User::where($schoolFilter)
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', User::class)
            ->groupBy('roles.name')
            ->selectRaw('roles.name as role, COUNT(*) as count')
            ->get();

        // Login activity (last 30 days)
        $loginActivity = User::where($schoolFilter)
            ->where('last_login_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(last_login_at)'))
            ->selectRaw('DATE(last_login_at) as date, COUNT(*) as count')
            ->orderBy('date')
            ->get();

        // Most active users
        $activeUsers = User::where($schoolFilter)
            ->where('is_active', true)
            ->orderBy('last_login_at', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'email', 'last_login_at', 'login_count']);

        return Inertia::render('Analytics/Users', [
            'userStats' => $userStats,
            'roleDistribution' => $roleDistribution,
            'loginActivity' => $loginActivity,
            'activeUsers' => $activeUsers,
            'filters' => $request->only(['period', 'role', 'status']),
        ]);
    }

    /**
     * Export analytics data.
     */
    public function export(Request $request)
    {
        Gate::authorize('viewAny', 'analytics');

        $validated = $request->validate([
            'type' => 'required|in:fees,students,payments,users,overview',
            'format' => 'required|in:csv,excel,pdf',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $fileName = "analytics_{$validated['type']}_" . now()->format('Y-m-d_H-i-s');

        switch ($validated['type']) {
            case 'fees':
                return $this->exportFeesAnalytics($validated, $fileName, $user);
            case 'students':
                return $this->exportStudentsAnalytics($validated, $fileName, $user);
            case 'payments':
                return $this->exportPaymentsAnalytics($validated, $fileName, $user);
            case 'users':
                return $this->exportUsersAnalytics($validated, $fileName, $user);
            case 'overview':
                return $this->exportOverviewAnalytics($validated, $fileName, $user);
        }
    }

    /**
     * Get comparative analytics between periods.
     */
    public function compare(Request $request)
    {
        Gate::authorize('viewAny', 'analytics');

        $validated = $request->validate([
            'current_start' => 'required|date',
            'current_end' => 'required|date|after:current_start',
            'previous_start' => 'required|date',
            'previous_end' => 'required|date|after:previous_start',
            'metrics' => 'array',
            'metrics.*' => 'in:revenue,payments,students,fees',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schoolFilter = $user->school_id && !$user->hasRole('Super Admin') 
            ? ['school_id' => $user->school_id] 
            : [];

        $comparison = [];

        foreach ($validated['metrics'] ?? ['revenue', 'payments', 'students', 'fees'] as $metric) {
            $comparison[$metric] = $this->getMetricComparison(
                $metric,
                $validated['current_start'],
                $validated['current_end'],
                $validated['previous_start'],
                $validated['previous_end'],
                $schoolFilter
            );
        }

        return response()->json([
            'comparison' => $comparison,
            'periods' => [
                'current' => [
                    'start' => $validated['current_start'],
                    'end' => $validated['current_end'],
                ],
                'previous' => [
                    'start' => $validated['previous_start'],
                    'end' => $validated['previous_end'],
                ],
            ],
        ]);
    }

    /**
     * Get metric comparison between two periods.
     */
    private function getMetricComparison($metric, $currentStart, $currentEnd, $previousStart, $previousEnd, $schoolFilter)
    {
        switch ($metric) {
            case 'revenue':
                $current = Payment::where($schoolFilter)
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$currentStart, $currentEnd])
                    ->sum('amount');
                
                $previous = Payment::where($schoolFilter)
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$previousStart, $previousEnd])
                    ->sum('amount');
                
                break;

            case 'payments':
                $current = Payment::where($schoolFilter)
                    ->whereBetween('created_at', [$currentStart, $currentEnd])
                    ->count();
                
                $previous = Payment::where($schoolFilter)
                    ->whereBetween('created_at', [$previousStart, $previousEnd])
                    ->count();
                
                break;

            case 'students':
                $current = Student::where($schoolFilter)
                    ->whereBetween('admission_date', [$currentStart, $currentEnd])
                    ->count();
                
                $previous = Student::where($schoolFilter)
                    ->whereBetween('admission_date', [$previousStart, $previousEnd])
                    ->count();
                
                break;

            case 'fees':
                $current = Fee::where($schoolFilter)
                    ->whereBetween('created_at', [$currentStart, $currentEnd])
                    ->sum('amount');
                
                $previous = Fee::where($schoolFilter)
                    ->whereBetween('created_at', [$previousStart, $previousEnd])
                    ->sum('amount');
                
                break;

            default:
                return null;
        }

        $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'current' => $current,
            'previous' => $previous,
            'change' => round($change, 2),
            'change_direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'same'),
        ];
    }

    /**
     * Export methods would be implemented based on requirements.
     */
    private function exportFeesAnalytics($validated, $fileName, $user)
    {
        // Implementation would depend on export requirements
        return response()->json(['message' => 'Fees analytics export not yet implemented']);
    }

    private function exportStudentsAnalytics($validated, $fileName, $user)
    {
        // Implementation would depend on export requirements
        return response()->json(['message' => 'Students analytics export not yet implemented']);
    }

    private function exportPaymentsAnalytics($validated, $fileName, $user)
    {
        // Implementation would depend on export requirements
        return response()->json(['message' => 'Payments analytics export not yet implemented']);
    }

    private function exportUsersAnalytics($validated, $fileName, $user)
    {
        // Implementation would depend on export requirements
        return response()->json(['message' => 'Users analytics export not yet implemented']);
    }

    private function exportOverviewAnalytics($validated, $fileName, $user)
    {
        // Implementation would depend on export requirements
        return response()->json(['message' => 'Overview analytics export not yet implemented']);
    }
}
