<?php $page = 'add-credit-notes'; ?>
@extends('backoffice.layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-md-10 mx-auto">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6><a href="{{ route('bo.sales.credit-notes.index') }}"><i
                                        class="isax isax-arrow-left me-2"></i>Avoirs</a></h6>
                        </div>

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

                        <div class="card">
                            <div class="card-body">
                                <h6 class="mb-3">Créer un avoir</h6>
                                <form action="{{ route('bo.sales.credit-notes.store') }}" method="POST">
                                    @csrf
                                    <div class="border-bottom mb-3 pb-3">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Client <span
                                                            class="text-danger">*</span></label>
                                                    <select name="customer_id"
                                                        class="select @error('customer_id') is-invalid @enderror">
                                                        <option value="">Sélectionner un client</option>
                                                        @foreach ($customers as $customer)
                                                            <option value="{{ $customer->id }}"
                                                                {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                                {{ $customer->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('customer_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Facture liée</label>
                                                    <select name="invoice_id"
                                                        class="select @error('invoice_id') is-invalid @enderror">
                                                        <option value="">Aucune</option>
                                                        @foreach ($invoices as $inv)
                                                            <option value="{{ $inv->id }}"
                                                                {{ old('invoice_id') == $inv->id ? 'selected' : '' }}>
                                                                {{ $inv->number }} — {{ $inv->customer->name ?? '' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('invoice_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Date d'émission <span
                                                            class="text-danger">*</span></label>
                                                    <input type="date" name="issue_date"
                                                        class="form-control @error('issue_date') is-invalid @enderror"
                                                        value="{{ old('issue_date', date('Y-m-d')) }}" required>
                                                    @error('issue_date')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Devise</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}"
                                                        readonly disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div
                                                        class="d-flex align-items-center justify-content-between border rounded p-2 mt-4">
                                                        <div class="form-check form-switch me-4">
                                                            <input class="form-check-input" type="checkbox" role="switch"
                                                                name="enable_tax" id="enable_tax" value="1"
                                                                {{ old('enable_tax', '1') == '1' ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="enable_tax">Activer la
                                                                taxe</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border-bottom mb-3 pb-3">
                                        <h6 class="mb-3">Articles</h6>
                                        <div class="table-responsive rounded border-bottom-0 border mb-3">
                                            <table class="table table-nowrap add-table m-0" id="items-table">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Libellé</th>
                                                        <th>Quantité</th>
                                                        <th>Prix unitaire</th>
                                                        <th>Taxe (%)</th>
                                                        <th>Montant</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="add-tbody">
                                                    <tr class="item-row">
                                                        <td><input type="text" name="items[0][label]"
                                                                class="form-control" value="{{ old('items.0.label') }}"
                                                                placeholder="Libellé" required></td>
                                                        <td><input type="number" name="items[0][quantity]"
                                                                class="form-control"
                                                                value="{{ old('items.0.quantity', 1) }}" min="0.001"
                                                                step="0.001" style="min-width: 80px;" required></td>
                                                        <td><input type="number" name="items[0][unit_price]"
                                                                class="form-control"
                                                                value="{{ old('items.0.unit_price', 0) }}" min="0"
                                                                step="0.01" style="min-width: 100px;" required></td>
                                                        <td><input type="number" name="items[0][tax_rate]"
                                                                class="form-control"
                                                                value="{{ old('items.0.tax_rate', 0) }}" min="0"
                                                                max="100" step="0.01" style="min-width: 80px;"></td>
                                                        <td><input type="text" class="form-control" value="0.00"
                                                                readonly style="min-width: 90px;"></td>
                                                        <td></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div>
                                            <a href="javascript:void(0);" class="d-inline-flex align-items-center"
                                                id="add-item-btn"><i
                                                    class="isax isax-add-circle5 text-primary me-1"></i>Ajouter un
                                                article</a>
                                        </div>
                                    </div>

                                    <div class="border-bottom mb-3 pb-3">
                                        <div class="col-md-12">
                                            <label class="form-label">Notes</label>
                                            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between">
                                        <a href="{{ route('bo.sales.credit-notes.index') }}"
                                            class="btn btn-outline-white">Annuler</a>
                                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @component('backoffice.components.footer')
            @endcomponent
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let itemIndex = 1;
            const tbody = document.querySelector('#items-table .add-tbody');
            document.getElementById('add-item-btn').addEventListener('click', function() {
                const row = document.createElement('tr');
                row.className = 'item-row';
                row.innerHTML = `
                <td><input type="text" name="items[${itemIndex}][label]" class="form-control" placeholder="Libellé" required></td>
                <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control" value="1" min="0.001" step="0.001" style="min-width: 80px;" required></td>
                <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control" value="0" min="0" step="0.01" style="min-width: 100px;" required></td>
                <td><input type="number" name="items[${itemIndex}][tax_rate]" class="form-control" value="0" min="0" max="100" step="0.01" style="min-width: 80px;"></td>
                <td><input type="text" class="form-control" value="0.00" readonly style="min-width: 90px;"></td>
                <td><a href="javascript:void(0);" class="text-danger remove-item"><i class="isax isax-close-circle"></i></a></td>
            `;
                tbody.appendChild(row);
                itemIndex++;
            });
            tbody.addEventListener('click', function(e) {
                const removeBtn = e.target.closest('.remove-item');
                if (removeBtn) {
                    removeBtn.closest('tr').remove();
                }
            });
        });
    </script>
@endpush
