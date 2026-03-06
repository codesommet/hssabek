<?php $page = 'purchases-report'; ?>
@extends('backoffice.layout.mainlayout')
@section('content')
    <!-- Based on purchases-report.blade.php layout -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content content-two">

            <!-- Start Page Header -->
            <div class="d-flex d-block align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h6 class="mb-0">Rapport des achats</h6>
                </div>
                <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
                    <div class="dropdown me-1">
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
                        <div class="card position-relative">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-1 text-truncate">Total achats</p>
                                        <h6 class="fs-16 fw-semibold mb-0">{{ number_format($totalPurchases, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</h6>
                                    </div>
                                    <div>
                                        <span class="badge badge-soft-primary report-icon avatar-md border border-primary rounded p-2 d-inline-flex align-items-center justify-content-center">
                                            <i class="isax isax-dollar-circle fs-16"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card position-relative">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-1 text-truncate">Achats payés</p>
                                        <h6 class="fs-16 fw-semibold mb-0">{{ number_format($paidPurchases, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</h6>
                                    </div>
                                    <div>
                                        <span class="badge badge-soft-success report-icon avatar-md border border-success rounded p-2 d-inline-flex align-items-center justify-content-center">
                                            <i class="isax isax-tick-circle fs-16"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card position-relative">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-1 text-truncate">Achats en attente</p>
                                        <h6 class="fs-16 fw-semibold mb-0">{{ number_format($pendingPurchases, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</h6>
                                    </div>
                                    <div>
                                        <span class="badge badge-soft-warning report-icon avatar-md border border-warning rounded p-2 d-inline-flex align-items-center justify-content-center">
                                            <i class="isax isax-timer fs-16"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card position-relative">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-1 text-truncate">Achats annulés</p>
                                        <h6 class="fs-16 fw-semibold mb-0">{{ number_format($cancelledPurchases, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</h6>
                                    </div>
                                    <div>
                                        <span class="badge badge-soft-danger report-icon avatar-md border border-danger rounded p-2 d-inline-flex align-items-center justify-content-center">
                                            <i class="isax isax-close-circle fs-16"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end row -->
            </div>

            <!-- Date Range Filter -->
            <div class="mb-3">
                <form method="GET" action="{{ route('bo.reports.purchases') }}" class="d-flex align-items-center gap-2 flex-wrap">
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
                        <a href="{{ route('bo.reports.purchases') }}" class="btn btn-outline-white">Réinitialiser</a>
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
                            <th class="no-sort">N°</th>
                            <th>Date</th>
                            <th>Fournisseur</th>
                            <th>Total</th>
                            <th>Payé</th>
                            <th>Restant</th>
                            <th class="no-sort">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendorBills as $bill)
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <a href="javascript:void(0);" class="link-default">{{ $bill->number }}</a>
                                </td>
                                <td>{{ $bill->issue_date?->format('d/m/Y') }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <h6 class="fs-14 fw-medium mb-0">{{ $bill->supplier?->name ?? '-' }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-dark">{{ number_format($bill->total, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</td>
                                <td class="text-dark">{{ number_format($bill->amount_paid, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</td>
                                <td class="text-dark">{{ number_format($bill->amount_due, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</td>
                                <td>
                                    @switch($bill->status)
                                        @case('paid')
                                            <span class="badge badge-soft-success d-inline-flex align-items-center">Payée <i class="isax isax-tick-circle ms-1"></i></span>
                                            @break
                                        @case('sent')
                                        @case('partial')
                                        @case('draft')
                                            <span class="badge badge-soft-warning d-inline-flex align-items-center">{{ ucfirst($bill->status) }} <i class="isax isax-timer ms-1"></i></span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge badge-soft-danger d-inline-flex align-items-center">Annulée <i class="isax isax-close-circle ms-1"></i></span>
                                            @break
                                        @default
                                            <span class="badge badge-soft-secondary d-inline-flex align-items-center">{{ ucfirst($bill->status) }}</span>
                                    @endswitch
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Aucune facture fournisseur trouvée pour cette période.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($vendorBills->hasPages())
                <div class="mt-3">
                    {{ $vendorBills->links() }}
                </div>
            @endif

            @component('backoffice.components.footer')
            @endcomponent
        </div>
    </div>
@endsection
