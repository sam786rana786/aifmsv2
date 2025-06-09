<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ActivityLogController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:activity_logs.view', only: ['index', 'show', 'export']),
            new Middleware('permission:activity_logs.delete', only: ['destroy', 'bulkDelete', 'purge']),
            new Middleware('permission:activity_logs.export', only: ['export']),
            new Middleware('can:viewAny,activity_log', only: ['index', 'show', 'export']),
            new Middleware('can:delete,activity_log', only: ['destroy', 'bulkDelete', 'purge']),
        ];
    }

    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ActivityLog::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = ActivityLog::query()
            ->with(['causer:id,name,email', 'subject'])
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('log_name', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%")
                  ->orWhereHas('causer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->get('log_name'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->get('event'));
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->get('causer_id'));
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->get('subject_type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get filter options
        $logNames = ActivityLog::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->distinct()
            ->pluck('log_name')
            ->filter()
            ->sort()
            ->values();

        $events = ActivityLog::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->distinct()
            ->pluck('event')
            ->filter()
            ->sort()
            ->values();

        $subjectTypes = ActivityLog::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->distinct()
            ->pluck('subject_type')
            ->filter()
            ->map(function ($type) {
                return [
                    'value' => $type,
                    'label' => class_basename($type),
                ];
            })
            ->values();

        $users = User::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only([
                'search', 'log_name', 'event', 'causer_id', 'subject_type', 'date_from', 'date_to'
            ]),
            'filterOptions' => [
                'logNames' => $logNames,
                'events' => $events,
                'subjectTypes' => $subjectTypes,
                'users' => $users,
            ],
        ]);
    }

    /**
     * Display the specified activity log.
     */
    public function show(ActivityLog $activityLog): Response
    {
        Gate::authorize('view', $activityLog);

        $activityLog->load(['causer:id,name,email', 'subject']);

        return Inertia::render('ActivityLogs/Show', [
            'log' => $activityLog,
        ]);
    }

    /**
     * Remove the specified activity log.
     */
    public function destroy(ActivityLog $activityLog): RedirectResponse
    {
        Gate::authorize('delete', $activityLog);

        $activityLog->delete();

        return redirect()->route('activity-logs.index')
            ->with('success', 'Activity log deleted successfully.');
    }

    /**
     * Bulk delete activity logs.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('delete', ActivityLog::class);

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:activity_log,id',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = ActivityLog::whereIn('id', $validated['ids']);

        // Apply school filter for non-super admins
        if ($user->school_id && !$user->hasRole('Super Admin')) {
            $query->where('school_id', $user->school_id);
        }

        $deletedCount = $query->count();
        $query->delete();

        return redirect()->route('activity-logs.index')
            ->with('success', "Successfully deleted {$deletedCount} activity logs.");
    }

    /**
     * Purge old activity logs.
     */
    public function purge(Request $request): RedirectResponse
    {
        Gate::authorize('delete', ActivityLog::class);

        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = ActivityLog::where('created_at', '<', now()->subDays($validated['days']));

        // Apply school filter for non-super admins
        if ($user->school_id && !$user->hasRole('Super Admin')) {
            $query->where('school_id', $user->school_id);
        }

        $deletedCount = $query->count();
        $query->delete();

        return redirect()->route('activity-logs.index')
            ->with('success', "Successfully purged {$deletedCount} activity logs older than {$validated['days']} days.");
    }

    /**
     * Export activity logs.
     */
    public function export(Request $request)
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $validated = $request->validate([
            'format' => 'required|in:csv,excel,pdf',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'log_name' => 'nullable|string',
            'event' => 'nullable|string',
            'causer_id' => 'nullable|exists:users,id',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = ActivityLog::with(['causer:id,name,email'])
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });

        // Apply filters
        if (!empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        if (!empty($validated['log_name'])) {
            $query->where('log_name', $validated['log_name']);
        }

        if (!empty($validated['event'])) {
            $query->where('event', $validated['event']);
        }

        if (!empty($validated['causer_id'])) {
            $query->where('causer_id', $validated['causer_id']);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $fileName = 'activity_logs_' . now()->format('Y-m-d_H-i-s');

        switch ($validated['format']) {
            case 'csv':
                return $this->exportToCsv($logs, $fileName);
            case 'excel':
                return $this->exportToExcel($logs, $fileName);
            case 'pdf':
                return $this->exportToPdf($logs, $fileName);
        }
    }

    /**
     * Get activity statistics.
     */
    public function statistics(Request $request)
    {
        Gate::authorize('viewAny', ActivityLog::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = ActivityLog::when($user->school_id && !$user->hasRole('Super Admin'), function ($q) use ($user) {
            $q->where('school_id', $user->school_id);
        });

        $period = $request->get('period', '30'); // days

        $stats = [
            'total_logs' => (clone $query)->count(),
            'logs_today' => (clone $query)->whereDate('created_at', today())->count(),
            'logs_this_week' => (clone $query)->whereBetween('created_at', [
                now()->startOfWeek(), now()->endOfWeek()
            ])->count(),
            'logs_this_month' => (clone $query)->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
            'logs_period' => (clone $query)->where('created_at', '>=', now()->subDays($period))->count(),
        ];

        // Activity by event type
        $eventStats = (clone $query)->where('created_at', '>=', now()->subDays($period))
            ->groupBy('event')
            ->selectRaw('event, count(*) as count')
            ->orderBy('count', 'desc')
            ->get();

        // Activity by log name
        $logNameStats = (clone $query)->where('created_at', '>=', now()->subDays($period))
            ->groupBy('log_name')
            ->selectRaw('log_name, count(*) as count')
            ->orderBy('count', 'desc')
            ->get();

        // Daily activity trend
        $dailyActivity = (clone $query)->where('created_at', '>=', now()->subDays($period))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->orderBy('date')
            ->get();

        // Top users by activity
        $topUsers = (clone $query)->where('created_at', '>=', now()->subDays($period))
            ->whereNotNull('causer_id')
            ->with('causer:id,name,email')
            ->groupBy('causer_id')
            ->selectRaw('causer_id, count(*) as count')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'eventStats' => $eventStats,
            'logNameStats' => $logNameStats,
            'dailyActivity' => $dailyActivity,
            'topUsers' => $topUsers,
            'period' => $period,
        ]);
    }

    /**
     * Export logs to CSV.
     */
    private function exportToCsv($logs, $fileName)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '.csv"',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'ID', 'Log Name', 'Event', 'Description', 'User', 'Subject Type', 
                'Subject ID', 'Properties', 'Created At'
            ]);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->log_name,
                    $log->event,
                    $log->description,
                    $log->causer?->name ?? 'System',
                    class_basename($log->subject_type ?? ''),
                    $log->subject_id,
                    json_encode($log->properties),
                    $log->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export logs to Excel (would require Laravel Excel package).
     */
    private function exportToExcel($logs, $fileName)
    {
        // This would require the Laravel Excel package
        // For now, fallback to CSV
        return $this->exportToCsv($logs, $fileName);
    }

    /**
     * Export logs to PDF (would require a PDF package like DomPDF).
     */
    private function exportToPdf($logs, $fileName)
    {
        // This would require a PDF package like DomPDF
        // For now, return JSON
        return response()->json($logs);
    }
}
