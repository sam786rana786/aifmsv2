<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\FeeType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class FeeTypeController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('permission:fee_types.view', only: ['index', 'show']),
            new Middleware('permission:fee_types.create', only: ['create', 'store']),
            new Middleware('permission:fee_types.edit', only: ['edit', 'update']),
            new Middleware('permission:fee_types.delete', only: ['destroy']),
            new Middleware('can:view,feeType', only: ['show']),
            new Middleware('can:update,feeType', only: ['edit', 'update']),
            new Middleware('can:delete,feeType', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of fee types.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FeeType::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $feeTypes = FeeType::query()
            ->when($user->school_id && !$user->hasRole('Super Admin'), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->is_active !== null, function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('FeeTypes/Index', [
            'feeTypes' => $feeTypes,
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }

    /**
     * Show the form for creating a new fee type.
     */
    public function create(): Response
    {
        Gate::authorize('create', FeeType::class);

        return Inertia::render('FeeTypes/Create');
    }

    /**
     * Store a newly created fee type.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', FeeType::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'is_recurring' => 'boolean',
            'due_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $user = Auth::user();
        $validated['school_id'] = $user->school_id;

        $feeType = FeeType::create($validated);

        return redirect()->route('fee-types.index')
            ->with('success', 'Fee type created successfully.');
    }

    /**
     * Display the specified fee type.
     */
    public function show(FeeType $feeType): Response
    {
        Gate::authorize('view', $feeType);

        return Inertia::render('FeeTypes/Show', [
            'feeType' => $feeType,
        ]);
    }

    /**
     * Show the form for editing the specified fee type.
     */
    public function edit(FeeType $feeType): Response
    {
        Gate::authorize('update', $feeType);

        return Inertia::render('FeeTypes/Edit', [
            'feeType' => $feeType,
        ]);
    }

    /**
     * Update the specified fee type.
     */
    public function update(Request $request, FeeType $feeType): RedirectResponse
    {
        Gate::authorize('update', $feeType);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'is_recurring' => 'boolean',
            'due_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $feeType->update($validated);

        return redirect()->route('fee-types.index')
            ->with('success', 'Fee type updated successfully.');
    }

    /**
     * Remove the specified fee type.
     */
    public function destroy(FeeType $feeType): RedirectResponse
    {
        Gate::authorize('delete', $feeType);

        // Check if fee type has related payments
        if ($feeType->payments()->exists()) {
            return redirect()->route('fee-types.index')
                ->with('error', 'Cannot delete fee type with existing payments.');
        }

        $feeType->delete();

        return redirect()->route('fee-types.index')
            ->with('success', 'Fee type deleted successfully.');
    }
}
