<?php

namespace App\Http\Controllers\Backoffice\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Store\StoreDeliveryChallanRequest;
use App\Http\Requests\Sales\Update\UpdateDeliveryChallanRequest;
use App\Models\Catalog\Product;
use App\Models\CRM\Customer;
use App\Models\Sales\DeliveryChallan;
use App\Models\Sales\DeliveryChallanItem;
use App\Models\Sales\Invoice;
use App\Services\Sales\PdfService;
use App\Services\System\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryChallanController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', DeliveryChallan::class);

        $challans = DeliveryChallan::query()
            ->with(['customer'])
            ->when($request->search, fn($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('number', 'like', "%{$s}%")
                    ->orWhere('reference_number', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest('challan_date')
            ->paginate(15)
            ->withQueryString();

        return view('backoffice.sales.delivery-challans.index', compact('challans'));
    }

    public function create()
    {
        $this->authorize('create', DeliveryChallan::class);

        $customers = Customer::orderBy('name')->get();
        $invoices = Invoice::orderBy('issue_date', 'desc')->limit(50)->get();
        $products = Product::orderBy('name')->get();

        return view('backoffice.sales.delivery-challans.create', compact('customers', 'invoices', 'products'));
    }

    public function store(StoreDeliveryChallanRequest $request)
    {
        $this->authorize('create', DeliveryChallan::class);

        $validated = $request->validated();

        $challan = DB::transaction(function () use ($validated) {
            $items = $validated['items'] ?? [];

            $subtotal = 0;
            $taxTotal = 0;
            foreach ($items as $item) {
                $lineSubtotal = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
                $lineTax = $lineSubtotal * ((float) ($item['tax_rate'] ?? 0)) / 100;
                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
            }
            $total = round($subtotal + $taxTotal, 2);

            $challan = DeliveryChallan::create([
                'customer_id' => $validated['customer_id'],
                'invoice_id' => $validated['invoice_id'] ?? null,
                'quote_id' => $validated['quote_id'] ?? null,
                'number' => app(DocumentNumberService::class)->next('delivery_challan'),
                'status' => 'draft',
                'challan_date' => $validated['challan_date'],
                'due_date' => $validated['due_date'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'enable_tax' => $validated['enable_tax'] ?? true,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
            ]);

            foreach ($items as $i => $item) {
                $lineSubtotal = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
                $lineTax = $lineSubtotal * ((float) ($item['tax_rate'] ?? 0)) / 100;

                DeliveryChallanItem::create([
                    'delivery_challan_id' => $challan->id,
                    'product_id' => $item['product_id'] ?? null,
                    'label' => $item['label'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'line_subtotal' => $lineSubtotal,
                    'line_tax' => $lineTax,
                    'line_total' => round($lineSubtotal + $lineTax, 2),
                    'position' => $i,
                ]);
            }

            return $challan;
        });

        return redirect()->route('bo.sales.delivery-challans.show', $challan)
            ->with('success', 'Bon de livraison créé avec succès.');
    }

    public function show(DeliveryChallan $deliveryChallan)
    {
        $this->authorize('view', $deliveryChallan);

        $deliveryChallan->load(['customer', 'items', 'invoice']);

        return view('backoffice.sales.delivery-challans.show', compact('deliveryChallan'));
    }

    public function edit(DeliveryChallan $deliveryChallan)
    {
        $this->authorize('update', $deliveryChallan);

        $customers = Customer::orderBy('name')->get();
        $invoices = Invoice::orderBy('issue_date', 'desc')->limit(50)->get();
        $products = Product::orderBy('name')->get();
        $deliveryChallan->load('items');

        return view('backoffice.sales.delivery-challans.edit', compact('deliveryChallan', 'customers', 'invoices', 'products'));
    }

    public function update(UpdateDeliveryChallanRequest $request, DeliveryChallan $deliveryChallan)
    {
        $this->authorize('update', $deliveryChallan);

        DB::transaction(function () use ($request, $deliveryChallan) {
            $validated = $request->validated();
            $items = $validated['items'] ?? [];

            $subtotal = 0;
            $taxTotal = 0;
            foreach ($items as $item) {
                $lineSubtotal = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
                $lineTax = $lineSubtotal * ((float) ($item['tax_rate'] ?? 0)) / 100;
                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
            }
            $total = round($subtotal + $taxTotal, 2);

            $deliveryChallan->update([
                'customer_id' => $validated['customer_id'] ?? $deliveryChallan->customer_id,
                'invoice_id' => $validated['invoice_id'] ?? $deliveryChallan->invoice_id,
                'challan_date' => $validated['challan_date'] ?? $deliveryChallan->challan_date,
                'reference_number' => $validated['reference_number'] ?? $deliveryChallan->reference_number,
                'notes' => $validated['notes'] ?? $deliveryChallan->notes,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);

            $deliveryChallan->items()->delete();
            foreach ($items as $i => $item) {
                $lineSubtotal = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
                $lineTax = $lineSubtotal * ((float) ($item['tax_rate'] ?? 0)) / 100;

                DeliveryChallanItem::create([
                    'delivery_challan_id' => $deliveryChallan->id,
                    'product_id' => $item['product_id'] ?? null,
                    'label' => $item['label'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'line_subtotal' => $lineSubtotal,
                    'line_tax' => $lineTax,
                    'line_total' => round($lineSubtotal + $lineTax, 2),
                    'position' => $i,
                ]);
            }
        });

        return redirect()->route('bo.sales.delivery-challans.show', $deliveryChallan)
            ->with('success', 'Bon de livraison mis à jour avec succès.');
    }

    public function destroy(DeliveryChallan $deliveryChallan)
    {
        $this->authorize('delete', $deliveryChallan);

        $deliveryChallan->items()->delete();
        $deliveryChallan->delete();

        return redirect()->route('bo.sales.delivery-challans.index')
            ->with('success', 'Bon de livraison supprimé avec succès.');
    }

    public function download(DeliveryChallan $deliveryChallan, PdfService $pdfService)
    {
        abort_unless(auth()->user()->can('sales.delivery_challans.view'), 403);

        return $pdfService->deliveryChallanResponse($deliveryChallan, 'download');
    }
}
