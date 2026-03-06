<?php

namespace App\Http\Controllers\Backoffice\Billing;

use App\Http\Controllers\Controller;

/**
 * Scaffold placeholder — NOT routed.
 * Subscription invoices are managed exclusively by SuperAdmin.
 * TODO: Remove this file if confirmed unused.
 */
class SubscriptionInvoiceController extends Controller
{
    public function __construct()
    {
        abort(403, 'Subscription invoices are managed by SuperAdmin only.');
    }
}
