<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AcademicYearPolicy
{
    /**
     * Perform pre-authorization checks on the model.
     * Grant super-admin all permissions before other checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
 
        return null; // Let other authorization methods handle the check
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('academic_years.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AcademicYear $academicYear): bool
    {
        if (!$user->can('academic_years.view')) {
            return false;
        }

        // Users can only view academic years from their school
        // (Super Admin check is handled in before() method)
        return $user->school_id === $academicYear->school_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('academic_years.create') && $user->school_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AcademicYear $academicYear): bool
    {
        if (!$user->can('academic_years.edit')) {
            return false;
        }

        // Users can only update academic years from their school
        // (Super Admin check is handled in before() method)
        return $user->school_id === $academicYear->school_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AcademicYear $academicYear): bool
    {
        if (!$user->can('academic_years.delete')) {
            return false;
        }

        // Cannot delete current academic year
        if ($academicYear->is_current) {
            return false;
        }

        // Users can only delete academic years from their school
        // (Super Admin check is handled in before() method)
        return $user->school_id === $academicYear->school_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AcademicYear $academicYear): bool
    {
        return $this->update($user, $academicYear);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasRole('Super Admin');
    }
}
