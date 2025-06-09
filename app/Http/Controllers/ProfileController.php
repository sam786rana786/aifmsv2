<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Services\SecurityService;
use App\Services\PreferenceService;
use Illuminate\Support\Facades\Log;
use App\Services\ActivityLogService;
use App\Services\FileManagerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules\Password;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProfileController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:view profile', only: ['show', 'edit', 'update', 'updatePassword', 'destroy', 'activityLogs', 'downloadData']),
            new Middleware('permission:edit profile', only: ['edit', 'update', 'updatePassword', 'destroy', 'activityLogs', 'downloadData']),
            new Middleware('permission:delete profile', only: ['destroy', 'activityLogs', 'downloadData']),
            new Middleware('can:view,user', only: ['show', 'edit', 'update', 'updatePassword', 'destroy', 'activityLogs', 'downloadData']),
            new Middleware('can:update,user', only: ['edit', 'update', 'updatePassword', 'destroy', 'activityLogs', 'downloadData']),
            new Middleware('can:delete,user', only: ['destroy', 'activityLogs', 'downloadData']),
        ];
    }

    /**
     * Extract profile changes for logging purposes.
     */
    private function getProfileChanges(Request $request): array
    {
        $changes = [];
        $sensitiveFields = ['password', 'current_password'];
        
        foreach ($request->all() as $key => $value) {
            if (!in_array($key, $sensitiveFields)) {
                $changes[$key] = is_string($value) ? substr($value, 0, 100) : $value;
            }
        }
        
        return $changes;
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load(['school:id,name', 'roles:id,name']);

        return Inertia::render('Profile/Edit', [
            'user' => $user,
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $avatar = $request->file('avatar');
            $avatarPath = $avatar->store('avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return Redirect::route('profile.edit')->with('success', 'Password updated successfully.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Delete avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Show the user's profile (public view).
     */
    public function show(Request $request): Response
    {
        $user = $request->user();
        $user->load(['school:id,name', 'roles:id,name']);

        // Get activity summary
        $activitySummary = [
            'last_login' => $user->last_login_at,
            'account_created' => $user->created_at,
            'total_logins' => $user->login_count ?? 0,
        ];

        return Inertia::render('Profile/Show', [
            'user' => $user,
            'activitySummary' => $activitySummary,
        ]);
    }

    /**
     * Update user preferences.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preferences' => 'array',
            'preferences.theme' => 'nullable|string|in:light,dark,system',
            'preferences.language' => 'nullable|string|in:en,hi,ta,te,kn,ml,bn,gu,pa,mr',
            'preferences.timezone' => 'nullable|string',
            'preferences.date_format' => 'nullable|string|in:DD/MM/YYYY,MM/DD/YYYY,YYYY-MM-DD',
            'preferences.notifications' => 'array',
            'preferences.notifications.email' => 'boolean',
            'preferences.notifications.browser' => 'boolean',
            'preferences.notifications.sms' => 'boolean',
        ]);

        $user = $request->user();
        $currentPreferences = $user->preferences ?? [];
        
        // Merge new preferences with existing ones
        $updatedPreferences = array_merge($currentPreferences, $validated['preferences'] ?? []);
        
        $user->update(['preferences' => $updatedPreferences]);

        return Redirect::route('profile.edit')->with('success', 'Preferences updated successfully.');
    }

    /**
     * Upload avatar.
     */
    public function uploadAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $avatar = $request->file('avatar');
        $avatarPath = $avatar->store('avatars', 'public');

        $user->update(['avatar' => $avatarPath]);

        return Redirect::route('profile.edit')->with('success', 'Avatar updated successfully.');
    }

    /**
     * Remove avatar.
     */
    public function removeAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);

        return Redirect::route('profile.edit')->with('success', 'Avatar removed successfully.');
    }

    /**
     * Get user's activity logs.
     */
    public function activityLogs(Request $request)
    {
        $user = $request->user();
        
        // This would require an ActivityLog model/system
        // For now, return empty array
        $logs = collect([]);

        return response()->json([
            'data' => $logs,
            'total' => $logs->count(),
        ]);
    }

    /**
     * Download user data (GDPR compliance).
     */
    public function downloadData(Request $request)
    {
        $user = $request->user();
        $user->load(['school', 'roles']);

        $userData = [
            'personal_information' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'employee_id' => $user->employee_id,
                'date_of_birth' => $user->date_of_birth,
                'gender' => $user->gender,
                'address' => $user->address,
            ],
            'account_information' => [
                'account_created' => $user->created_at,
                'last_updated' => $user->updated_at,
                'email_verified_at' => $user->email_verified_at,
                'last_login_at' => $user->last_login_at,
                'preferences' => $user->preferences,
            ],
            'school_information' => [
                'school_name' => $user->school->name ?? 'N/A',
                'roles' => $user->roles->pluck('name')->toArray(),
            ],
        ];

        $fileName = 'user_data_' . $user->id . '_' . now()->format('Y-m-d') . '.json';

        return response()->json($userData)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}
