<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Notification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class NotificationController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:notifications.view', only: ['index', 'show']),
            new Middleware('permission:notifications.create', only: ['create', 'store']),
            new Middleware('permission:notifications.edit', only: ['edit', 'update']),
            new Middleware('permission:notifications.delete', only: ['destroy']),
            new Middleware('can:view,notification', only: ['show']),
            new Middleware('can:update,notification', only: ['edit', 'update']),
            new Middleware('can:delete,notification', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of notifications.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Notification::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $notifications = Notification::query()
            ->with(['sender', 'recipient'])
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new notification.
     */
    public function create(): Response
    {
        Gate::authorize('create', Notification::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Get students and users for recipient selection
        $students = Student::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no']);

        $users = User::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Notifications/Create', [
            'students' => $students,
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created notification.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Notification::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,warning,success,error',
            'priority' => 'required|in:low,medium,high',
            'recipient_type' => 'required|in:user,student,all_users,all_students',
            'recipient_id' => 'nullable|integer',
            'scheduled_at' => 'nullable|date|after:now',
            'expires_at' => 'nullable|date|after:scheduled_at',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $validated['school_id'] = $user->school_id;
        $validated['sender_id'] = $user->id;
        $validated['sender_type'] = User::class;

        // Set default status
        $validated['status'] = $validated['scheduled_at'] ? 'scheduled' : 'active';

        // Handle recipient based on type
        if ($validated['recipient_type'] === 'user' || $validated['recipient_type'] === 'student') {
            $validated['recipient_type'] = $validated['recipient_type'] === 'user' ? User::class : Student::class;
        } else {
            $validated['recipient_id'] = null;
            // recipient_type remains unchanged for 'all_users' and 'all_students'
        }

        $notification = Notification::create($validated);

        return redirect()->route('notifications.index')
            ->with('success', 'Notification created successfully.');
    }

    /**
     * Display the specified notification.
     */
    public function show(Notification $notification): Response
    {
        Gate::authorize('view', $notification);

        $notification->load(['sender', 'recipient']);

        return Inertia::render('Notifications/Show', [
            'notification' => $notification,
        ]);
    }

    /**
     * Show the form for editing the specified notification.
     */
    public function edit(Notification $notification): Response
    {
        Gate::authorize('update', $notification);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Get students and users for recipient selection
        $students = Student::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no']);

        $users = User::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Notifications/Edit', [
            'notification' => $notification,
            'students' => $students,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified notification.
     */
    public function update(Request $request, Notification $notification): RedirectResponse
    {
        Gate::authorize('update', $notification);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,warning,success,error',
            'priority' => 'required|in:low,medium,high',
            'recipient_type' => 'required|in:user,student,all_users,all_students',
            'recipient_id' => 'nullable|integer',
            'scheduled_at' => 'nullable|date|after:now',
            'expires_at' => 'nullable|date|after:scheduled_at',
            'status' => 'required|in:draft,scheduled,active,expired,cancelled',
        ]);

        // Handle recipient based on type
        if ($validated['recipient_type'] === 'user' || $validated['recipient_type'] === 'student') {
            $validated['recipient_type'] = $validated['recipient_type'] === 'user' ? User::class : Student::class;
        } else {
            $validated['recipient_id'] = null;
            // recipient_type remains unchanged for 'all_users' and 'all_students'
        }

        $notification->update($validated);

        return redirect()->route('notifications.index')
            ->with('success', 'Notification updated successfully.');
    }

    /**
     * Remove the specified notification.
     */
    public function destroy(Notification $notification): RedirectResponse
    {
        Gate::authorize('delete', $notification);

        $notification->delete();

        return redirect()->route('notifications.index')
            ->with('success', 'Notification deleted successfully.');
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification): RedirectResponse
    {
        $notification->update(['read_at' => now()]);

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Get user's unread notifications.
     */
    public function getUnread(): Response
    {
        $user = Auth::user();

        $notifications = Notification::query()
            ->where(function ($query) use ($user) {
                $query->where('recipient_type', get_class($user))
                    ->where('recipient_id', $user->id);
            })
            ->orWhere(function ($query) use ($user) {
                $query->where('recipient_type', 'all_users')
                    ->where('school_id', $user->school_id);
            })
            ->where('status', 'active')
            ->whereNull('read_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Notifications/Unread', [
            'notifications' => $notifications,
        ]);
    }
}
