<?php $page = 'subscriptions'; ?>
@extends('backoffice.layout.mainlayout')
@section('content')
    <!-- ========================
               Start Page Content
              ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content content-two">

            <!-- Start Breadcrumb -->
            <div class="d-flex d-block align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h6>Abonnements</h6>
                </div>
                <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
                    <div class="dropdown me-1">
                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="isax isax-export-1 me-1"></i>Exporter
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#">Télécharger en PDF</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">Télécharger en Excel</a>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <a href="javascript:void(0);" class="btn btn-primary d-flex align-items-center"
                            data-bs-toggle="modal" data-bs-target="#add_subscription"><i
                                class="isax isax-add-circle5 me-1"></i>Nouvel Abonnement</a>
                    </div>
                </div>
            </div>
            <!-- End Breadcrumb -->

            <!-- start row -->
            <div class="row">
                <div class="col-lg-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center overflow-hidden">
                                <div>
                                    <p class="fs-14 mb-1 text-truncate">Total Abonnements</p>
                                    <h4 class="fs-16 fw-semibold">{{ $totalSubscriptions }}</h4>
                                </div>
                            </div>
                            <div>
                                <span class="avatar avatar-lg bg-warning flex-shrink-0">
                                    <i class="isax isax-receipt-25 fs-32"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center overflow-hidden">
                                <div>
                                    <p class="fs-14 mb-1 text-truncate">Abonnements Actifs</p>
                                    <h4 class="fs-16 fw-semibold">{{ $activeSubscriptions }}</h4>
                                </div>
                            </div>
                            <div>
                                <span class="avatar avatar-lg bg-success flex-shrink-0">
                                    <i class="isax isax-tick-circle5 fs-32"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center overflow-hidden">
                                <div>
                                    <p class="fs-14 mb-1 text-truncate">En Période d'Essai</p>
                                    <h4 class="fs-16 fw-semibold">{{ $trialingSubscriptions }}</h4>
                                </div>
                            </div>
                            <div>
                                <span class="avatar avatar-lg bg-info flex-shrink-0">
                                    <i class="isax isax-timer-15 fs-32"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center overflow-hidden">
                                <div>
                                    <p class="fs-14 mb-1 text-truncate">Annulés</p>
                                    <h4 class="fs-16 fw-semibold">{{ $cancelledSubscriptions }}</h4>
                                </div>
                            </div>
                            <div>
                                <span class="avatar avatar-lg bg-danger flex-shrink-0">
                                    <i class="isax isax-close-circle5 fs-32"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end row -->

            <!-- Start Table Search -->
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <div class="table-search d-flex align-items-center mb-0">
                            <div class="search-input">
                                <a href="javascript:void(0);" class="btn-searchset"><i
                                        class="isax isax-search-normal fs-12"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        @include('backoffice.components.column-toggle', [
                            'columns' => ['Entreprise', 'Plan', 'Statut', 'Début', 'Fin', 'Prochaine facturation'],
                        ])
                        <div class="dropdown me-2">
                            <a href="javascript:void(0);"
                                class="dropdown-toggle btn btn-outline-white d-inline-flex align-items-center fw-medium"
                                data-bs-toggle="dropdown">
                                <i class="isax isax-sort me-1"></i>Trier par : <span class="fw-normal ms-1">Plus
                                    récent</span>
                            </a>
                            <ul class="dropdown-menu  dropdown-menu-end">
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item">Plus récent</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item">Plus ancien</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Table Search -->

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif

            <!-- Start Table List -->
            @include('backoffice.subscriptions.partials._table')
            <!-- End Table List -->

        </div>
        <!-- End Content -->

        @component('backoffice.components.footer')
        @endcomponent

    </div>

    <!-- Modals -->
    @include('backoffice.subscriptions.partials._modals')

    @if ($errors->any() && old('_modal') === 'add_subscription')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('add_subscription'));
                modal.show();
            });
        </script>
    @endif

    <!-- ========================
               End Page Content
              ========================= -->
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const plans = @json($plans->keyBy('id')->map(fn($p) => ['price' => $p->price, 'currency' => $p->currency]));
            const addModal = document.getElementById('add_subscription');
            if (addModal) {
                const planSelect = addModal.querySelector('select[name="plan_id"]');
                const discountInput = addModal.querySelector('input[name="discount"]');
                const priceHint = document.getElementById('add-sub-final-price');

                function updatePrice() {
                    const planId = planSelect.value;
                    const discount = parseFloat(discountInput.value) || 0;
                    if (planId && plans[planId]) {
                        const plan = plans[planId];
                        const final_price = Math.max(0, plan.price - discount);
                        priceHint.textContent = 'Prix plan : ' + plan.price.toFixed(2) + ' ' + plan.currency +
                            ' → Prix final : ' + final_price.toFixed(2) + ' ' + plan.currency;
                    } else {
                        priceHint.textContent = '';
                    }
                }

                planSelect.addEventListener('change', updatePrice);
                discountInput.addEventListener('input', updatePrice);
                updatePrice();
            }
        });
    </script>
@endpush
