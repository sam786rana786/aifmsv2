<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'admission_no',
        'roll_no',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'blood_group',
        'religion',
        'caste',
        'nationality',
        'aadhar_number',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'father_name',
        'father_phone',
        'father_occupation',
        'mother_name',
        'mother_phone',
        'mother_occupation',
        'guardian_name',
        'guardian_phone',
        'guardian_occupation',
        'guardian_relation',
        'photo_path',
        'admission_date',
        'previous_school',
        'previous_qualification',
        'documents',
        'is_active',
        'school_id',
        'academic_year_id',
        'class_id'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'is_active' => 'boolean',
        'documents' => 'array'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function concessions()
    {
        return $this->hasMany(Concession::class);
    }

    public function transportAssignments()
    {
        return $this->hasMany(TransportAssignment::class);
    }

    public function promotions()
    {
        return $this->hasMany(StudentPromotion::class);
    }

    public function previousYearBalances()
    {
        return $this->hasMany(PreviousYearBalance::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'recipient');
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()->where('status', 'paid')->sum('amount');
    }

    public function getTotalDueAttribute()
    {
        $totalFees = $this->class->feeStructures()
            ->where('academic_year_id', $this->academic_year_id)
            ->sum('amount');
        
        $totalPaid = $this->getTotalPaidAttribute();
        $totalConcessions = $this->concessions()
            ->where('status', 'approved')
            ->where('academic_year_id', $this->academic_year_id)
            ->sum('value');

        return $totalFees - $totalPaid - $totalConcessions;
    }
}
