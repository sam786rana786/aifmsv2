<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Payment;
use App\Models\Student;
use App\Models\FeeType;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PaymentController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:payments.view', only: ['index', 'show']),
            new Middleware('permission:payments.create', only: ['create', 'store']),
            new Middleware('permission:payments.edit', only: ['edit', 'update']),
            new Middleware('permission:payments.delete', only: ['destroy']),
            new Middleware('can:view,payment', only: ['show']),
            new Middleware('can:update,payment', only: ['edit', 'update']),
            new Middleware('can:delete,payment', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of payments.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Payment::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $payments = Payment::query()
            ->with(['student', 'feeType', 'academicYear', 'collectedBy'])
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->when($request->search, function ($query, $search) {
                $query->whereHas('student', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('admission_no', 'like', "%{$search}%");
                })->orWhere('receipt_number', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->fee_type_id, function ($query, $feeTypeId) {
                $query->where('fee_type_id', $feeTypeId);
            })
            ->when($request->academic_year_id, function ($query, $academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Get filter options
        $feeTypes = FeeType::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $academicYears = AcademicYear::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name']);

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'feeTypes' => $feeTypes,
            'academicYears' => $academicYears,
            'filters' => $request->only(['search', 'status', 'fee_type_id', 'academic_year_id']),
        ]);
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create(): Response
    {
        Gate::authorize('create', Payment::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $students = Student::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no']);

        $feeTypes = FeeType::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'amount']);

        $academicYears = AcademicYear::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name']);

        return Inertia::render('Payments/Create', [
            'students' => $students,
            'feeTypes' => $feeTypes,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Payment::class);

        $validated = $request->validate([
            'receipt_number' => 'required|string|max:50|unique:payments,receipt_number',
            'amount' => 'required|numeric|min:0',
            'late_fee' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,cheque,bank_transfer,upi,card',
            'transaction_id' => 'nullable|string|max:100',
            'cheque_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'payment_date' => 'required|date',
            'remarks' => 'nullable|string',
            'student_id' => 'required|exists:students,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'status' => 'required|in:pending,paid,cancelled',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $validated['school_id'] = $user->school_id;
        $validated['collected_by'] = $user->id;

        if ($validated['status'] === 'paid') {
            $validated['processed_at'] = now();
        }

        $payment = Payment::create($validated);

        return redirect()->route('payments.index')
            ->with('success', 'Payment created successfully.');
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): Response
    {
        Gate::authorize('view', $payment);

        $payment->load(['student', 'feeType', 'academicYear', 'school', 'collectedBy']);

        return Inertia::render('Payments/Show', [
            'payment' => $payment,
        ]);
    }

    /**
     * Show the form for editing the specified payment.
     */
    public function edit(Payment $payment): Response
    {
        Gate::authorize('update', $payment);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $students = Student::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no']);

        $feeTypes = FeeType::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'amount']);

        $academicYears = AcademicYear::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name']);

        return Inertia::render('Payments/Edit', [
            'payment' => $payment,
            'students' => $students,
            'feeTypes' => $feeTypes,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Update the specified payment.
     */
    public function update(Request $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('update', $payment);

        $validated = $request->validate([
            'receipt_number' => 'required|string|max:50|unique:payments,receipt_number,' . $payment->id,
            'amount' => 'required|numeric|min:0',
            'late_fee' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,cheque,bank_transfer,upi,card',
            'transaction_id' => 'nullable|string|max:100',
            'cheque_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'payment_date' => 'required|date',
            'remarks' => 'nullable|string',
            'student_id' => 'required|exists:students,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'status' => 'required|in:pending,paid,cancelled',
        ]);

        // Update processed_at when status changes to paid
        if ($validated['status'] === 'paid' && $payment->status !== 'paid') {
            $validated['processed_at'] = now();
        }

        $payment->update($validated);

        return redirect()->route('payments.index')
            ->with('success', 'Payment updated successfully.');
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(Payment $payment): RedirectResponse
    {
        Gate::authorize('delete', $payment);

        // Only allow deletion of pending payments
        if ($payment->status === 'paid') {
            return redirect()->route('payments.index')
                ->with('error', 'Cannot delete a processed payment.');
        }

        $payment->delete();

        return redirect()->route('payments.index')
            ->with('success', 'Payment deleted successfully.');
    }
}
