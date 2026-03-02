<?php

namespace App\Http\Middleware;

use App\Models\Tenancy\TenantDomain;
use App\Models\Tenancy\Tenant;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;

class IdentifyTenantByDomain
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        // Include port for matching (dev environments often use localhost:8000)
        $hostWithPort = $request->getHttpHost(); // e.g. localhost:8000

        // Try exact domain match first (with port, then without)
        $tenantDomain = TenantDomain::where('domain', $hostWithPort)->first()
            ?? TenantDomain::where('domain', $host)->first();

        if ($tenantDomain) {
            $tenant = $tenantDomain->tenant;
        } else {
            // Fallback: try to find tenant by subdomain slug
            $parts = explode('.', $host);
            $tenant = (count($parts) >= 2)
                ? Tenant::where('slug', $parts[0])->first()
                : null;
        }

        if ($tenant) {
            // Bind tenant into TenantContext singleton
            TenantContext::set($tenant);

            // Also bind into the container and request for backward compatibility
            app()->instance('tenant', $tenant);
            $request->attributes->set('tenant', $tenant);
        } elseif ($request->is('backoffice/*')) {
            // Hard stop: no tenant found for this domain on a backoffice route
            abort(404, 'Domaine non reconnu.');
        }

        return $next($request);
    }
}
