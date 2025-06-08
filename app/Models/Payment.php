<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'receipt_number',
        'amount',
        'late_fee',
        'payment_method',
        'transaction_id',
        'cheque_number',
        'bank_name',
        'payment_date',
        'remarks',
        'student_id',
        'fee_type_id',
        'academic_year_id',
        'school_id',
        'collected_by',
        'status',
        'processed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'payment_date' => 'date',
        'processed_at' => 'datetime'
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

    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
