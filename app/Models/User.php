<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method bool hasRole(string $role)
 * @method bool hasPermissionTo(string $permission)
 * @method bool hasAnyRole(array $roles)
 * @method bool hasAllRoles(array $roles)
 */

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'employee_id',
        'profile_picture',
        'school_id',
        'is_active',
        'last_login_at',
        'login_count'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'login_count' => 'integer'
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function collectedPayments()
    {
        return $this->hasMany(Payment::class, 'collected_by');
    }

    public function createdConcessions()
    {
        return $this->hasMany(Concession::class, 'created_by');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }
}
