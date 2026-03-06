<?php $page = 'purchase-orders'; ?>
@extends('backoffice.layout.mainlayout')
@section('content')
    <!-- ========================
                        Start Page Content
                    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- start row -->
            <div class="row">
                <div class="col-md-11 mx-auto">

                    <!-- Start Breadcrumb -->
                    <div class="d-flex d-block align-items-center justify-content-between flex-wrap gap-3 mb-3">
                        <div>
                            <h6><a href="{{ route('bo.purchases.purchase-orders.index') }}"
                                    class="d-flex align-items-center"><i class="isax isax-arrow-left me-2"></i>Bons de
                                    commande</a></h6>
                        </div>
                    </div>
                    <!-- End Breadcrumb -->

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('bo.purchases.purchase-orders.store') }}" method="POST" id="poForm">
                        @csrf

                        <div class="card">
                            <div class="card-body">
                                <div class="top-content">
                                    <div class="purchase-header mb-3">
                                        <h6>Détails du bon de commande</h6>
                                    </div>
                                    <div>
                                        <!-- start row -->
                                        <div class="row justify-content-between">
                                            <div class="col-xl-5">
                                                <div class="purchase-top-content">
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="mb-3">
                                                                <label class="form-label">Fournisseur <span
                                                                        class="text-danger">*</span></label>
                                                                <select
                                                                    class="form-select @error('supplier_id') is-invalid @enderror"
                                                                    name="supplier_id" required>
                                                                    <option value="">-- Sélectionner --</option>
                                                                    @foreach ($suppliers as $supplier)
                                                                        <option value="{{ $supplier->id }}"
                                                                            {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                                            {{ $supplier->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                                @error('supplier_id')
                                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Date de commande <span
                                                                        class="text-danger">*</span></label>
                                                                <input type="date"
                                                                    class="form-control @error('order_date') is-invalid @enderror"
                                                                    name="order_date"
                                                                    value="{{ old('order_date', date('Y-m-d')) }}" required>
                                                                @error('order_date')
                                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Date de livraison prévue</label>
                                                                <input type="date"
                                                                    class="form-control @error('expected_date') is-invalid @enderror"
                                                                    name="expected_date" value="{{ old('expected_date') }}">
                                                                @error('expected_date')
                                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- end col -->
                                            <div class="col-xl-4">
                                                <div class="purchase-top-content">
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="mb-3">
                                                                <label class="form-label">Devise</label>
                                                                <input type="text" class="form-control"
                                                                    value="{{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}"
                                                                    readonly disabled>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- end col -->
                                        </div>
                                        <!-- end row -->
                                    </div>
                                </div>

                                <div class="items-details">
                                    <div class="purchase-header mb-3">
                                        <h6>Articles</h6>
                                    </div>

                                    <!-- Table List Start -->
                                    <div class="table-responsive rounded border-bottom-0 border mb-3">
                                        <table class="table table-nowrap add-table mb-0" id="items-table">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Produit / Libellé</th>
                                                    <th>Quantité</th>
                                                    <th>Coût unitaire</th>
                                                    <th>Taxe (%)</th>
                                                    <th>Total ligne</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody class="add-tbody" id="items-body">
                                                <tr class="item-row">
                                                    <td>
                                                        <input type="text" class="form-control" name="items[0][label]"
                                                            placeholder="Libellé de l'article"
                                                            value="{{ old('items.0.label') }}" required
                                                            style="min-width:200px;">
                                                        <select class="form-select form-select-sm mt-1"
                                                            name="items[0][product_id]">
                                                            <option value="">-- Produit (optionnel) --</option>
                                                            @foreach ($products as $product)
                                                                <option value="{{ $product->id }}"
                                                                    {{ old('items.0.product_id') == $product->id ? 'selected' : '' }}>
                                                                    {{ $product->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control item-qty"
                                                            name="items[0][quantity]"
                                                            value="{{ old('items.0.quantity', 1) }}" min="0.001"
                                                            step="0.001" required style="min-width:80px;">
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control item-cost"
                                                            name="items[0][unit_cost]"
                                                            value="{{ old('items.0.unit_cost', 0) }}" min="0"
                                                            step="0.01" required style="min-width:100px;">
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control item-tax"
                                                            name="items[0][tax_rate]"
                                                            value="{{ old('items.0.tax_rate', 20) }}" min="0"
                                                            max="100" step="0.01" style="min-width:70px;">
                                                    </td>
                                                    <td>
                                                        <span class="item-total fw-medium">0.00</span>
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- Table List End -->

                                    <div>
                                        <a href="javascript:void(0);" class="d-inline-flex align-items-center"
                                            id="add-item-btn"><i
                                                class="isax isax-add-circle5 text-primary me-1"></i>Ajouter
                                            un article</a>
                                    </div>
                                </div>

                                <div class="extra-info">
                                    <!-- start row -->
                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="mb-3">
                                                <label class="form-label">Notes</label>
                                                <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Conditions</label>
                                                <textarea class="form-control" name="terms" rows="3">{{ old('terms') }}</textarea>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-md-5">
                                            <ul class="mb-0 ps-0 list-unstyled">
                                                <li class="mb-3">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <p class="fw-semibold fs-14 text-gray-9 mb-0">Sous-total</p>
                                                        <h6 class="fs-14" id="display-subtotal">0.00</h6>
                                                    </div>
                                                </li>
                                                <li class="mb-3">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <p class="fw-semibold fs-14 text-gray-9 mb-0">Taxes</p>
                                                        <h6 class="fs-14" id="display-tax">0.00</h6>
                                                    </div>
                                                </li>
                                                <li class="mt-3 pb-3 border-bottom border-gray">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <h6>Total</h6>
                                                        <h6 id="display-total">0.00</h6>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div><!-- end col -->
                                    </div>
                                    <!-- end row -->
                                </div>
                            </div><!-- end card body -->
                            <div class="card-footer">
                                <div class="d-flex align-items-center justify-content-between">
                                    <a href="{{ route('bo.purchases.purchase-orders.index') }}"
                                        class="btn btn-outline-white">Annuler</a>
                                    <button type="submit" class="btn btn-primary">Créer le bon de commande</button>
                                </div>
                            </div><!-- end card footer -->
                        </div><!-- end card -->
                    </form>
                </div>
            </div>
            <!-- end row -->

            @component('backoffice.components.footer')
            @endcomponent
        </div>
        <!-- End Content -->

    </div>

    <!-- ========================
                        End Page Content
                    ========================= -->
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let itemIndex = 1;
            const productsJson = @json($products);

            document.getElementById('add-item-btn').addEventListener('click', function() {
                let productOptions = '<option value="">-- Produit (optionnel) --</option>';
                productsJson.forEach(p => {
                    productOptions += `<option value="${p.id}">${p.name}</option>`;
                });

                const row = document.createElement('tr');
                row.classList.add('item-row');
                row.innerHTML = `
            <td>
                <input type="text" class="form-control" name="items[${itemIndex}][label]" placeholder="Libellé de l'article" required style="min-width:200px;">
                <select class="form-select form-select-sm mt-1" name="items[${itemIndex}][product_id]">${productOptions}</select>
            </td>
            <td><input type="number" class="form-control item-qty" name="items[${itemIndex}][quantity]" value="1" min="0.001" step="0.001" required style="min-width:80px;"></td>
            <td><input type="number" class="form-control item-cost" name="items[${itemIndex}][unit_cost]" value="0" min="0" step="0.01" required style="min-width:100px;"></td>
            <td><input type="number" class="form-control item-tax" name="items[${itemIndex}][tax_rate]" value="20" min="0" max="100" step="0.01" style="min-width:70px;"></td>
            <td><span class="item-total fw-medium">0.00</span></td>
            <td><a href="javascript:void(0);" class="text-danger remove-item"><i class="isax isax-close-circle"></i></a></td>
        `;
                document.getElementById('items-body').appendChild(row);
                itemIndex++;
                recalc();
            });

            document.getElementById('items-body').addEventListener('click', function(e) {
                if (e.target.closest('.remove-item')) {
                    e.target.closest('.item-row').remove();
                    recalc();
                }
            });

            document.getElementById('items-body').addEventListener('input', function() {
                recalc();
            });

            function recalc() {
                let subtotal = 0,
                    taxTotal = 0;
                document.querySelectorAll('.item-row').forEach(row => {
                    const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
                    const cost = parseFloat(row.querySelector('.item-cost')?.value) || 0;
                    const taxRate = parseFloat(row.querySelector('.item-tax')?.value) || 0;
                    const lineSub = qty * cost;
                    const lineTax = lineSub * taxRate / 100;
                    const lineTotal = lineSub + lineTax;
                    subtotal += lineSub;
                    taxTotal += lineTax;
                    const totalEl = row.querySelector('.item-total');
                    if (totalEl) totalEl.textContent = lineTotal.toFixed(2);
                });
                document.getElementById('display-subtotal').textContent = subtotal.toFixed(2);
                document.getElementById('display-tax').textContent = taxTotal.toFixed(2);
                document.getElementById('display-total').textContent = (subtotal + taxTotal).toFixed(2);
            }

            recalc();
        });
    </script>
@endpush
