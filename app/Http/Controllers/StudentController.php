<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Student;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StudentController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:students.view', only: ['index', 'show']),
            new Middleware('permission:students.create', only: ['create', 'store']),
            new Middleware('permission:students.edit', only: ['edit', 'update']),
            new Middleware('permission:students.delete', only: ['destroy']),
            new Middleware('can:view,student', only: ['show']),
            new Middleware('can:update,student', only: ['edit', 'update']),
            new Middleware('can:delete,student', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of students.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Student::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $students = Student::query()
            ->with(['school', 'academicYear', 'class'])
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('admission_no', 'like', "%{$search}%")
                        ->orWhere('roll_no', 'like', "%{$search}%");
                });
            })
            ->when($request->class_id, function ($query, $classId) {
                $query->where('class_id', $classId);
            })
            ->when($request->academic_year_id, function ($query, $academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        // Get filter options
        $classes = SchoolClass::query()
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

        return Inertia::render('Students/Index', [
            'students' => $students,
            'classes' => $classes,
            'academicYears' => $academicYears,
            'filters' => $request->only(['search', 'class_id', 'academic_year_id']),
        ]);
    }

    /**
     * Show the form for creating a new student.
     */
    public function create(): Response
    {
        Gate::authorize('create', Student::class);
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schools = $user->hasRole('Super Admin') 
            ? School::where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : collect([$user->school]);

        $classes = SchoolClass::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'school_id']);

        $academicYears = AcademicYear::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name', 'school_id']);

        return Inertia::render('Students/Create', [
            'schools' => $schools,
            'classes' => $classes,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Store a newly created student.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        $validated = $request->validate([
            'admission_no' => 'required|string|max:50|unique:students,admission_no',
            'roll_no' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date',
            'blood_group' => 'nullable|string|max:10',
            'religion' => 'nullable|string|max:50',
            'caste' => 'nullable|string|max:50',
            'nationality' => 'required|string|max:50',
            'aadhar_number' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:students,email',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'pincode' => 'required|string|max:20',
            'father_name' => 'required|string|max:100',
            'father_phone' => 'required|string|max:20',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'required|string|max:100',
            'mother_phone' => 'nullable|string|max:20',
            'mother_occupation' => 'nullable|string|max:100',
            'guardian_name' => 'nullable|string|max:100',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_occupation' => 'nullable|string|max:100',
            'guardian_relation' => 'nullable|string|max:50',
            'admission_date' => 'required|date',
            'previous_school' => 'nullable|string|max:255',
            'previous_qualification' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'school_id' => 'sometimes|exists:schools,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:school_classes,id',
        ]);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Determine school_id based on user role
        $schoolId = $user->hasRole('Super Admin') && $request->has('school_id')
            ? $validated['school_id']
            : $user->school_id;

        $validated['school_id'] = $schoolId;

        $student = Student::create($validated);

        return redirect()->route('students.index')
            ->with('success', 'Student created successfully.');
    }

    /**
     * Display the specified student.
     */
    public function show(Student $student): Response
    {
        Gate::authorize('view', $student);

        $student->load([
            'school', 
            'academicYear', 
            'class', 
            'payments.feeType',
            'concessions.feeType',
            'transportAssignments.transportRoute',
            'promotions',
            'previousYearBalances'
        ]);

        return Inertia::render('Students/Show', [
            'student' => $student,
        ]);
    }

    /**
     * Show the form for editing the specified student.
     */
    public function edit(Student $student): Response
    {
        Gate::authorize('update', $student);
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $schools = $user->hasRole('Super Admin') 
            ? School::where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : collect([$student->school]);

        $classes = SchoolClass::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'school_id']);

        $academicYears = AcademicYear::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name', 'school_id']);

        return Inertia::render('Students/Edit', [
            'student' => $student,
            'schools' => $schools,
            'classes' => $classes,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Update the specified student.
     */
    public function update(Request $request, Student $student): RedirectResponse
    {
        Gate::authorize('update', $student);

        $validated = $request->validate([
            'admission_no' => 'required|string|max:50|unique:students,admission_no,' . $student->id,
            'roll_no' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date',
            'blood_group' => 'nullable|string|max:10',
            'religion' => 'nullable|string|max:50',
            'caste' => 'nullable|string|max:50',
            'nationality' => 'required|string|max:50',
            'aadhar_number' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:students,email,' . $student->id,
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'pincode' => 'required|string|max:20',
            'father_name' => 'required|string|max:100',
            'father_phone' => 'required|string|max:20',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'required|string|max:100',
            'mother_phone' => 'nullable|string|max:20',
            'mother_occupation' => 'nullable|string|max:100',
            'guardian_name' => 'nullable|string|max:100',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_occupation' => 'nullable|string|max:100',
            'guardian_relation' => 'nullable|string|max:50',
            'admission_date' => 'required|date',
            'previous_school' => 'nullable|string|max:255',
            'previous_qualification' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:school_classes,id',
        ]);

        $student->update($validated);

        return redirect()->route('students.index')
            ->with('success', 'Student updated successfully.');
    }

    /**
     * Remove the specified student.
     */
    public function destroy(Student $student): RedirectResponse
    {
        Gate::authorize('delete', $student);

        // Check if student has payments or other related data
        if ($student->payments()->exists()) {
            return redirect()->route('students.index')
                ->with('error', 'Cannot delete student with payment records.');
        }

        $student->delete();

        return redirect()->route('students.index')
            ->with('success', 'Student deleted successfully.');
    }
}
