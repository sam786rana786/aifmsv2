<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Services\CacheService;
use App\Services\SettingsService;
use App\Services\ValidationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SettingController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:view settings', only: ['index', 'show', 'edit', 'update', 'updateBatch', 'initializeDefaults']),
            new Middleware('permission:edit settings', only: ['edit', 'update', 'updateBatch', 'initializeDefaults']),
            new Middleware('permission:create settings', only: ['initializeDefaults']),
            new Middleware('permission:delete settings', only: ['destroy']),
            new Middleware('can:view,setting', only: ['index', 'show', 'edit', 'update', 'updateBatch', 'initializeDefaults']),
            new Middleware('can:update,setting', only: ['edit', 'update', 'updateBatch', 'initializeDefaults']),
            new Middleware('can:create,setting', only: ['initializeDefaults']),
            new Middleware('can:delete,setting', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of settings grouped by category.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Setting::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $query = Setting::where('school_id', $user->school_id);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }
        
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $settings = $query->orderBy('category')->orderBy('key')->get()
            ->groupBy('category');

        // Get all available categories
        $categories = Setting::where('school_id', $user->school_id)
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Show the form for editing settings by category.
     */
    public function edit(Request $request, $category = null)
    {
        $this->authorize('update', Setting::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $query = Setting::where('school_id', $user->school_id);

        if ($category) {
            $query->where('category', $category);
        }

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $settings = $query->orderBy('key')->get()->groupBy('category');

        // If no category specified, get the first available category
        if (!$category && $settings->isNotEmpty()) {
            $category = $settings->keys()->first();
        }

        $currentSettings = $category ? $settings->get($category, collect()) : collect();

        return Inertia::render('Settings/Edit', [
            'category' => $category,
            'settings' => $currentSettings,
            'allCategories' => $settings->keys()->values(),
        ]);
    }

    /**
     * Update settings in batch for a category.
     */
    public function updateBatch(Request $request)
    {
        $this->authorize('update', Setting::class);

        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.id' => 'required|exists:settings,id',
            'settings.*.value' => 'nullable|string',
        ]);

        foreach ($validated['settings'] as $settingData) {
            $setting = Setting::where('id', $settingData['id'])
                ->where('school_id', Auth::user()->school_id)
                ->first();

            if ($setting) {
                // Validate based on setting type
                $value = $this->validateAndFormatValue($setting, $settingData['value']);
                
                $setting->update(['value' => $value]);
                
                // Clear cache for this setting
                Cache::forget("setting.{$setting->school_id}.{$setting->key}");
            }
        }

        // Clear all settings cache for this school
        Cache::forget("settings.{auth()->user()->school_id}");

        return redirect()->back()
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Update a single setting.
     */
    public function update(Request $request, Setting $setting)
    {
        $this->authorize('update', $setting);

        $validated = $request->validate([
            'value' => 'nullable|string',
        ]);

        // Validate based on setting type
        $value = $this->validateAndFormatValue($setting, $validated['value']);

        $setting->update(['value' => $value]);

        // Clear cache for this setting
        Cache::forget("setting.{$setting->school_id}.{$setting->key}");
        Cache::forget("settings.{$setting->school_id}");

        return redirect()->route('settings.index')
            ->with('success', 'Setting updated successfully.');
    }

    /**
     * Initialize default settings for a school.
     */
    public function initializeDefaults()
    {
        $this->authorize('create', Setting::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $schoolId = $user->school_id;

        $defaultSettings = [
            // General Settings
            [
                'key' => 'school_name',
                'value' => '',
                'type' => 'string',
                'category' => 'general',
                'description' => 'School name displayed on reports and certificates',
                'is_public' => true,
            ],
            [
                'key' => 'school_address',
                'value' => '',
                'type' => 'text',
                'category' => 'general',
                'description' => 'Complete school address',
                'is_public' => true,
            ],
            [
                'key' => 'school_contact_number',
                'value' => '',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Primary contact number',
                'is_public' => true,
            ],
            [
                'key' => 'school_email',
                'value' => '',
                'type' => 'email',
                'category' => 'general',
                'description' => 'Primary email address',
                'is_public' => true,
            ],

            // Academic Settings
            [
                'key' => 'academic_year_start_month',
                'value' => '4',
                'type' => 'integer',
                'category' => 'academic',
                'description' => 'Month when academic year starts (1-12)',
                'is_public' => false,
            ],
            [
                'key' => 'academic_year_end_month',
                'value' => '3',
                'type' => 'integer',
                'category' => 'academic',
                'description' => 'Month when academic year ends (1-12)',
                'is_public' => false,
            ],

            // Fee Settings
            [
                'key' => 'late_fee_percentage',
                'value' => '5',
                'type' => 'decimal',
                'category' => 'fees',
                'description' => 'Late fee percentage for overdue payments',
                'is_public' => false,
            ],
            [
                'key' => 'grace_period_days',
                'value' => '7',
                'type' => 'integer',
                'category' => 'fees',
                'description' => 'Grace period in days before late fee applies',
                'is_public' => false,
            ],
            [
                'key' => 'currency_symbol',
                'value' => '₹',
                'type' => 'string',
                'category' => 'fees',
                'description' => 'Currency symbol for fee display',
                'is_public' => true,
            ],

            // Notification Settings
            [
                'key' => 'sms_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'category' => 'notifications',
                'description' => 'Enable SMS notifications',
                'is_public' => false,
            ],
            [
                'key' => 'email_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'notifications',
                'description' => 'Enable email notifications',
                'is_public' => false,
            ],
            [
                'key' => 'notification_sender_name',
                'value' => '',
                'type' => 'string',
                'category' => 'notifications',
                'description' => 'Sender name for notifications',
                'is_public' => false,
            ],

            // Report Settings
            [
                'key' => 'report_header_logo',
                'value' => '',
                'type' => 'file',
                'category' => 'reports',
                'description' => 'Logo for report headers',
                'is_public' => false,
            ],
            [
                'key' => 'report_footer_text',
                'value' => '',
                'type' => 'text',
                'category' => 'reports',
                'description' => 'Footer text for reports',
                'is_public' => false,
            ],

            // Security Settings
            [
                'key' => 'session_timeout_minutes',
                'value' => '120',
                'type' => 'integer',
                'category' => 'security',
                'description' => 'Session timeout in minutes',
                'is_public' => false,
            ],
            [
                'key' => 'password_min_length',
                'value' => '8',
                'type' => 'integer',
                'category' => 'security',
                'description' => 'Minimum password length',
                'is_public' => false,
            ],
        ];

        $createdCount = 0;
        foreach ($defaultSettings as $settingData) {
            $exists = Setting::where('school_id', $schoolId)
                ->where('key', $settingData['key'])
                ->exists();

            if (!$exists) {
                Setting::create(array_merge($settingData, ['school_id' => $schoolId]));
                $createdCount++;
            }
        }

        return redirect()->route('settings.index')
            ->with('success', "Default settings initialized. Created {$createdCount} new settings.");
    }

    /**
     * Validate and format setting value based on its type.
     */
    private function validateAndFormatValue(Setting $setting, $value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        switch ($setting->type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
            
            case 'integer':
                return (string) intval($value);
            
            case 'decimal':
                return (string) floatval($value);
            
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException('Invalid email format');
                }
                return $value;
            
            case 'json':
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \InvalidArgumentException('Invalid JSON format');
                }
                return $value;
            
            case 'string':
            case 'text':
            case 'file':
            default:
                return $value;
        }
    }

    /**
     * Get a setting value with caching.
     */
    public function getValue($key, $default = null)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $schoolId = $user->school_id;
        
        return Cache::remember("setting.{$schoolId}.{$key}", 3600, function () use ($schoolId, $key, $default) {
            $setting = Setting::where('school_id', $schoolId)
                ->where('key', $key)
                ->first();
            
            return $setting ? $setting->value : $default;
        });
    }
}
