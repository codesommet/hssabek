<?php

namespace App\Http\Controllers\Backoffice\Purchases;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\Store\StoreDebitNoteRequest;
use App\Http\Requests\Purchases\Update\UpdateDebitNoteRequest;
use App\Models\Purchases\DebitNote;
use App\Models\Purchases\DebitNoteItem;
use App\Models\Purchases\Supplier;
use App\Models\Purchases\VendorBill;
use App\Services\Purchases\DebitNoteService;
use App\Services\Sales\PdfService;
use App\Services\System\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebitNoteController extends Controller
{
    public function __construct(
        private readonly DebitNoteService $debitNoteService,
    ) {}
    public function index(Request $request)
    {
        $this->authorize('viewAny', DebitNote::class);

        $debitNotes = DebitNote::query()
            ->with(['supplier'])
            ->when($request->search, fn($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('number', 'like', "%{$s}%")
                    ->orWhere('reference_number', 'like', "%{$s}%")
                    ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest('debit_note_date')
            ->paginate(15)
            ->withQueryString();

        return view('backoffice.purchases.debit-notes.index', compact('debitNotes'));
    }

    public function create()
    {
        $this->authorize('create', DebitNote::class);

        $suppliers = Supplier::orderBy('name')->get();
        $vendorBills = VendorBill::with('supplier')->orderBy('issue_date', 'desc')->limit(50)->get();

        return view('backoffice.purchases.debit-notes.create', compact('suppliers', 'vendorBills'));
    }

    public function store(StoreDebitNoteRequest $request)
    {
        $this->authorize('create', DebitNote::class);

        $validated = $request->validated();

        $debitNote = DB::transaction(function () use ($validated) {
            $items = $validated['items'] ?? [];

            // Calculate totals from items
            $subtotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;
            foreach ($items as $item) {
                $lineSubtotal = (float) $item['quantity'] * (float) $item['unit_price'];
                $discount = 0;
                if (($item['discount_type'] ?? 'none') === 'percentage') {
                    $discount = $lineSubtotal * ((float) ($item['discount_value'] ?? 0)) / 100;
                } elseif (($item['discount_type'] ?? 'none') === 'fixed') {
                    $discount = (float) ($item['discount_value'] ?? 0);
                }
                $afterDiscount = $lineSubtotal - $discount;
                $tax = $afterDiscount * ((float) ($item['tax_rate'] ?? 0)) / 100;
                $subtotal += $lineSubtotal;
                $discountTotal += $discount;
                $taxTotal += $tax;
            }
            $total = round($subtotal - $discountTotal + $taxTotal, 2);

            $debitNote = DebitNote::create([
                'supplier_id' => $validated['supplier_id'],
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'vendor_bill_id' => $validated['vendor_bill_id'] ?? null,
                'number' => app(DocumentNumberService::class)->next('debit_note'),
                'reference_number' => $validated['reference_number'] ?? null,
                'status' => 'draft',
                'debit_note_date' => $validated['debit_note_date'],
                'due_date' => $validated['due_date'] ?? null,
                'enable_tax' => $validated['enable_tax'] ?? true,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
            ]);

            foreach ($items as $i => $item) {
                $lineSubtotal = (float) $item['quantity'] * (float) $item['unit_price'];
                $discount = 0;
                if (($item['discount_type'] ?? 'none') === 'percentage') {
                    $discount = $lineSubtotal * ((float) ($item['discount_value'] ?? 0)) / 100;
                } elseif (($item['discount_type'] ?? 'none') === 'fixed') {
                    $discount = (float) ($item['discount_value'] ?? 0);
                }
                $afterDiscount = $lineSubtotal - $discount;
                $tax = $afterDiscount * ((float) ($item['tax_rate'] ?? 0)) / 100;

                DebitNoteItem::create([
                    'debit_note_id' => $debitNote->id,
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_percentage' => ($item['discount_type'] ?? 'none') === 'percentage' ? ($item['discount_value'] ?? 0) : 0,
                    'tax_amount' => $tax,
                    'line_total' => round($afterDiscount + $tax, 2),
                ]);
            }

            return $debitNote;
        });

        return redirect()->route('bo.purchases.debit-notes.show', $debitNote)
            ->with('success', 'Note de débit créée avec succès.');
    }

    public function show(DebitNote $debitNote)
    {
        $this->authorize('view', $debitNote);

        $debitNote->load(['supplier', 'items', 'vendorBill', 'purchaseOrder', 'applications.vendorBill']);

        return view('backoffice.purchases.debit-notes.show', compact('debitNote'));
    }

    public function edit(DebitNote $debitNote)
    {
        $this->authorize('update', $debitNote);

        $suppliers = Supplier::orderBy('name')->get();
        $vendorBills = VendorBill::with('supplier')->orderBy('issue_date', 'desc')->limit(50)->get();

        return view('backoffice.purchases.debit-notes.edit', compact('debitNote', 'suppliers', 'vendorBills'));
    }

    public function update(UpdateDebitNoteRequest $request, DebitNote $debitNote)
    {
        $this->authorize('update', $debitNote);
        abort_unless($debitNote->status === 'draft', 403, 'Seules les notes de débit en brouillon peuvent être modifiées.');

        DB::transaction(function () use ($request, $debitNote) {
            $validated = $request->validated();
            $items = $validated['items'] ?? [];

            $subtotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;
            foreach ($items as $item) {
                $lineSubtotal = (float) $item['quantity'] * (float) $item['unit_price'];
                $discount = 0;
                if (($item['discount_type'] ?? 'none') === 'percentage') {
                    $discount = $lineSubtotal * ((float) ($item['discount_value'] ?? 0)) / 100;
                } elseif (($item['discount_type'] ?? 'none') === 'fixed') {
                    $discount = (float) ($item['discount_value'] ?? 0);
                }
                $afterDiscount = $lineSubtotal - $discount;
                $tax = $afterDiscount * ((float) ($item['tax_rate'] ?? 0)) / 100;
                $subtotal += $lineSubtotal;
                $discountTotal += $discount;
                $taxTotal += $tax;
            }
            $total = round($subtotal - $discountTotal + $taxTotal, 2);

            $debitNote->update([
                'supplier_id' => $validated['supplier_id'] ?? $debitNote->supplier_id,
                'vendor_bill_id' => $validated['vendor_bill_id'] ?? $debitNote->vendor_bill_id,
                'debit_note_date' => $validated['debit_note_date'] ?? $debitNote->debit_note_date,
                'due_date' => $validated['due_date'] ?? $debitNote->due_date,
                'reference_number' => $validated['reference_number'] ?? $debitNote->reference_number,
                'notes' => $validated['notes'] ?? $debitNote->notes,
                'terms' => $validated['terms'] ?? $debitNote->terms,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);

            $debitNote->items()->delete();
            foreach ($items as $item) {
                $lineSubtotal = (float) $item['quantity'] * (float) $item['unit_price'];
                $discount = 0;
                if (($item['discount_type'] ?? 'none') === 'percentage') {
                    $discount = $lineSubtotal * ((float) ($item['discount_value'] ?? 0)) / 100;
                } elseif (($item['discount_type'] ?? 'none') === 'fixed') {
                    $discount = (float) ($item['discount_value'] ?? 0);
                }
                $afterDiscount = $lineSubtotal - $discount;
                $tax = $afterDiscount * ((float) ($item['tax_rate'] ?? 0)) / 100;

                DebitNoteItem::create([
                    'debit_note_id' => $debitNote->id,
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_percentage' => ($item['discount_type'] ?? 'none') === 'percentage' ? ($item['discount_value'] ?? 0) : 0,
                    'tax_amount' => $tax,
                    'line_total' => round($afterDiscount + $tax, 2),
                ]);
            }
        });

        return redirect()->route('bo.purchases.debit-notes.show', $debitNote)
            ->with('success', 'Note de débit mise à jour avec succès.');
    }

    public function apply(Request $request, DebitNote $debitNote)
    {
        $this->authorize('update', $debitNote);
        abort_unless(in_array($debitNote->status, ['draft', 'sent']), 403, 'Seules les notes de débit en brouillon ou envoyées peuvent être appliquées.');

        $validated = $request->validate([
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.vendor_bill_id' => ['required', 'uuid', 'exists:vendor_bills,id'],
            'allocations.*.amount_applied' => ['required', 'numeric', 'min:0.01'],
        ], [
            'allocations.required' => 'Au moins une allocation est obligatoire.',
            'allocations.*.vendor_bill_id.required' => 'La facture fournisseur est obligatoire.',
            'allocations.*.amount_applied.required' => 'Le montant est obligatoire.',
        ]);

        $this->debitNoteService->apply($debitNote, $validated['allocations']);

        return redirect()->route('bo.purchases.debit-notes.show', $debitNote)
            ->with('success', 'Note de débit appliquée avec succès.');
    }

    public function destroy(DebitNote $debitNote)
    {
        $this->authorize('delete', $debitNote);

        $debitNote->items()->delete();
        $debitNote->delete();

        return redirect()->route('bo.purchases.debit-notes.index')
            ->with('success', 'Note de débit supprimée avec succès.');
    }

    public function download(DebitNote $debitNote, PdfService $pdfService)
    {
        abort_unless(auth()->user()->can('purchases.debit_notes.view'), 403);

        return $pdfService->debitNoteResponse($debitNote, 'download');
    }
}
