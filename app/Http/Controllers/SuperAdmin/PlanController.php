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
        $plans = Plan::withCount('subscriptions')
            ->orderBy('price')
            ->get();

        $totalPlans = Plan::count();
        $activePlans = Plan::where('is_active', true)->count();
        $inactivePlans = Plan::where('is_active', false)->count();
        $totalSubscribers = \App\Models\Billing\Subscription::where('status', 'active')->count();

        return view('backoffice.plans.index', compact(
            'plans',
            'totalPlans',
            'activePlans',
            'inactivePlans',
            'totalSubscribers'
        ));
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
            'is_active' => 'nullable|boolean',
            'features' => 'nullable|array',
        ]);

        Plan::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'interval' => $validated['interval'],
            'price' => $validated['price'],
            'currency' => $validated['currency'] ?? 'MAD',
            'trial_days' => $validated['trial_days'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'features' => $validated['features'] ?? null,
        ]);

        return redirect()->route('sa.plans.index')
            ->with('success', 'Le plan a été créé avec succès.');
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
            'is_active' => 'nullable|boolean',
            'features' => 'nullable|array',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $plan->update($validated);

        return redirect()->route('sa.plans.index')
            ->with('success', "Le plan « {$plan->name} » a été mis à jour avec succès.");
    }

    /**
     * Delete a plan.
     */
    public function destroy(Plan $plan)
    {
        $name = $plan->name;
        $plan->delete();

        return redirect()->route('sa.plans.index')
            ->with('success', "Le plan « {$name} » a été supprimé avec succès.");
    }
}
