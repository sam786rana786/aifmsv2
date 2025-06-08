<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'frequency',
        'is_optional',
        'has_late_fee',
        'late_fee_amount',
        'late_fee_grace_days',
        'is_active',
        'school_id'
    ];

    protected $casts = [
        'is_optional' => 'boolean',
        'has_late_fee' => 'boolean',
        'late_fee_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }

    public function concessions()
    {
        return $this->hasMany(Concession::class);
    }
}
