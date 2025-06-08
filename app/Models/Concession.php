<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Concession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'calculation_type',
        'value',
        'description',
        'valid_from',
        'valid_until',
        'requires_approval',
        'status',
        'rejection_reason',
        'student_id',
        'fee_type_id',
        'academic_year_id',
        'school_id',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
