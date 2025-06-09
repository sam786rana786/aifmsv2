<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\TransportRoute;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\GPSTrackingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use App\Services\RouteOptimizationService;
use App\Services\TransportManagementService;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TransportRouteController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:view transport routes', only: ['index', 'show', 'statistics']),
            new Middleware('permission:create transport routes', only: ['create', 'store']),
            new Middleware('permission:edit transport routes', only: ['edit', 'update', 'toggleActive']),
            new Middleware('permission:delete transport routes', only: ['destroy']),
            new Middleware('can:view,transportRoute', only: ['show']),
            new Middleware('can:update,transportRoute', only: ['edit', 'update', 'toggleActive']),
            new Middleware('can:delete,transportRoute', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of transport routes.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', TransportRoute::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $query = TransportRoute::where('school_id', $user->school_id);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('route_code', 'like', "%{$search}%")
                  ->orWhere('start_point', 'like', "%{$search}%")
                  ->orWhere('end_point', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $routes = $query->withCount('transportAssignments')
            ->orderBy('route_code')
            ->paginate(15);

        return Inertia::render('TransportRoutes/Index', [
            'routes' => $routes,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new transport route.
     */
    public function create()
    {
        $this->authorize('create', TransportRoute::class);

        return Inertia::render('TransportRoutes/Create');
    }

    /**
     * Store a newly created transport route.
     */
    public function store(Request $request)
    {
        $this->authorize('create', TransportRoute::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'route_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('transport_routes', 'route_code')->where('school_id', Auth::user()->school_id)
            ],
            'start_point' => 'required|string|max:255',
            'end_point' => 'required|string|max:255',
            'distance_km' => 'nullable|numeric|min:0|max:9999.99',
            'estimated_time_minutes' => 'nullable|integer|min:0|max:1440',
            'monthly_fee' => 'nullable|numeric|min:0|max:999999.99',
            'stops' => 'nullable|array',
            'stops.*.name' => 'required|string|max:255',
            'stops.*.distance_from_start' => 'nullable|numeric|min:0',
            'stops.*.pickup_time' => 'nullable|date_format:H:i',
            'stops.*.drop_time' => 'nullable|date_format:H:i',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $validated['school_id'] = Auth::user()->school_id;
        $validated['is_active'] = $request->boolean('is_active', true);

        // Handle stops data
        if (isset($validated['stops'])) {
            $validated['stops'] = json_encode($validated['stops']);
        }

        TransportRoute::create($validated);

        return redirect()->route('transport-routes.index')
            ->with('success', 'Transport route created successfully.');
    }

    /**
     * Display the specified transport route.
     */
    public function show(TransportRoute $transportRoute)
    {
        $this->authorize('view', $transportRoute);

        $transportRoute->load(['transportAssignments.student:id,name,admission_number']);

        // Decode stops data
        $transportRoute->stops = $transportRoute->stops ? json_decode($transportRoute->stops, true) : [];

        return Inertia::render('TransportRoutes/Show', [
            'route' => $transportRoute,
        ]);
    }

    /**
     * Show the form for editing the specified transport route.
     */
    public function edit(TransportRoute $transportRoute)
    {
        $this->authorize('update', $transportRoute);

        // Decode stops data for editing
        $transportRoute->stops = $transportRoute->stops ? json_decode($transportRoute->stops, true) : [];

        return Inertia::render('TransportRoutes/Edit', [
            'route' => $transportRoute,
        ]);
    }

    /**
     * Update the specified transport route.
     */
    public function update(Request $request, TransportRoute $transportRoute)
    {
        $this->authorize('update', $transportRoute);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'route_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('transport_routes', 'route_code')
                    ->where('school_id', Auth::user()->school_id)
                    ->ignore($transportRoute->id)
            ],
            'start_point' => 'required|string|max:255',
            'end_point' => 'required|string|max:255',
            'distance_km' => 'nullable|numeric|min:0|max:9999.99',
            'estimated_time_minutes' => 'nullable|integer|min:0|max:1440',
            'monthly_fee' => 'nullable|numeric|min:0|max:999999.99',
            'stops' => 'nullable|array',
            'stops.*.name' => 'required|string|max:255',
            'stops.*.distance_from_start' => 'nullable|numeric|min:0',
            'stops.*.pickup_time' => 'nullable|date_format:H:i',
            'stops.*.drop_time' => 'nullable|date_format:H:i',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        // Handle stops data
        if (isset($validated['stops'])) {
            $validated['stops'] = json_encode($validated['stops']);
        } else {
            $validated['stops'] = null;
        }

        $transportRoute->update($validated);

        return redirect()->route('transport-routes.index')
            ->with('success', 'Transport route updated successfully.');
    }

    /**
     * Remove the specified transport route.
     */
    public function destroy(TransportRoute $transportRoute)
    {
        $this->authorize('delete', $transportRoute);

        // Check if route has active assignments
        if ($transportRoute->transportAssignments()->exists()) {
            return back()->withErrors([
                'error' => 'Cannot delete transport route that has active student assignments.'
            ]);
        }

        $transportRoute->delete();

        return redirect()->route('transport-routes.index')
            ->with('success', 'Transport route deleted successfully.');
    }

    /**
     * Toggle the active status of a transport route.
     */
    public function toggleActive(TransportRoute $transportRoute)
    {
        $this->authorize('update', $transportRoute);

        $transportRoute->update([
            'is_active' => !$transportRoute->is_active
        ]);

        $status = $transportRoute->is_active ? 'activated' : 'deactivated';

        return redirect()->route('transport-routes.index')
            ->with('success', "Transport route {$status} successfully.");
    }

    /**
     * Get route statistics.
     */
    public function statistics()
    {
        $this->authorize('viewAny', TransportRoute::class);

        $schoolId = Auth::user()->school_id;

        $stats = [
            'total_routes' => TransportRoute::where('school_id', $schoolId)->count(),
            'active_routes' => TransportRoute::where('school_id', $schoolId)->where('is_active', true)->count(),
            'total_students_assigned' => DB::table('transport_assignments')
                ->join('transport_routes', 'transport_assignments.transport_route_id', '=', 'transport_routes.id')
                ->where('transport_routes.school_id', $schoolId)
                ->where('transport_assignments.is_active', true)
                ->count(),
            'total_distance' => TransportRoute::where('school_id', $schoolId)
                ->where('is_active', true)
                ->sum('distance_km'),
        ];

        return response()->json($stats);
    }
}
