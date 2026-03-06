<?php

namespace App\Http\Controllers\Backoffice\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\Store\StoreProductRequest;
use App\Http\Requests\Catalog\Update\UpdateProductRequest;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductCategory;
use App\Models\Catalog\TaxCategory;
use App\Models\Catalog\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::query()
            ->with(['category', 'unit', 'taxCategory']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $products = $query->latest()->paginate(15)->withQueryString();

        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('backoffice.catalog.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $this->authorize('create', Product::class);

        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $taxCategories = TaxCategory::where('is_active', true)->orderBy('name')->get();

        return view('backoffice.catalog.products.create', compact(
            'categories',
            'units',
            'taxCategories'
        ));
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $data = $request->validated();
        unset($data['product_image']);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $product = Product::create($data);

        if ($request->hasFile('product_image')) {
            $product->addMediaFromRequest('product_image')
                ->toMediaCollection('product_image');
        }

        return redirect()->route('bo.catalog.products.index')
            ->with('success', 'Produit créé avec succès.');
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $taxCategories = TaxCategory::where('is_active', true)->orderBy('name')->get();

        return view('backoffice.catalog.products.edit', compact(
            'product',
            'categories',
            'units',
            'taxCategories'
        ));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $data = $request->validated();
        unset($data['product_image']);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $product->update($data);

        if ($request->hasFile('product_image')) {
            $product->addMediaFromRequest('product_image')
                ->toMediaCollection('product_image');
        }

        return redirect()->route('bo.catalog.products.index')
            ->with('success', 'Produit mis à jour avec succès.');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('bo.catalog.products.index')
            ->with('success', 'Produit supprimé avec succès.');
    }
}
