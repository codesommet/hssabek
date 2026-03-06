<?php

namespace App\Http\Controllers\Backoffice\Billing;

use App\Http\Controllers\Controller;

/**
 * Scaffold placeholder — NOT routed.
 * Plans are managed exclusively via SuperAdmin\PlanController.
 * TODO: Remove this file if confirmed unused.
 */
class PlanController extends Controller
{
    public function __construct()
    {
        abort(403, 'Plans are managed by SuperAdmin only.');
    }
}
