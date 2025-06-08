<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeStructure extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fee_type_id',
        'class_id',
        'academic_year_id',
        'school_id',
        'amount',
        'due_date',
        'description',
        'is_active'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'is_active' => 'boolean'
    ];

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
