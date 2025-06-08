<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreviousYearBalance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'previous_academic_year_id',
        'school_id',
        'balance_amount',
        'adjustment_amount',
        'final_balance',
        'status',
        'remarks',
        'processed_by',
        'processed_at'
    ];

    protected $casts = [
        'balance_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'final_balance' => 'decimal:2',
        'processed_at' => 'datetime'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function previousAcademicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'previous_academic_year_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
} 