<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Billing\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * List all active subscriptions.
     */
    public function index(Request $request)
    {
        $query = Subscription::with('tenant', 'plan')
            ->orderByDesc('created_at');

        // Optional filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $subscriptions = $query->paginate(20);

        return view('backoffice.subscriptions.index', compact('subscriptions'));
    }

    /**
     * Show a single subscription.
     */
    public function show(Subscription $subscription)
    {
        $subscription->load('tenant', 'plan', 'invoices');

        return view('backoffice.subscriptions.show', compact('subscription'));
    }
}
