<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'affiliation_number',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'phone',
        'email',
        'website',
        'logo_path',
        'favicon_path',
        'currency_code',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function academicYears()
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function feeTypes()
    {
        return $this->hasMany(FeeType::class);
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function concessions()
    {
        return $this->hasMany(Concession::class);
    }

    public function transportRoutes()
    {
        return $this->hasMany(TransportRoute::class);
    }

    public function transportAssignments()
    {
        return $this->hasMany(TransportAssignment::class);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function studentPromotions()
    {
        return $this->hasMany(StudentPromotion::class);
    }

    public function previousYearBalances()
    {
        return $this->hasMany(PreviousYearBalance::class);
    }
}
