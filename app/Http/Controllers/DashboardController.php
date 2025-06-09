<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Concession;
use App\Models\ActivityLog;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(): Response
    {
        /** @var \App\Models\User $user */  
        $user = Auth::user();
        
        // Get school-specific data for non-super admins
        $schoolId = $user->roles->contains('name', 'Super Admin') ? null : $user->school_id;
        
        // Calculate statistics
        $stats = [
            'total_students' => $this->getTotalStudents($schoolId),
            'students_change' => $this->getStudentsChange($schoolId),
            'total_collection' => $this->getTotalCollection($schoolId),
            'collection_change' => $this->getCollectionChange($schoolId),
            'pending_fees' => $this->getPendingFees($schoolId),
            'defaulter_count' => $this->getDefaulterCount($schoolId),
            'active_classes' => $this->getActiveClasses($schoolId),
            'academic_years' => $this->getAcademicYears($schoolId),
        ];
        
        // Get recent activities
        $recentActivities = $this->getRecentActivities($schoolId);
        
        // Get recent payments
        $recentPayments = $this->getRecentPayments($schoolId);
        
        // Get pending approvals
        $pendingApprovals = $this->getPendingApprovals($schoolId);
        
        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'recentPayments' => $recentPayments,
            'pendingApprovals' => $pendingApprovals,
        ]);
    }
    
    private function getTotalStudents($schoolId): int
    {
        return Student::when($schoolId, function ($query) use ($schoolId) {
            $query->where('school_id', $schoolId);
        })->count();
    }
    
    private function getStudentsChange($schoolId): float
    {
        // Calculate percentage change from last month
        $currentMonth = Student::when($schoolId, function ($query) use ($schoolId) {
            $query->where('school_id', $schoolId);
        })->whereMonth('created_at', now()->month)->count();
        
        $lastMonth = Student::when($schoolId, function ($query) use ($schoolId) {
            $query->where('school_id', $schoolId);
        })->whereMonth('created_at', now()->subMonth()->month)->count();
        
        if ($lastMonth == 0) return $currentMonth > 0 ? 100 : 0;
        
        return round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1);
    }
    
    private function getTotalCollection($schoolId): float
    {
        return Payment::when($schoolId, function ($query) use ($schoolId) {
            $query->whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        })->whereMonth('created_at', now()->month)->sum('amount');
    }
    
    private function getCollectionChange($schoolId): float
    {
        $currentMonth = Payment::when($schoolId, function ($query) use ($schoolId) {
            $query->whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        })->whereMonth('created_at', now()->month)->sum('amount');
        
        $lastMonth = Payment::when($schoolId, function ($query) use ($schoolId) {
            $query->whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        })->whereMonth('created_at', now()->subMonth()->month)->sum('amount');
        
        if ($lastMonth == 0) return $currentMonth > 0 ? 100 : 0;
        
        return round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1);
    }
    
    private function getPendingFees($schoolId): float
    {
        // This would be calculated based on fee structures vs payments
        // For now, return a placeholder
        return 50000;
    }
    
    private function getDefaulterCount($schoolId): int
    {
        // Students with pending fees - simplified calculation
        return Student::when($schoolId, function ($query) use ($schoolId) {
            $query->where('school_id', $schoolId);
        })->whereDoesntHave('payments', function ($query) {
            $query->whereMonth('created_at', now()->month);
        })->count();
    }
    
    private function getActiveClasses($schoolId): int
    {
        return SchoolClass::when($schoolId, function ($query) use ($schoolId) {
            $query->where('school_id', $schoolId);
        })->where('is_active', true)->count();
    }
    
    private function getAcademicYears($schoolId): int
    {
        return AcademicYear::when($schoolId, function ($query) use ($schoolId) {
            $query->where('school_id', $schoolId);
        })->count();
    }
    
    private function getRecentActivities($schoolId): array
    {
        return ActivityLog::with('user')
            ->when($schoolId, function ($query) use ($schoolId) {
                $query->whereHas('user', function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }
    
    private function getRecentPayments($schoolId): array
    {
        return Payment::with(['student', 'feeType'])
            ->when($schoolId, function ($query) use ($schoolId) {
                $query->whereHas('student', function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }
    
    private function getPendingApprovals($schoolId): array
    {
        $approvals = [];
        
        // Add pending concessions
        $pendingConcessions = Concession::where('status', 'pending')
            ->when($schoolId, function ($query) use ($schoolId) {
                $query->whereHas('student', function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId);
                });
            })
            ->limit(3)
            ->get();
            
        foreach ($pendingConcessions as $concession) {
            $approvals[] = [
                'id' => $concession->id,
                'type' => 'concession',
                'title' => 'Fee Concession Request',
                'description' => "Concession of {$concession->amount} for student",
            ];
        }
        
        return $approvals;
    }
} 