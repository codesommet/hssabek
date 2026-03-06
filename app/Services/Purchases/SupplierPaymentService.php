<?php

namespace App\Services\Purchases;

use App\Models\Purchases\SupplierPayment;
use App\Models\Purchases\SupplierPaymentAllocation;
use App\Models\Purchases\VendorBill;
use Illuminate\Support\Facades\DB;

class SupplierPaymentService
{
    public function __construct(
        private readonly VendorBillService $vendorBillService,
    ) {}

    /**
     * Create a supplier payment and allocate it to vendor bills.
     */
    public function create(array $validated): SupplierPayment
    {
        return DB::transaction(function () use ($validated) {
            $payment = SupplierPayment::create([
                'supplier_id' => $validated['supplier_id'],
                'amount' => $validated['amount'],
                'status' => 'completed',
                'payment_date' => $validated['paid_at'],
                'paid_at' => now(),
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'reference_number' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $allocations = $validated['allocations'] ?? [];
            foreach ($allocations as $alloc) {
                $amountApplied = (float) $alloc['amount_applied'];
                if ($amountApplied <= 0) {
                    continue;
                }

                $vendorBill = VendorBill::findOrFail($alloc['vendor_bill_id']);

                // Anti-over-allocation check
                $outstanding = (float) $vendorBill->amount_due;
                if ($amountApplied > $outstanding + 0.01) {
                    throw new \DomainException(
                        "Le montant d'allocation ({$amountApplied}) dépasse le solde restant ({$outstanding}) de la facture fournisseur {$vendorBill->number}."
                    );
                }

                SupplierPaymentAllocation::create([
                    'supplier_payment_id' => $payment->id,
                    'vendor_bill_id' => $vendorBill->id,
                    'amount_applied' => $amountApplied,
                ]);

                $this->vendorBillService->updatePaymentTotals($vendorBill);
            }

            return $payment->load('allocations');
        });
    }

    /**
     * Delete a supplier payment and reverse its allocations.
     */
    public function delete(SupplierPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->loadMissing('allocations');
            $vendorBillIds = $payment->allocations->pluck('vendor_bill_id')->unique();

            $payment->allocations()->delete();

            foreach ($vendorBillIds as $vendorBillId) {
                $vendorBill = VendorBill::find($vendorBillId);
                if ($vendorBill) {
                    $this->vendorBillService->updatePaymentTotals($vendorBill);
                }
            }

            $payment->delete();
        });
    }
}
