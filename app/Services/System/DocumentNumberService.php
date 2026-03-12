<?php

namespace App\Services\System;

use App\Models\System\DocumentNumberSequence;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentNumberService
{
    /**
     * Generate the next document number for the given type.
     * Uses pessimistic locking to prevent race conditions.
     *
     * @param string $documentType  e.g. 'invoice', 'quote', 'purchase_order', 'credit_note'
     * @return string  e.g. 'INV-00001'
     * @throws \RuntimeException if TenantContext is not set
     */
    /**
     * Preview the next document number without incrementing.
     */
    public function preview(string $documentType): string
    {
        $tenantId = TenantContext::id()
            ?? throw new \RuntimeException('TenantContext not set.');

        $sequence = DocumentNumberSequence::where('tenant_id', $tenantId)
            ->where('key', $documentType)
            ->first();

        $prefix = $sequence->prefix ?? strtoupper(substr($documentType, 0, 3)) . '-';
        $number = $sequence->next_number ?? 1;

        return $prefix . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }

    public function next(string $documentType): string
    {
        $tenantId = TenantContext::id()
            ?? throw new \RuntimeException('TenantContext not set.');

        return DB::transaction(function () use ($tenantId, $documentType) {
            $sequence = DocumentNumberSequence::where('tenant_id', $tenantId)
                ->where('key', $documentType)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                $prefix = strtoupper(substr($documentType, 0, 3)) . '-';
                $startNumber = $this->detectNextNumber($tenantId, $documentType, $prefix);

                $sequence = DocumentNumberSequence::create([
                    'key'         => $documentType,
                    'prefix'      => $prefix,
                    'next_number' => $startNumber,
                ]);
            }

            $number = $sequence->next_number;

            // Increment using direct SQL to avoid Model events re-triggering
            DocumentNumberSequence::where('id', $sequence->id)
                ->update(['next_number' => $number + 1]);

            $padded = str_pad((string) $number, 5, '0', STR_PAD_LEFT);

            return ($sequence->prefix ?? '') . $padded;
        });
    }

    /**
     * Detect the next available number by scanning existing records in the target table.
     */
    private function detectNextNumber(string $tenantId, string $documentType, string $prefix): int
    {
        $tableMap = [
            'invoice'          => 'invoices',
            'quote'            => 'quotes',
            'credit_note'      => 'credit_notes',
            'debit_note'       => 'debit_notes',
            'purchase_order'   => 'purchase_orders',
            'delivery_challan' => 'delivery_challans',
            'vendor_bill'      => 'vendor_bills',
            'goods_receipt'    => 'goods_receipts',
            'payment'          => 'payments',
        ];

        $table = $tableMap[$documentType] ?? null;

        if (!$table || !Schema::hasTable($table)) {
            return 1;
        }

        $maxNumber = DB::table($table)
            ->where('tenant_id', $tenantId)
            ->where('number', 'LIKE', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(`number`, ?) AS UNSIGNED)) as max_num", [strlen($prefix) + 1])
            ->value('max_num');

        return $maxNumber ? (int) $maxNumber + 1 : 1;
    }
}
