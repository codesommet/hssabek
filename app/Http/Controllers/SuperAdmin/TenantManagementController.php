<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenancy\Tenant;
use App\Models\Tenancy\TenantDomain;
use Illuminate\Http\Request;

class TenantManagementController extends Controller
{
    /**
     * List all tenants.
     */
    public function index(Request $request)
    {
        $tenants = Tenant::with('domains')
            ->withCount('users')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('backoffice.tenants.index', compact('tenants'));
    }

    /**
     * Show form to create a new tenant.
     */
    public function create()
    {
        return view('backoffice.tenants.create');
    }

    /**
     * Store a new tenant.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:tenants,slug|alpha_dash',
            'domain' => 'required|string|max:255|unique:tenant_domains,domain',
            'status' => 'required|in:active,suspended,cancelled',
            'timezone' => 'nullable|string|max:50',
            'default_currency' => 'nullable|string|size:3',
        ]);

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'status' => $validated['status'],
            'timezone' => $validated['timezone'] ?? 'Africa/Casablanca',
            'default_currency' => $validated['default_currency'] ?? 'MAD',
        ]);

        // Create primary domain
        $tenant->domains()->create([
            'domain' => $validated['domain'],
            'is_primary' => true,
        ]);

        return redirect()->route('sa.tenants.index')
            ->with('success', "Tenant '{$tenant->name}' created successfully.");
    }

    /**
     * Show a single tenant.
     */
    public function show(Tenant $tenant)
    {
        $tenant->load('domains', 'users', 'settings');

        return view('backoffice.tenants.show', compact('tenant'));
    }

    /**
     * Show form to edit a tenant.
     */
    public function edit(Tenant $tenant)
    {
        return view('backoffice.tenants.edit', compact('tenant'));
    }

    /**
     * Update a tenant.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|alpha_dash|unique:tenants,slug,' . $tenant->id,
            'status' => 'required|in:active,suspended,cancelled',
            'timezone' => 'nullable|string|max:50',
            'default_currency' => 'nullable|string|size:3',
        ]);

        $tenant->update($validated);

        return redirect()->route('sa.tenants.index')
            ->with('success', "Tenant '{$tenant->name}' updated successfully.");
    }

    /**
     * Delete a tenant.
     */
    public function destroy(Tenant $tenant)
    {
        $name = $tenant->name;
        $tenant->delete();

        return redirect()->route('sa.tenants.index')
            ->with('success', "Tenant '{$name}' deleted successfully.");
    }

    /**
     * Suspend a tenant.
     */
    public function suspend(Tenant $tenant)
    {
        $tenant->update(['status' => 'suspended']);

        return redirect()->route('sa.tenants.index')
            ->with('success', "Tenant '{$tenant->name}' has been suspended.");
    }

    /**
     * Activate a tenant.
     */
    public function activate(Tenant $tenant)
    {
        $tenant->update(['status' => 'active']);

        return redirect()->route('sa.tenants.index')
            ->with('success', "Tenant '{$tenant->name}' has been activated.");
    }
}
