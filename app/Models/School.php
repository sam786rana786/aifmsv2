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
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'phone',
        'email',
        'website',
        'logo',
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
}
