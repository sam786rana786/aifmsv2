<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'transport_route_id',
        'academic_year_id',
        'school_id',
        'payment_frequency',
        'start_date',
        'end_date',
        'pickup_point',
        'drop_point',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function transportRoute()
    {
        return $this->belongsTo(TransportRoute::class);
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
