<?php

namespace App\Observers;

use App\Models\Finance\BankAccount;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\Income;
use App\Models\Sales\Payment;
use App\Services\System\DocumentNumberService;
use App\Services\Tenancy\TenantContext;

class PaymentObserver
{
    public function __construct(
        private DocumentNumberService $documentNumberService
    ) {}

    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->syncIncomeAndBankBalance($payment);
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        // Only sync if status changed to completed/paid or amount changed
        if ($payment->wasChanged(['status', 'amount', 'bank_account_id'])) {
            $this->syncIncomeAndBankBalance($payment);
        }
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        // Reverse the bank balance if payment had a bank account
        if ($payment->bank_account_id && $payment->status === 'completed') {
            $bankAccount = BankAccount::find($payment->bank_account_id);
            if ($bankAccount) {
                $bankAccount->decrement('current_balance', $payment->amount);
            }
        }

        // Delete associated auto-generated income
        Income::where('reference_number', 'PAY-' . $payment->id)->delete();
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        $this->syncIncomeAndBankBalance($payment);
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        // Same as deleted
        $this->deleted($payment);
    }

    /**
     * Sync income record and bank balance when payment is completed.
     */
    private function syncIncomeAndBankBalance(Payment $payment): void
    {
        // Only process completed payments with a bank account
        if ($payment->status !== 'completed' || !$payment->bank_account_id) {
            return;
        }

        $tenantId = $payment->tenant_id ?? TenantContext::id();

        // Find or create the sales income category
        $category = FinanceCategory::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => 'Ventes - Paiements Clients',
                'type' => 'income',
            ],
            ['is_active' => true]
        );

        // Create or update income record
        $incomeReference = 'PAY-' . $payment->id;

        // Check if income already exists to avoid generating new number
        $existingIncome = Income::where('tenant_id', $tenantId)
            ->where('reference_number', $incomeReference)
            ->first();

        $incomeData = [
            'amount' => $payment->amount,
            'income_date' => $payment->payment_date ?? now(),
            'payment_mode' => $payment->paymentMethod?->name ?? 'Virement',
            'bank_account_id' => $payment->bank_account_id,
            'customer_id' => $payment->customer_id,
            'category_id' => $category->id,
            'description' => 'Paiement client automatique - Réf: ' . ($payment->reference_number ?? $payment->id),
        ];

        if ($existingIncome) {
            $existingIncome->update($incomeData);
        } else {
            $incomeData['tenant_id'] = $tenantId;
            $incomeData['income_number'] = $this->documentNumberService->next('income');
            $incomeData['reference_number'] = $incomeReference;
            Income::create($incomeData);
        }        // Update bank account balance
        $bankAccount = BankAccount::find($payment->bank_account_id);
        if ($bankAccount) {
            // Check if this is a new payment or status just changed to completed
            $wasJustCompleted = $payment->wasChanged('status') && $payment->getOriginal('status') !== 'completed';
            $isNewPayment = $payment->wasRecentlyCreated;

            if ($isNewPayment || $wasJustCompleted) {
                $bankAccount->increment('current_balance', $payment->amount);
            }
        }
    }
}
