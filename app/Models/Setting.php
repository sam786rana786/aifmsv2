<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'key',
        'value',
        'type',
        'school_id',
        'category',
        'description',
        'is_encrypted',
        'is_public'
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'is_public' => 'boolean',
        'value' => 'json'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public static function get($key, $default = null, $schoolId = null)
    {
        $setting = self::where('key', $key)
            ->when($schoolId, function ($query) use ($schoolId) {
                return $query->where('school_id', $schoolId);
            })
            ->first();

        return $setting ? $setting->value : $default;
    }

    public static function set($key, $value, $schoolId = null, $type = 'string')
    {
        return self::updateOrCreate(
            ['key' => $key, 'school_id' => $schoolId],
            ['value' => $value, 'type' => $type]
        );
    }
} 