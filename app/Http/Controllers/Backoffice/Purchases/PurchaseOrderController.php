<?php

namespace App\Http\Controllers\Backoffice\Purchases;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\Store\StorePurchaseOrderRequest;
use App\Http\Requests\Purchases\Update\UpdatePurchaseOrderRequest;
use App\Models\Catalog\Product;
use App\Models\Catalog\TaxGroup;
use App\Models\Purchases\GoodsReceipt;
use App\Models\Purchases\GoodsReceiptItem;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\PurchaseOrderItem;
use App\Models\Purchases\Supplier;
use App\Services\Sales\PdfService;
use App\Services\System\DocumentNumberService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private DocumentNumberService $docNumberService,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $query = PurchaseOrder::query()
            ->with('supplier');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $purchaseOrders = $query->latest()->paginate(15)->withQueryString();

        return view('backoffice.purchases.purchase-orders.index', compact('purchaseOrders'));
    }

    public function create()
    {
        $this->authorize('create', PurchaseOrder::class);

        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $taxGroups = TaxGroup::orderBy('name')->get();

        return view('backoffice.purchases.purchase-orders.create', compact('suppliers', 'products', 'taxGroups'));
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        $this->authorize('create', PurchaseOrder::class);

        $validated = $request->validated();

        $po = DB::transaction(function () use ($validated) {
            $items = $validated['items'];
            $subtotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;

            $computedItems = [];
            foreach ($items as $index => $item) {
                $qty = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $lineSubtotal = round($qty * $unitCost, 2);

                $discountType = $item['discount_type'] ?? 'none';
                $discountValue = (float) ($item['discount_value'] ?? 0);
                $discountAmount = 0;
                if ($discountType === 'percentage') {
                    $discountAmount = round($lineSubtotal * $discountValue / 100, 2);
                } elseif ($discountType === 'fixed') {
                    $discountAmount = round($discountValue, 2);
                }

                $afterDiscount = $lineSubtotal - $discountAmount;
                $taxRate = (float) ($item['tax_rate'] ?? 0);
                $lineTax = round($afterDiscount * $taxRate / 100, 2);
                $lineTotal = round($afterDiscount + $lineTax, 2);

                $subtotal += $lineSubtotal;
                $discountTotal += $discountAmount;
                $taxTotal += $lineTax;

                $computedItems[] = array_merge($item, [
                    'line_subtotal' => $lineSubtotal,
                    'line_tax'      => $lineTax,
                    'line_total'    => $lineTotal,
                    'position'      => $index + 1,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'tax_rate'      => $taxRate,
                ]);
            }

            $total = round($subtotal - $discountTotal + $taxTotal, 2);

            $po = PurchaseOrder::create([
                'supplier_id'    => $validated['supplier_id'],
                'number'         => $this->docNumberService->next('purchase_order'),
                'order_date'     => $validated['order_date'],
                'expected_date'  => $validated['expected_date'] ?? null,
                'status'         => 'draft',
                'subtotal'       => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total'      => $taxTotal,
                'total'          => $total,
                'notes'          => $validated['notes'] ?? null,
                'terms'          => $validated['terms'] ?? null,
            ]);

            foreach ($computedItems as $item) {
                PurchaseOrderItem::create([
                    'tenant_id'        => TenantContext::id(),
                    'purchase_order_id' => $po->id,
                    'product_id'       => $item['product_id'] ?? null,
                    'label'            => $item['label'],
                    'description'      => $item['description'] ?? null,
                    'quantity'         => $item['quantity'],
                    'unit_cost'        => $item['unit_cost'],
                    'discount_type'    => $item['discount_type'],
                    'discount_value'   => $item['discount_value'],
                    'tax_rate'         => $item['tax_rate'],
                    'tax_group_id'     => $item['tax_group_id'] ?? null,
                    'line_subtotal'    => $item['line_subtotal'],
                    'line_tax'         => $item['line_tax'],
                    'line_total'       => $item['line_total'],
                    'position'         => $item['position'],
                ]);
            }

            return $po;
        });

        return redirect()->route('bo.purchases.purchase-orders.show', $po)
            ->with('success', 'Bon de commande créé avec succès.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier', 'items.product', 'goodsReceipts']);

        return view('backoffice.purchases.purchase-orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        abort_unless(in_array($purchaseOrder->status, ['draft']), 403, 'Seuls les bons de commande en brouillon peuvent être modifiés.');

        $purchaseOrder->load('items');
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $taxGroups = TaxGroup::orderBy('name')->get();

        return view('backoffice.purchases.purchase-orders.edit', compact('purchaseOrder', 'suppliers', 'products', 'taxGroups'));
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);
        abort_unless($purchaseOrder->status === 'draft', 403, 'Seuls les bons de commande en brouillon peuvent être modifiés.');

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $purchaseOrder) {
            $items = $validated['items'];
            $subtotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;

            $purchaseOrder->items()->delete();

            foreach ($items as $index => $item) {
                $qty = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $lineSubtotal = round($qty * $unitCost, 2);

                $discountType = $item['discount_type'] ?? 'none';
                $discountValue = (float) ($item['discount_value'] ?? 0);
                $discountAmount = 0;
                if ($discountType === 'percentage') {
                    $discountAmount = round($lineSubtotal * $discountValue / 100, 2);
                } elseif ($discountType === 'fixed') {
                    $discountAmount = round($discountValue, 2);
                }

                $afterDiscount = $lineSubtotal - $discountAmount;
                $taxRate = (float) ($item['tax_rate'] ?? 0);
                $lineTax = round($afterDiscount * $taxRate / 100, 2);
                $lineTotal = round($afterDiscount + $lineTax, 2);

                $subtotal += $lineSubtotal;
                $discountTotal += $discountAmount;
                $taxTotal += $lineTax;

                PurchaseOrderItem::create([
                    'tenant_id'        => TenantContext::id(),
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id'       => $item['product_id'] ?? null,
                    'label'            => $item['label'],
                    'description'      => $item['description'] ?? null,
                    'quantity'         => $item['quantity'],
                    'unit_cost'        => $item['unit_cost'],
                    'discount_type'    => $discountType,
                    'discount_value'   => $discountValue,
                    'tax_rate'         => $taxRate,
                    'tax_group_id'     => $item['tax_group_id'] ?? null,
                    'line_subtotal'    => $lineSubtotal,
                    'line_tax'         => $lineTax,
                    'line_total'       => $lineTotal,
                    'position'         => $index + 1,
                ]);
            }

            $total = round($subtotal - $discountTotal + $taxTotal, 2);

            $purchaseOrder->update([
                'supplier_id'    => $validated['supplier_id'],
                'order_date'     => $validated['order_date'],
                'expected_date'  => $validated['expected_date'] ?? null,
                'subtotal'       => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total'      => $taxTotal,
                'total'          => $total,
                'notes'          => $validated['notes'] ?? null,
                'terms'          => $validated['terms'] ?? null,
            ]);
        });

        return redirect()->route('bo.purchases.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Bon de commande mis à jour avec succès.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('delete', $purchaseOrder);
        $purchaseOrder->items()->delete();
        $purchaseOrder->delete();

        return redirect()->route('bo.purchases.purchase-orders.index')
            ->with('success', 'Bon de commande supprimé avec succès.');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        abort_unless(
            in_array($purchaseOrder->status, ['draft', 'sent', 'confirmed', 'partially_received']),
            422,
            'Ce bon de commande ne peut pas être réceptionné dans son état actuel.'
        );

        $purchaseOrder->load('items');

        DB::transaction(function () use ($purchaseOrder) {
            $receipt = GoodsReceipt::create([
                'purchase_order_id' => $purchaseOrder->id,
                'number'            => $this->docNumberService->next('goods_receipt'),
                'status'            => 'received',
                'received_at'       => now(),
                'created_by'        => auth()->id(),
            ]);

            foreach ($purchaseOrder->items as $item) {
                $qtyToReceive = $item->quantity - $item->received_quantity;
                if ($qtyToReceive <= 0) {
                    continue;
                }

                GoodsReceiptItem::create([
                    'tenant_id'              => TenantContext::id(),
                    'goods_receipt_id'       => $receipt->id,
                    'purchase_order_item_id' => $item->id,
                    'product_id'             => $item->product_id,
                    'quantity'               => $qtyToReceive,
                    'unit_cost'              => $item->unit_cost,
                    'tax_rate'               => $item->tax_rate,
                    'tax_group_id'           => $item->tax_group_id,
                    'line_total'             => $item->line_total,
                ]);

                $item->update(['received_quantity' => $item->quantity]);
            }

            $purchaseOrder->update(['status' => 'received']);
        });

        return redirect()->route('bo.purchases.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Marchandises réceptionnées avec succès.');
    }

    public function download(PurchaseOrder $purchaseOrder, PdfService $pdfService)
    {
        $this->authorize('view', $purchaseOrder);

        return $pdfService->purchaseOrderResponse($purchaseOrder, 'download');
    }

    public function stream(PurchaseOrder $purchaseOrder, PdfService $pdfService)
    {
        $this->authorize('view', $purchaseOrder);

        return $pdfService->purchaseOrderResponse($purchaseOrder, 'inline');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        abort_unless(
            in_array($purchaseOrder->status, ['draft', 'sent', 'confirmed']),
            422,
            'Ce bon de commande ne peut pas être annulé dans son état actuel.'
        );

        $purchaseOrder->update(['status' => 'cancelled']);

        return redirect()->route('bo.purchases.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Bon de commande annulé.');
    }
}
