<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ConcessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\FeeTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PreviousYearBalanceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPromotionController;
use App\Http\Controllers\TransportAssignmentController;
use App\Http\Controllers\TransportRouteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

// Dashboard route - accessible to all authenticated users
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Academic Year Management
    Route::middleware('permission:view_academic_years')->group(function () {
        Route::get('/academic-years', [AcademicYearController::class, 'index'])->name('academic-years.index');
        Route::get('/academic-years/create', [AcademicYearController::class, 'create'])->middleware('permission:create_academic_years')->name('academic-years.create');
        Route::post('/academic-years', [AcademicYearController::class, 'store'])->middleware('permission:create_academic_years')->name('academic-years.store');
        Route::get('/academic-years/{academicYear}', [AcademicYearController::class, 'show'])->name('academic-years.show');
        Route::get('/academic-years/{academicYear}/edit', [AcademicYearController::class, 'edit'])->middleware('permission:edit_academic_years')->name('academic-years.edit');
        Route::put('/academic-years/{academicYear}', [AcademicYearController::class, 'update'])->middleware('permission:edit_academic_years')->name('academic-years.update');
        Route::delete('/academic-years/{academicYear}', [AcademicYearController::class, 'destroy'])->middleware('permission:delete_academic_years')->name('academic-years.destroy');
    });
    
    // School Classes Management
    Route::middleware('permission:view_classes')->group(function () {
        Route::get('/classes', [SchoolClassController::class, 'index'])->name('classes.index');
        Route::get('/classes/create', [SchoolClassController::class, 'create'])->middleware('permission:create_classes')->name('classes.create');
        Route::post('/classes', [SchoolClassController::class, 'store'])->middleware('permission:create_classes')->name('classes.store');
        Route::get('/classes/{schoolClass}', [SchoolClassController::class, 'show'])->name('classes.show');
        Route::get('/classes/{schoolClass}/edit', [SchoolClassController::class, 'edit'])->middleware('permission:edit_classes')->name('classes.edit');
        Route::put('/classes/{schoolClass}', [SchoolClassController::class, 'update'])->middleware('permission:edit_classes')->name('classes.update');
        Route::delete('/classes/{schoolClass}', [SchoolClassController::class, 'destroy'])->middleware('permission:delete_classes')->name('classes.destroy');
    });
    
    // Student Management
    Route::middleware('permission:view_students')->group(function () {
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/create', [StudentController::class, 'create'])->middleware('permission:create_students')->name('students.create');
        Route::post('/students', [StudentController::class, 'store'])->middleware('permission:create_students')->name('students.store');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->middleware('permission:edit_students')->name('students.edit');
        Route::put('/students/{student}', [StudentController::class, 'update'])->middleware('permission:edit_students')->name('students.update');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->middleware('permission:delete_students')->name('students.destroy');
        Route::post('/students/import', [StudentController::class, 'import'])->middleware('permission:create_students')->name('students.import');
        Route::get('/students/export', [StudentController::class, 'export'])->middleware('permission:view_students')->name('students.export');
    });
    
    // Student Promotions
    Route::middleware('permission:view_promotions')->group(function () {
        Route::get('/student-promotions', [StudentPromotionController::class, 'index'])->name('student-promotions.index');
        Route::get('/student-promotions/create', [StudentPromotionController::class, 'create'])->middleware('permission:bulk_promote_students')->name('student-promotions.create');
        Route::post('/student-promotions', [StudentPromotionController::class, 'store'])->middleware('permission:bulk_promote_students')->name('student-promotions.store');
        Route::post('/student-promotions/{promotion}/rollback', [StudentPromotionController::class, 'rollback'])->middleware('permission:rollback_promotions')->name('student-promotions.rollback');
    });
    
    // Fee Type Management
    Route::middleware('permission:view_fee_types')->group(function () {
        Route::get('/fee-types', [FeeTypeController::class, 'index'])->name('fee-types.index');
        Route::get('/fee-types/create', [FeeTypeController::class, 'create'])->middleware('permission:create_fee_types')->name('fee-types.create');
        Route::post('/fee-types', [FeeTypeController::class, 'store'])->middleware('permission:create_fee_types')->name('fee-types.store');
        Route::get('/fee-types/{feeType}', [FeeTypeController::class, 'show'])->name('fee-types.show');
        Route::get('/fee-types/{feeType}/edit', [FeeTypeController::class, 'edit'])->middleware('permission:edit_fee_types')->name('fee-types.edit');
        Route::put('/fee-types/{feeType}', [FeeTypeController::class, 'update'])->middleware('permission:edit_fee_types')->name('fee-types.update');
        Route::delete('/fee-types/{feeType}', [FeeTypeController::class, 'destroy'])->middleware('permission:delete_fee_types')->name('fee-types.destroy');
    });
    
    // Fee Structure Management
    Route::middleware('permission:view_fee_structures')->group(function () {
        Route::get('/fee-structures', [FeeStructureController::class, 'index'])->name('fee-structures.index');
        Route::get('/fee-structures/create', [FeeStructureController::class, 'create'])->middleware('permission:create_fee_structures')->name('fee-structures.create');
        Route::post('/fee-structures', [FeeStructureController::class, 'store'])->middleware('permission:create_fee_structures')->name('fee-structures.store');
        Route::get('/fee-structures/{feeStructure}', [FeeStructureController::class, 'show'])->name('fee-structures.show');
        Route::get('/fee-structures/{feeStructure}/edit', [FeeStructureController::class, 'edit'])->middleware('permission:edit_fee_structures')->name('fee-structures.edit');
        Route::put('/fee-structures/{feeStructure}', [FeeStructureController::class, 'update'])->middleware('permission:edit_fee_structures')->name('fee-structures.update');
        Route::delete('/fee-structures/{feeStructure}', [FeeStructureController::class, 'destroy'])->middleware('permission:delete_fee_structures')->name('fee-structures.destroy');
    });
    
    // Payment Management
    Route::middleware('permission:view_payments')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/create', [PaymentController::class, 'create'])->middleware('permission:create_payments')->name('payments.create');
        Route::post('/payments', [PaymentController::class, 'store'])->middleware('permission:create_payments')->name('payments.store');
        Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
        Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->middleware('permission:delete_payments')->name('payments.destroy');
    });
    
    // Concession Management
    Route::middleware('permission:view_concessions')->group(function () {
        Route::get('/concessions', [ConcessionController::class, 'index'])->name('concessions.index');
        Route::get('/concessions/create', [ConcessionController::class, 'create'])->middleware('permission:create_concessions')->name('concessions.create');
        Route::post('/concessions', [ConcessionController::class, 'store'])->middleware('permission:create_concessions')->name('concessions.store');
        Route::get('/concessions/{concession}', [ConcessionController::class, 'show'])->name('concessions.show');
        Route::post('/concessions/{concession}/approve', [ConcessionController::class, 'approve'])->middleware('permission:approve_concessions')->name('concessions.approve');
        Route::post('/concessions/{concession}/reject', [ConcessionController::class, 'reject'])->middleware('permission:approve_concessions')->name('concessions.reject');
        Route::delete('/concessions/{concession}', [ConcessionController::class, 'destroy'])->middleware('permission:delete_concessions')->name('concessions.destroy');
    });
    
    // Previous Year Balance Management
    Route::middleware('permission:view_previous_balances')->group(function () {
        Route::get('/previous-year-balances', [PreviousYearBalanceController::class, 'index'])->name('previous-year-balances.index');
        Route::get('/previous-year-balances/create', [PreviousYearBalanceController::class, 'create'])->middleware('permission:manage_previous_balances')->name('previous-year-balances.create');
        Route::post('/previous-year-balances', [PreviousYearBalanceController::class, 'store'])->middleware('permission:manage_previous_balances')->name('previous-year-balances.store');
        Route::put('/previous-year-balances/{balance}', [PreviousYearBalanceController::class, 'update'])->middleware('permission:adjust_previous_balances')->name('previous-year-balances.update');
    });
    
    // Transport Route Management
    Route::middleware('permission:view_transport_routes')->group(function () {
        Route::get('/transport-routes', [TransportRouteController::class, 'index'])->name('transport-routes.index');
        Route::get('/transport-routes/create', [TransportRouteController::class, 'create'])->middleware('permission:create_transport_routes')->name('transport-routes.create');
        Route::post('/transport-routes', [TransportRouteController::class, 'store'])->middleware('permission:create_transport_routes')->name('transport-routes.store');
        Route::get('/transport-routes/{transportRoute}', [TransportRouteController::class, 'show'])->name('transport-routes.show');
        Route::get('/transport-routes/{transportRoute}/edit', [TransportRouteController::class, 'edit'])->middleware('permission:edit_transport_routes')->name('transport-routes.edit');
        Route::put('/transport-routes/{transportRoute}', [TransportRouteController::class, 'update'])->middleware('permission:edit_transport_routes')->name('transport-routes.update');
        Route::delete('/transport-routes/{transportRoute}', [TransportRouteController::class, 'destroy'])->middleware('permission:delete_transport_routes')->name('transport-routes.destroy');
    });
    
    // Transport Assignment Management
    Route::middleware('permission:view_transport_assignments')->group(function () {
        Route::get('/transport-assignments', [TransportAssignmentController::class, 'index'])->name('transport-assignments.index');
        Route::get('/transport-assignments/create', [TransportAssignmentController::class, 'create'])->middleware('permission:create_transport_assignments')->name('transport-assignments.create');
        Route::post('/transport-assignments', [TransportAssignmentController::class, 'store'])->middleware('permission:create_transport_assignments')->name('transport-assignments.store');
        Route::delete('/transport-assignments/{assignment}', [TransportAssignmentController::class, 'destroy'])->middleware('permission:delete_transport_assignments')->name('transport-assignments.destroy');
    });
    
    // User Management
    Route::middleware('permission:view_users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:create_users')->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->middleware('permission:create_users')->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:edit_users')->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:edit_users')->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:delete_users')->name('users.destroy');
    });
    
    // Role Management
    Route::middleware('permission:view_users')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->middleware('permission:assign_roles')->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:assign_roles')->name('roles.store');
        Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:assign_roles')->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:assign_roles')->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:assign_roles')->name('roles.destroy');
    });
    
    // Permission Management
    Route::middleware('permission:assign_roles')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    });
    
    // School Management (Super Admin only)
    Route::middleware('permission:view_schools')->group(function () {
        Route::get('/schools', [SchoolController::class, 'index'])->name('schools.index');
        Route::get('/schools/create', [SchoolController::class, 'create'])->middleware('permission:create_schools')->name('schools.create');
        Route::post('/schools', [SchoolController::class, 'store'])->middleware('permission:create_schools')->name('schools.store');
        Route::get('/schools/{school}', [SchoolController::class, 'show'])->name('schools.show');
        Route::get('/schools/{school}/edit', [SchoolController::class, 'edit'])->middleware('permission:edit_schools')->name('schools.edit');
        Route::put('/schools/{school}', [SchoolController::class, 'update'])->middleware('permission:edit_schools')->name('schools.update');
        Route::delete('/schools/{school}', [SchoolController::class, 'destroy'])->middleware('permission:delete_schools')->name('schools.destroy');
    });
    
    // Notifications
    Route::middleware('permission:view_notifications')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->middleware('permission:delete_notifications')->name('notifications.destroy');
    });
    
    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->middleware('permission:view_activity_logs')->name('activity-logs.index');
    
    // Reports
    Route::middleware('permission:view_financial_reports')->group(function () {
        Route::get('/reports/fee-collection', [ReportsController::class, 'feeCollection'])->name('reports.fee-collection');
        Route::get('/reports/student-list', [ReportsController::class, 'studentList'])->name('reports.student-list');
        Route::get('/reports/defaulters', [ReportsController::class, 'defaulters'])->name('reports.defaulters');
        Route::get('/reports/transport', [ReportsController::class, 'transport'])->name('reports.transport');
    });
    
    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->middleware('permission:view_analytics')->name('analytics.index');
    
    // Settings
    Route::middleware('permission:view_settings')->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/update', [SettingController::class, 'update'])->middleware('permission:edit_settings')->name('settings.update');
    });
});

require __DIR__.'/auth.php';
