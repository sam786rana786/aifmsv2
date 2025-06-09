<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Fee extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'fee_structure_id',
        'academic_year_id',
        'school_id',
        'amount',
        'discount_amount',
        'fine_amount',
        'total_amount',
        'due_date',
        'status',
        'payment_status',
        'description',
        'fee_category',
        'fee_type',
        'installment_number',
        'installment_of',
        'waiver_amount',
        'waiver_reason',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
        'paid_at',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'fine_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'waiver_amount' => 'decimal:2',
            'due_date' => 'date',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'installment_number' => 'integer',
            'installment_of' => 'integer',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_by',
        'updated_by',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate total amount when creating/updating
        static::saving(function ($fee) {
            $fee->total_amount = $fee->amount + $fee->fine_amount - $fee->discount_amount - $fee->waiver_amount;
        });

        // Set payment status based on payments
        static::saved(function ($fee) {
            $fee->updatePaymentStatus();
        });
    }

    /**
     * Get the student that owns the fee.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the fee structure that owns the fee.
     */
    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    /**
     * Get the academic year that owns the fee.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the school that owns the fee.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the user who created the fee.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the fee.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who approved the fee.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the payments for the fee.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get successful payments for the fee.
     */
    public function successfulPayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('status', 'completed');
    }

    /**
     * Scope a query to only include fees for a specific school.
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope a query to only include pending fees.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include overdue fees.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')->where('due_date', '<', now());
    }

    /**
     * Scope a query to only include completed fees.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include fees for a specific academic year.
     */
    public function scopeForAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    /**
     * Scope a query to only include fees for a specific student.
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope a query to only include fees of a specific category.
     */
    public function scopeOfCategory($query, $category)
    {
        return $query->where('fee_category', $category);
    }

    /**
     * Get the formatted amount attribute.
     */
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->amount, 2),
        );
    }

    /**
     * Get the formatted total amount attribute.
     */
    protected function formattedTotalAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->total_amount, 2),
        );
    }

    /**
     * Get the remaining amount attribute.
     */
    protected function remainingAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                $paidAmount = $this->payments()->where('status', 'completed')->sum('amount');
                return max(0, $this->total_amount - $paidAmount);
            },
        );
    }

    /**
     * Get the paid amount attribute.
     */
    protected function paidAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payments()->where('status', 'completed')->sum('amount'),
        );
    }

    /**
     * Check if the fee is overdue.
     */
    protected function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->due_date && $this->due_date->isPast() && $this->status !== 'completed',
        );
    }

    /**
     * Check if the fee is fully paid.
     */
    protected function isFullyPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->remaining_amount <= 0,
        );
    }

    /**
     * Check if the fee is partially paid.
     */
    protected function isPartiallyPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->paid_amount > 0 && $this->remaining_amount > 0,
        );
    }

    /**
     * Get the status color for UI display.
     */
    protected function statusColor(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->status) {
                    'pending' => 'warning',
                    'overdue' => 'danger',
                    'completed' => 'success',
                    'cancelled' => 'secondary',
                    'waived' => 'info',
                    default => 'primary',
                };
            },
        );
    }

    /**
     * Update payment status based on payments.
     */
    public function updatePaymentStatus(): void
    {
        $paidAmount = $this->payments()->where('status', 'completed')->sum('amount');
        $remainingAmount = $this->total_amount - $paidAmount;

        if ($remainingAmount <= 0) {
            $this->payment_status = 'fully_paid';
            $this->status = 'completed';
            $this->paid_at = now();
        } elseif ($paidAmount > 0) {
            $this->payment_status = 'partially_paid';
        } else {
            $this->payment_status = 'unpaid';
        }

        // Check if overdue
        if ($this->due_date && $this->due_date->isPast() && $this->status !== 'completed') {
            $this->status = 'overdue';
        }

        $this->saveQuietly(); // Save without triggering events to avoid recursion
    }

    /**
     * Apply discount to the fee.
     */
    public function applyDiscount(float $amount, string $reason = null): bool
    {
        if ($amount < 0 || $amount > $this->amount) {
            return false;
        }

        $this->discount_amount = $amount;
        if ($reason) {
            $this->remarks = ($this->remarks ? $this->remarks . "\n" : '') . "Discount applied: {$reason}";
        }

        return $this->save();
    }

    /**
     * Apply fine to the fee.
     */
    public function applyFine(float $amount, string $reason = null): bool
    {
        if ($amount < 0) {
            return false;
        }

        $this->fine_amount += $amount;
        if ($reason) {
            $this->remarks = ($this->remarks ? $this->remarks . "\n" : '') . "Fine applied: {$reason}";
        }

        return $this->save();
    }

    /**
     * Apply waiver to the fee.
     */
    public function applyWaiver(float $amount, string $reason): bool
    {
        if ($amount < 0 || $amount > $this->amount) {
            return false;
        }

        $this->waiver_amount = $amount;
        $this->waiver_reason = $reason;
        $this->remarks = ($this->remarks ? $this->remarks . "\n" : '') . "Waiver applied: {$reason}";

        return $this->save();
    }

    /**
     * Mark fee as cancelled.
     */
    public function cancel(string $reason): bool
    {
        $this->status = 'cancelled';
        $this->remarks = ($this->remarks ? $this->remarks . "\n" : '') . "Cancelled: {$reason}";

        return $this->save();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Get the fee status options.
     */
    public static function getStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'overdue' => 'Overdue',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'waived' => 'Waived',
        ];
    }

    /**
     * Get the payment status options.
     */
    public static function getPaymentStatusOptions(): array
    {
        return [
            'unpaid' => 'Unpaid',
            'partially_paid' => 'Partially Paid',
            'fully_paid' => 'Fully Paid',
        ];
    }

    /**
     * Get the fee category options.
     */
    public static function getCategoryOptions(): array
    {
        return [
            'tuition' => 'Tuition Fee',
            'admission' => 'Admission Fee',
            'library' => 'Library Fee',
            'laboratory' => 'Laboratory Fee',
            'sports' => 'Sports Fee',
            'transport' => 'Transport Fee',
            'examination' => 'Examination Fee',
            'development' => 'Development Fee',
            'miscellaneous' => 'Miscellaneous',
        ];
    }

    /**
     * Get the fee type options.
     */
    public static function getTypeOptions(): array
    {
        return [
            'mandatory' => 'Mandatory',
            'optional' => 'Optional',
            'conditional' => 'Conditional',
        ];
    }
}
