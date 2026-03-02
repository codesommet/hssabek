<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Billing\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * List all plans.
     */
    public function index()
    {
        $plans = Plan::orderBy('price')->get();

        return view('backoffice.plans.index', compact('plans'));
    }

    /**
     * Show form to create a new plan.
     */
    public function create()
    {
        return view('backoffice.plans.create');
    }

    /**
     * Store a new plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:plans,code',
            'interval' => 'required|in:month,year,lifetime',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'trial_days' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'features' => 'nullable|array',
        ]);

        $plan = Plan::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'interval' => $validated['interval'],
            'price' => $validated['price'],
            'currency' => $validated['currency'] ?? 'MAD',
            'trial_days' => $validated['trial_days'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
            'features' => $validated['features'] ?? null,
        ]);

        return redirect()->route('sa.plans.index')
            ->with('success', "Plan '{$plan->name}' created successfully.");
    }

    /**
     * Show a single plan.
     */
    public function show(Plan $plan)
    {
        $plan->loadCount('subscriptions');

        return view('backoffice.plans.show', compact('plan'));
    }

    /**
     * Show form to edit a plan.
     */
    public function edit(Plan $plan)
    {
        return view('backoffice.plans.edit', compact('plan'));
    }

    /**
     * Update a plan.
     */
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:plans,code,' . $plan->id,
            'interval' => 'required|in:month,year,lifetime',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'trial_days' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'features' => 'nullable|array',
        ]);

        $plan->update($validated);

        return redirect()->route('sa.plans.index')
            ->with('success', "Plan '{$plan->name}' updated successfully.");
    }

    /**
     * Delete a plan.
     */
    public function destroy(Plan $plan)
    {
        $name = $plan->name;
        $plan->delete();

        return redirect()->route('sa.plans.index')
            ->with('success', "Plan '{$name}' deleted successfully.");
    }
}
