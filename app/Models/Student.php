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
        'photo',
        'is_active',
        'school_id',
        'academic_year_id',
        'class_id'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean'
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

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
