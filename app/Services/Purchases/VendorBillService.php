<?php

namespace App\Services\Purchases;

use App\Models\Purchases\VendorBill;

class VendorBillService
{
    /**
     * Recalculate amount_paid / amount_due from SupplierPaymentAllocations + DebitNoteApplications.
     */
    public function updatePaymentTotals(VendorBill $vendorBill): void
    {
        $totalPaid = (float) $vendorBill->supplierPaymentAllocations()->sum('amount_applied');
        $totalDebits = (float) $vendorBill->debitNoteApplications()->sum('amount_applied');
        $amountPaid = round($totalPaid + $totalDebits, 2);
        $amountDue = round((float) $vendorBill->total - $amountPaid, 2);

        $vendorBill->update([
            'amount_paid' => $amountPaid,
            'amount_due' => max(0, $amountDue),
        ]);

        // Auto-transition status based on payment
        if ($amountDue <= 0.01 && !in_array($vendorBill->status, ['paid', 'void'])) {
            $vendorBill->update(['status' => 'paid']);
        } elseif ($amountPaid > 0 && $amountDue > 0.01 && $vendorBill->status === 'pending') {
            $vendorBill->update(['status' => 'partial']);
        }
    }
}
