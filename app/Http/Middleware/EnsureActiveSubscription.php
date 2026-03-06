<?php

namespace App\Http\Middleware;

use App\Models\Billing\Subscription;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = TenantContext::get();

        if (!$tenant) {
            return $next($request);
        }

        // Allow access to settings/billing pages so user can subscribe
        if ($request->routeIs('bo.settings.plans-billings*', 'bo.settings.plans-billings.*', 'bo.billing.*')) {
            return $next($request);
        }

        // Check for an active subscription in the database
        // A valid free trial on the tenant level alone is NOT enough — a subscription record is required
        $hasActiveSubscription = Subscription::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->exists();

        if (!$hasActiveSubscription) {
            return response()->view('errors.no-subscription', [
                'tenant' => $tenant,
            ], 403);
        }

        return $next($request);
    }
}
