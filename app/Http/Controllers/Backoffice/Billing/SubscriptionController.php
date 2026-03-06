<?php

namespace App\Http\Controllers\Backoffice\Billing;

use App\Http\Controllers\Controller;

/**
 * Scaffold placeholder — NOT routed.
 * Subscriptions are managed exclusively via SuperAdmin\SubscriptionController.
 * TODO: Remove this file if confirmed unused.
 */
class SubscriptionController extends Controller
{
    public function __construct()
    {
        abort(403, 'Subscriptions are managed by SuperAdmin only.');
    }
}
