<?php

namespace App\Services\System;

use App\Models\System\DocumentNumberSequence;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

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
    public function next(string $documentType): string
    {
        $tenantId = TenantContext::id()
            ?? throw new \RuntimeException('TenantContext not set.');

        return DB::transaction(function () use ($tenantId, $documentType) {
            $sequence = DocumentNumberSequence::where('tenant_id', $tenantId)
                ->where('document_type', $documentType)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                // Auto-create a default sequence for this tenant+type
                $sequence = DocumentNumberSequence::create([
                    'document_type'  => $documentType,
                    'prefix'         => strtoupper(substr($documentType, 0, 3)) . '-',
                    'current_number' => 1,
                    'increment_by'   => 1,
                    'suffix'         => null,
                ]);
            }

            $number = $sequence->current_number;

            // Increment using direct SQL to avoid Model events re-triggering
            DocumentNumberSequence::where('id', $sequence->id)
                ->update(['current_number' => $number + $sequence->increment_by]);

            $padded = str_pad((string) $number, 5, '0', STR_PAD_LEFT);

            return ($sequence->prefix ?? '') . $padded . ($sequence->suffix ?? '');
        });
    }
}
