<?php

namespace App\Http\Middleware;

use App\Services\Billing\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimit
{
    public function __construct(private readonly PlanLimitService $limitService)
    {
    }

    /**
     * Handle an incoming request.
     *
     * Usage in routes: ->middleware('plan.limit:quotes_per_month')
     *
     * @param string $resource The resource key (users, customers, products, invoices_per_month, etc.)
     */
    public function handle(Request $request, Closure $next, string $resource): Response
    {
        if (!$this->limitService->canCreate($resource)) {
            $message = $this->limitService->getLimitMessage($resource);

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
