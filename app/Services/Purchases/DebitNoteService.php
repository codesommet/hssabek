<?php

namespace App\Services\Purchases;

use App\Models\Purchases\DebitNote;
use App\Models\Purchases\DebitNoteApplication;
use App\Models\Purchases\VendorBill;
use Illuminate\Support\Facades\DB;

class DebitNoteService
{
    public function __construct(
        private readonly VendorBillService $vendorBillService,
    ) {}

    /**
     * Apply a debit note to vendor bills.
     */
    public function apply(DebitNote $debitNote, array $allocations): void
    {
        DB::transaction(function () use ($debitNote, $allocations) {
            $totalAlreadyApplied = (float) $debitNote->applications()->sum('amount_applied');
            $totalNewApplied = 0;

            foreach ($allocations as $alloc) {
                $amountApplied = (float) $alloc['amount_applied'];
                if ($amountApplied <= 0) {
                    continue;
                }

                $totalNewApplied += $amountApplied;
                $vendorBill = VendorBill::findOrFail($alloc['vendor_bill_id']);

                // Anti-over-allocation check on vendor bill
                $outstanding = (float) $vendorBill->amount_due;
                if ($amountApplied > $outstanding + 0.01) {
                    throw new \DomainException(
                        "Le montant d'application ({$amountApplied}) dépasse le solde restant ({$outstanding}) de la facture fournisseur {$vendorBill->number}."
                    );
                }

                DebitNoteApplication::create([
                    'debit_note_id' => $debitNote->id,
                    'vendor_bill_id' => $vendorBill->id,
                    'amount_applied' => $amountApplied,
                ]);

                $this->vendorBillService->updatePaymentTotals($vendorBill);
            }

            // Anti-over-allocation check on debit note total
            if (($totalAlreadyApplied + $totalNewApplied) > (float) $debitNote->total + 0.01) {
                throw new \DomainException(
                    "Le total des applications dépasse le montant de la note de débit."
                );
            }

            // Auto-transition to 'applied' if fully applied
            $totalApplied = $totalAlreadyApplied + $totalNewApplied;
            if ($totalApplied >= (float) $debitNote->total - 0.01) {
                $debitNote->update(['status' => 'applied']);
            }
        });
    }
}
