<?php

namespace App\Observers;

use App\Models\Finance\BankAccount;
use App\Models\Finance\Expense;
use App\Models\Finance\FinanceCategory;
use App\Models\Purchases\SupplierPayment;
use App\Services\System\DocumentNumberService;
use App\Services\Tenancy\TenantContext;

class SupplierPaymentObserver
{
    public function __construct(
        private DocumentNumberService $documentNumberService
    ) {}

    /**
     * Handle the SupplierPayment "created" event.
     */
    public function created(SupplierPayment $supplierPayment): void
    {
        $this->syncExpenseAndBankBalance($supplierPayment);
    }

    /**
     * Handle the SupplierPayment "updated" event.
     */
    public function updated(SupplierPayment $supplierPayment): void
    {
        // Only sync if status changed to completed/paid or amount changed
        if ($supplierPayment->wasChanged(['status', 'amount', 'bank_account_id'])) {
            $this->syncExpenseAndBankBalance($supplierPayment);
        }
    }

    /**
     * Handle the SupplierPayment "deleted" event.
     */
    public function deleted(SupplierPayment $supplierPayment): void
    {
        // Reverse the bank balance if payment had a bank account
        if ($supplierPayment->bank_account_id && $supplierPayment->status === 'completed') {
            $bankAccount = BankAccount::find($supplierPayment->bank_account_id);
            if ($bankAccount) {
                // Add back the amount since expense is being deleted
                $bankAccount->increment('current_balance', $supplierPayment->amount);
            }
        }

        // Delete associated auto-generated expense
        Expense::where('reference_number', 'SUPPAY-' . $supplierPayment->id)->delete();
    }

    /**
     * Handle the SupplierPayment "restored" event.
     */
    public function restored(SupplierPayment $supplierPayment): void
    {
        $this->syncExpenseAndBankBalance($supplierPayment);
    }

    /**
     * Handle the SupplierPayment "force deleted" event.
     */
    public function forceDeleted(SupplierPayment $supplierPayment): void
    {
        $this->deleted($supplierPayment);
    }

    /**
     * Sync expense record and bank balance when supplier payment is completed.
     */
    private function syncExpenseAndBankBalance(SupplierPayment $supplierPayment): void
    {
        // Only process completed payments with a bank account
        if ($supplierPayment->status !== 'completed' || !$supplierPayment->bank_account_id) {
            return;
        }

        $tenantId = $supplierPayment->tenant_id ?? TenantContext::id();

        // Find or create the purchases expense category
        $category = FinanceCategory::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => 'Achats - Paiements Fournisseurs',
                'type' => 'expense',
            ],
            ['is_active' => true]
        );

        // Create or update expense record
        $expenseReference = 'SUPPAY-' . $supplierPayment->id;

        // Check if expense already exists to avoid generating new number
        $existingExpense = Expense::where('tenant_id', $tenantId)
            ->where('reference_number', $expenseReference)
            ->first();

        $expenseData = [
            'amount' => $supplierPayment->amount,
            'paid_amount' => $supplierPayment->amount,
            'expense_date' => $supplierPayment->payment_date ?? now(),
            'payment_mode' => $supplierPayment->paymentMethod?->name ?? 'Virement',
            'payment_status' => 'paid',
            'bank_account_id' => $supplierPayment->bank_account_id,
            'supplier_id' => $supplierPayment->supplier_id,
            'category_id' => $category->id,
            'description' => 'Paiement fournisseur automatique - Réf: ' . ($supplierPayment->reference_number ?? $supplierPayment->id),
        ];

        if ($existingExpense) {
            $existingExpense->update($expenseData);
        } else {
            $expenseData['tenant_id'] = $tenantId;
            $expenseData['expense_number'] = $this->documentNumberService->next('expense');
            $expenseData['reference_number'] = $expenseReference;
            Expense::create($expenseData);
        }

        // Update bank account balance (decrement for expenses)
        $bankAccount = BankAccount::find($supplierPayment->bank_account_id);
        if ($bankAccount) {
            // Check if this is a new payment or status just changed to completed
            $wasJustCompleted = $supplierPayment->wasChanged('status') && $supplierPayment->getOriginal('status') !== 'completed';
            $isNewPayment = $supplierPayment->wasRecentlyCreated;

            if ($isNewPayment || $wasJustCompleted) {
                $bankAccount->decrement('current_balance', $supplierPayment->amount);
            }
        }
    }
}
