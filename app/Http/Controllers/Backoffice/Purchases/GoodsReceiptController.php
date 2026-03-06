<?php

namespace App\Http\Controllers\Backoffice\Purchases;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\Store\StoreGoodsReceiptRequest;
use App\Http\Requests\Purchases\Update\UpdateGoodsReceiptRequest;
use App\Models\Catalog\Product;
use App\Models\Inventory\Warehouse;
use App\Models\Purchases\GoodsReceipt;
use App\Models\Purchases\GoodsReceiptItem;
use App\Models\Purchases\PurchaseOrder;
use App\Services\Sales\PdfService;
use App\Services\System\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', GoodsReceipt::class);

        $receipts = GoodsReceipt::query()
            ->with(['purchaseOrder', 'warehouse', 'creator'])
            ->when($request->search, fn($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('number', 'like', "%{$s}%")
                    ->orWhere('reference_number', 'like', "%{$s}%");
            }))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest('received_at')
            ->paginate(15)
            ->withQueryString();

        return view('backoffice.purchases.goods-receipts.index', compact('receipts'));
    }

    public function create()
    {
        $this->authorize('create', GoodsReceipt::class);

        $purchaseOrders = PurchaseOrder::where('status', 'confirmed')->with('supplier')->orderBy('order_date', 'desc')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('backoffice.purchases.goods-receipts.create', compact('purchaseOrders', 'warehouses', 'products'));
    }

    public function store(StoreGoodsReceiptRequest $request)
    {
        $this->authorize('create', GoodsReceipt::class);

        $validated = $request->validated();

        $receipt = DB::transaction(function () use ($validated) {
            $items = $validated['items'] ?? [];

            $receipt = GoodsReceipt::create([
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'warehouse_id' => $validated['warehouse_id'],
                'number' => app(DocumentNumberService::class)->next('goods_receipt'),
                'status' => 'received',
                'received_at' => $validated['received_at'] ?? now(),
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $i => $item) {
                GoodsReceiptItem::create([
                    'goods_receipt_id' => $receipt->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'position' => $i,
                ]);
            }

            return $receipt;
        });

        return redirect()->route('bo.purchases.goods-receipts.show', $receipt)
            ->with('success', 'Réception de marchandises enregistrée avec succès.');
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        $this->authorize('view', $goodsReceipt);

        $goodsReceipt->load(['purchaseOrder', 'warehouse', 'items.product', 'creator']);

        return view('backoffice.purchases.goods-receipts.show', compact('goodsReceipt'));
    }

    public function edit(GoodsReceipt $goodsReceipt)
    {
        $this->authorize('update', $goodsReceipt);

        $purchaseOrders = PurchaseOrder::with('supplier')->orderBy('order_date', 'desc')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $goodsReceipt->load('items');

        return view('backoffice.purchases.goods-receipts.edit', compact('goodsReceipt', 'purchaseOrders', 'warehouses', 'products'));
    }

    public function update(UpdateGoodsReceiptRequest $request, GoodsReceipt $goodsReceipt)
    {
        $this->authorize('update', $goodsReceipt);

        DB::transaction(function () use ($request, $goodsReceipt) {
            $validated = $request->validated();
            $items = $validated['items'] ?? [];

            $goodsReceipt->update([
                'purchase_order_id' => $validated['purchase_order_id'] ?? $goodsReceipt->purchase_order_id,
                'warehouse_id' => $validated['warehouse_id'] ?? $goodsReceipt->warehouse_id,
                'received_at' => $validated['received_at'] ?? $goodsReceipt->received_at,
                'reference_number' => $validated['reference_number'] ?? $goodsReceipt->reference_number,
                'notes' => $validated['notes'] ?? $goodsReceipt->notes,
            ]);

            $goodsReceipt->items()->delete();
            foreach ($items as $i => $item) {
                GoodsReceiptItem::create([
                    'goods_receipt_id' => $goodsReceipt->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'position' => $i,
                ]);
            }
        });

        return redirect()->route('bo.purchases.goods-receipts.show', $goodsReceipt)
            ->with('success', 'Réception de marchandises mise à jour avec succès.');
    }

    public function destroy(GoodsReceipt $goodsReceipt)
    {
        $this->authorize('delete', $goodsReceipt);

        $goodsReceipt->items()->delete();
        $goodsReceipt->delete();

        return redirect()->route('bo.purchases.goods-receipts.index')
            ->with('success', 'Réception de marchandises supprimée avec succès.');
    }

    public function download(GoodsReceipt $goodsReceipt, PdfService $pdfService)
    {
        abort_unless(auth()->user()->can('purchases.goods_receipts.view'), 403);

        return $pdfService->goodsReceiptResponse($goodsReceipt, 'download');
    }
}
