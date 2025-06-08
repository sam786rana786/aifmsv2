<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportRoute extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'distance',
        'monthly_fee',
        'quarterly_fee',
        'annual_fee',
        'capacity',
        'vehicle_number',
        'driver_name',
        'driver_phone',
        'pickup_time',
        'drop_time',
        'is_active',
        'school_id'
    ];

    protected $casts = [
        'distance' => 'decimal:2',
        'monthly_fee' => 'decimal:2',
        'quarterly_fee' => 'decimal:2',
        'annual_fee' => 'decimal:2',
        'pickup_time' => 'datetime',
        'drop_time' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function transportAssignments()
    {
        return $this->hasMany(TransportAssignment::class);
    }
}
