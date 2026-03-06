<?php $page = 'customers-report'; ?>
@extends('backoffice.layout.mainlayout')
@section('content')
    <!-- Based on customers-report.blade.php layout -->

    <div class="page-wrapper">

        <div class="content content-two">

            <!-- Page Header -->
            <div class="d-flex d-block align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h6 class="mb-0">Rapport des clients</h6>
                </div>
                <div class="my-xl-auto">
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="isax isax-export-1 me-1"></i>Exporter
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="javascript:void(0);">Télécharger en PDF</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="javascript:void(0);">Télécharger en Excel</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- End Page Header -->

            <div class="border-bottom mb-3">
                <!-- start row -->
                <div class="row">
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div
                            class="card shadow-lg position-relative border-0 border-end border-bottom border-3 border-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1">Total clients</p>
                                        <h6 class="fs-16 fw-semibold mb-0">{{ number_format($totalCustomers) }}</h6>
                                    </div>
                                    <div>
                                        <span class="badge badge-soft-primary p-2 rounded-circle border border-primary">
                                            <i class="isax isax-profile-2user fs-16"></i>
                                        </span>
                                    </div>
                                </div>
                                <p class="fs-13 mb-0">
                                    <span class="text-muted">Tous les clients actifs</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div
                            class="card shadow-lg position-relative border-0 border-end border-bottom border-3 border-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1">Nouveaux clients</p>
                                        <h6 class="fs-16 fw-semibold mb-0">{{ number_format($newCustomers) }}</h6>
                                    </div>
                                    <div>
                                        <span class="badge badge-soft-success p-2 rounded-circle border border-success">
                                            <i class="isax isax-profile-2user fs-16"></i>
                                        </span>
                                    </div>
                                </div>
                                <p class="fs-13 mb-0">
                                    <span class="text-muted">{{ $from }} - {{ $to }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div
                            class="card shadow-lg position-relative border-0 border-end border-bottom border-3 border-warning">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1">Revenu total</p>
                                        <h6 class="fs-16 fw-semibold mb-0">{{ number_format($totalRevenue, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</h6>
                                    </div>
                                    <div>
                                        <span class="badge badge-soft-warning p-2 rounded-circle border border-warning">
                                            <i class="isax isax-dollar-circle fs-16"></i>
                                        </span>
                                    </div>
                                </div>
                                <p class="fs-13 mb-0">
                                    <span class="text-muted">Période sélectionnée</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div
                            class="card shadow-lg position-relative border-0 border-end border-bottom border-3 border-info">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1">Revenu moyen</p>
                                        <h6 class="fs-16 fw-semibold mb-0">{{ number_format($avgRevenue, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</h6>
                                    </div>
                                    <div>
                                        <span class="badge badge-soft-info p-2 rounded-circle border border-info">
                                            <i class="isax isax-dollar-circle fs-16"></i>
                                        </span>
                                    </div>
                                </div>
                                <p class="fs-13 mb-0">
                                    <span class="text-muted">Par client</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end row -->
            </div>

            <!-- Date Range Filter -->
            <div class="mb-3">
                <form method="GET" action="{{ route('bo.reports.customers') }}" class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div>
                            <input type="date" name="from" class="form-control" value="{{ $from }}">
                        </div>
                        <div>
                            <input type="date" name="to" class="form-control" value="{{ $to }}">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="isax isax-filter me-1"></i>Filtrer
                        </button>
                        <a href="{{ route('bo.reports.customers') }}" class="btn btn-outline-white">Réinitialiser</a>
                    </div>
                </form>
            </div>

            <!-- Table List -->
            <div class="table-responsive">
                <table class="table table-nowrap datatable">
                    <thead class="thead-light">
                        <tr>
                            <th class="no-sort">
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th>Client</th>
                            <th>Téléphone</th>
                            <th>Solde dû</th>
                            <th>Nb factures</th>
                            <th>Revenu total</th>
                            <th>Créé le</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <h6 class="fs-14 fw-medium mb-0">
                                                <a href="{{ route('bo.crm.customers.show', $customer) }}">{{ $customer->name }}</a>
                                            </h6>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $customer->phone ?? '-' }}</td>
                                <td class="text-dark">{{ number_format($customer->total_due ?? 0, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</td>
                                <td>{{ $customer->invoices_count ?? 0 }}</td>
                                <td class="text-dark">{{ number_format($customer->total_revenue ?? 0, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</td>
                                <td>{{ $customer->created_at?->format('d/m/Y') }}</td>
                                <td>
                                    @if($customer->status === 'active')
                                        <span class="badge badge-soft-success d-inline-flex align-items-center">Actif <i class="isax isax-tick-circle ms-1"></i></span>
                                    @else
                                        <span class="badge badge-soft-danger d-inline-flex align-items-center">Inactif <i class="isax isax-close-circle ms-1"></i></span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Aucun client trouvé pour cette période.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($customers->hasPages())
                <div class="mt-3">
                    {{ $customers->links() }}
                </div>
            @endif

            @component('backoffice.components.footer')
            @endcomponent
        </div>
    </div>
@endsection
