<?php $page = 'super-admin-dashboard'; ?>
@extends('backoffice.layout.mainlayout')
@section('content')
    <!-- ========================
            Start Page Content
        ========================= -->

    <div class="page-wrapper">
        <div class="content">

            <!-- Breadcrumb Start-->
            <div class="d-flex d-block align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h6>Tableau de bord</h6>
                </div>
            </div>
            <!-- Breadcrumb End -->

            <!-- row start -->
            <div class="row">
                <!-- welcome -->
                <div class="col-xl-4">
                    <div class="card bg-primary rounded-3 px-3 position-relative z-0">
                        <img src="{{ URL::asset('build/img/icons/dashboard-icon-02.svg') }}" alt="img"
                            class="dashboard-bg-2 d-lg-flex d-none">
                        <div class="row">
                            <div class="col-lg-12 py-3">
                                <h5 class="text-white d-inline-flex align-items-center mb-2 text-truncate line-clamb-1"><i
                                        class="isax isax-sun-1 fs-20 me-1"></i>Bonjour, {{ auth()->user()->name }}</h5>
                                <p class="fs-16 text-white mb-lg-5 mb-3 text-truncate">{{ $totalTenants }} entreprise(s) inscrite(s) au total</p>
                                <div class="d-flex align-items-center">
                                    <a href="{{ route('sa.tenants.index') }}"
                                        class="btn btn-sm btn-blue fw-medium me-2 px-xl-2 px-lg-3">Voir les entreprises</a>
                                    <a href="{{ route('sa.plans.index') }}"
                                        class="btn btn-sm btn-outline-blue fw-medium px-xl-2 px-lg-3">Tous les forfaits</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- welcome -->

                <!-- widget -->
                <div class="col-xl-8">
                    <!-- row start -->
                    <div class="row">
                        <div class="col-md-3 d-flex">
                            <div class="card bg-light shadow-none flex-fill w-100 rounded-3">
                                <div class="card-body p-3">
                                    <div class="avatar avatar-xl bg-white rounded-3 mb-3">
                                        <img src="{{ URL::asset('build/img/icons/info-icon-01.svg') }}" alt="img"
                                            class="rounded-3 img-fluid w-auto h-auto">
                                    </div>
                                    <p class="mb-1 text-gray-9 text-truncate">Total entreprises</p>
                                    <h6 class="mb-1 fs-16 fw-semibold">{{ number_format($totalTenants) }}</h6>
                                    <p class="fs-13 mb-0 text-truncate"><span class="text-success fs-14"><i
                                                class="isax isax-send text-success me-1"></i>{{ $activeTenants }}</span> actives</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex">
                            <div class="card bg-light shadow-none flex-fill w-100 rounded-3">
                                <div class="card-body p-3">
                                    <div class="avatar avatar-xl bg-white rounded-3 mb-3">
                                        <img src="{{ URL::asset('build/img/icons/info-icon-02.svg') }}" alt="img"
                                            class="rounded-3 img-fluid w-auto h-auto">
                                    </div>
                                    <p class="mb-1 text-gray-9 text-truncate">Entreprises actives</p>
                                    <h6 class="mb-1 fs-16 fw-semibold">{{ number_format($activeTenants) }}</h6>
                                    <p class="fs-13 mb-0 text-truncate"><span class="text-success fs-14"><i
                                                class="isax isax-send text-success me-1"></i>{{ $totalTenants > 0 ? round(($activeTenants / $totalTenants) * 100, 1) : 0 }}%</span> du total</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex">
                            <div class="card bg-light shadow-none flex-fill w-100 rounded-3">
                                <div class="card-body p-3">
                                    <div class="avatar avatar-xl bg-white rounded-3 mb-3">
                                        <img src="{{ URL::asset('build/img/icons/info-icon-03.svg') }}" alt="img"
                                            class="rounded-3 img-fluid w-auto h-auto">
                                    </div>
                                    <p class="mb-1 text-gray-9 text-truncate">Entreprises suspendues</p>
                                    <h6 class="mb-1 fs-16 fw-semibold">{{ number_format($suspendedTenants) }}</h6>
                                    <p class="fs-13 mb-0 text-truncate"><span class="text-danger fs-14"><i
                                                class="isax isax-send text-danger me-1"></i>{{ $totalTenants > 0 ? round(($suspendedTenants / $totalTenants) * 100, 1) : 0 }}%</span> du total</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex">
                            <div class="card bg-light shadow-none flex-fill w-100 rounded-3">
                                <div class="card-body p-3">
                                    <div class="avatar avatar-xl bg-white rounded-3 mb-3">
                                        <img src="{{ URL::asset('build/img/icons/info-icon-04.svg') }}" alt="img"
                                            class="rounded-3 img-fluid w-auto h-auto">
                                    </div>
                                    <p class="mb-1 text-gray-9 text-truncate">Forfaits actifs</p>
                                    <h6 class="mb-1 fs-16 fw-semibold">{{ number_format($activePlans) }}</h6>
                                    <p class="fs-13 mb-0 text-truncate"><span class="text-primary fs-14"><i
                                                class="isax isax-crown me-1"></i></span> disponibles</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- row end -->
                </div>
                <!-- widget -->
            </div>
            <!-- row end -->
            <!-- row start -->
            <div class="row">
                <div class="col-xl-4 d-flex">
                    <div class="card rounded-3 shadow-none flex-fill w-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3">
                                <h6 class="fs-16 fw-semibold text-truncate">Forfait le plus commandé</h6>
                            </div>
                            @if($mostOrderedPlan)
                            <div class="bg-light rounded-3 p-3">
                                <div
                                    class="d-flex align-items-center mb-2 justify-content-between gap-2 flex-wrap flex-md-nowrap">
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-lg bg-white rounded-3">
                                            <img src="{{ URL::asset('build/img/icons/company-logo-01.svg') }}"
                                                alt="img" class="rounded-3 img-fluid w-auto h-auto">
                                        </span>
                                        <div class="ms-2">
                                            <p class="mb-1"><span class="text-gray-9 fw-medium">{{ $mostOrderedPlan->name }}</span>
                                                ({{ ucfirst($mostOrderedPlan->interval ?? 'monthly') }})</p>
                                            <p class="mb-0">Total commandes : {{ $mostOrderedPlan->subscriptions_count }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <p class="text-gray-9">{{ number_format($mostOrderedPlan->price, 2) }} {{ $mostOrderedPlan->currency ?? 'MAD' }}</p>
                                </div>
                            </div>
                            @else
                            <p class="text-muted">Aucun forfait trouvé.</p>
                            @endif
                        </div>
                        <!-- card body end -->
                    </div>
                    <!-- card end -->
                </div>
                <div class="col-xl-4 d-flex">
                    <div class="card rounded-3 shadow-none flex-fill w-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3">
                                <h6 class="fs-16 fw-semibold text-truncate">Meilleure entreprise</h6>
                            </div>
                            @if($topTenant)
                            <div class="bg-light rounded-3 p-3">
                                <div
                                    class="d-flex align-items-center mb-2 justify-content-between gap-2 flex-wrap flex-lg-nowrap">
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-lg bg-white rounded-3">
                                            <img src="{{ $topTenant->logo_url }}"
                                                alt="img" class="rounded-3 img-fluid w-auto h-auto">
                                        </span>
                                        <div class="ms-2">
                                            <p class="mb-1 fw-medium text-gray-9">{{ $topTenant->name }}</p>
                                            <p class="mb-0 text-truncate">{{ $topTenant->slug }}.{{ request()->getHost() }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <p class="text-gray-9 flex-shrink-0">{{ $topTenant->subscriptions_count ?? 0 }} abonnements</p>
                                </div>
                            </div>
                            @else
                            <p class="text-muted">Aucune entreprise trouvée.</p>
                            @endif
                        </div>
                        <!-- card body end -->
                    </div>
                    <!-- card end -->
                </div>
                <div class="col-xl-4 d-flex">
                    <div class="card rounded-3 shadow-none flex-fill w-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3">
                                <h6 class="fs-16 fw-semibold text-truncate">Revenus</h6>
                            </div>
                            <div class="bg-light rounded-3 p-3">
                                <div class="d-flex align-items-center mb-2 justify-content-between gap-2 flex-wrap">
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-lg bg-white rounded-3">
                                            <i class="isax isax-money-4 fs-24 text-primary"></i>
                                        </span>
                                        <div class="ms-2">
                                            <p class="mb-1 fw-medium text-gray-9">Revenu total</p>
                                            <p class="mb-0">{{ number_format($totalRevenue, 2) }} MAD</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <p class="text-gray-9">Ce mois : {{ number_format($revenueMtd, 2) }} MAD</p>
                                </div>
                            </div>
                        </div>
                        <!-- card body end -->
                    </div>
                    <!-- card end -->
                </div>
            </div>
            <!-- row end -->
            <!-- row start -->
            <div class="row">
                <div class="col-xl-6 d-flex">
                    <div class="card shadow-none rounded-3 flex-fill w-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                <h6>Dernières entreprises inscrites</h6>
                                <a href="{{ route('sa.tenants.index') }}" class="btn btn-sm btn-dark">Voir tout</a>
                            </div>
                            <!-- Table List -->
                            <div class="table-responsive no-filter no-paginaion">
                                <table class="table table-nowrap">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Entreprise</th>
                                            <th>Forfait</th>
                                            <th>Date d'inscription</th>
                                            <th class="no-sort">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($latestTenants as $tenant)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);"
                                                        class="avatar avatar-sm rounded-circle me-2 flex-shrink-0">
                                                        <img src="{{ $tenant->logo_url }}"
                                                            class="rounded-circle" alt="img">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 fw-medium mb-0"><a
                                                                href="{{ route('sa.tenants.show', $tenant) }}">{{ $tenant->name }}</a></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($tenant->latest_subscription && $tenant->latest_subscription->plan)
                                                    {{ $tenant->latest_subscription->plan->name }}
                                                    ({{ ucfirst($tenant->latest_subscription->plan->interval ?? '') }})
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $tenant->created_at->format('d M Y') }}</td>
                                            <td>
                                                @if($tenant->status === 'active')
                                                    <span class="badge badge-sm badge-soft-success d-inline-flex align-items-center">Actif
                                                        <i class="isax isax-tick-circle ms-1"></i>
                                                    </span>
                                                @elseif($tenant->status === 'suspended')
                                                    <span class="badge badge-sm badge-soft-danger d-inline-flex align-items-center">Suspendu
                                                        <i class="isax isax-close-circle ms-1"></i>
                                                    </span>
                                                @else
                                                    <span class="badge badge-sm badge-soft-warning d-inline-flex align-items-center">{{ ucfirst($tenant->status) }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Aucune entreprise inscrite.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <!-- /Table List -->
                        </div>
                        <!-- card body end -->
                    </div>
                    <!-- card end  -->
                </div>

                <!-- earnings -->
                <div class="col-xl-6 d-flex">
                    <div class="card shadow-none rounded-3 flex-fill w-100">
                        <div class="card-body pb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="fw-semibold fs-16">Revenus mensuels</h6>
                                <div class="d-flex align-items-center">
                                    <p class="d-inline-flex align-items-center me-4 mb-0"><i
                                            class="fa-solid fa-square text-primary fs-12 me-1"></i>Revenus</p>
                                </div>
                            </div>
                            <div id="sa-earnings-chart" class="chart-set"></div>
                        </div>
                        <!-- card body end -->
                    </div>
                    <!-- card end -->
                </div>
                <!-- earnings -->
            </div>
            <!-- row end -->
            <!-- row start -->
            <div class="row">
                <!-- recent plan expired -->
                <div class="col-lg-7 d-flex">
                    <div class="card shadow-none w-100 rounded-3">
                        <div class="card-body">
                            <h6 class="mb-3">Abonnements récemment expirés</h6>
                            <!-- Table List -->
                            <div class="table-responsive no-filter no-paginaion">
                                <table class="table table-nowrap">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Entreprise</th>
                                            <th>Forfait</th>
                                            <th>Expiré le</th>
                                            <th class="no-sort">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($expiredSubscriptions as $sub)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="javascript:void(0);"
                                                        class="avatar avatar-sm rounded-circle me-2 flex-shrink-0">
                                                        <img src="{{ $sub->tenant?->logo_url ?? asset('build/img/icons/company-logo-01.svg') }}"
                                                            class="rounded-circle" alt="img">
                                                    </a>
                                                    <div>
                                                        <h6 class="fs-14 fw-medium mb-0"><a
                                                                href="{{ route('sa.tenants.show', $sub->tenant) }}">{{ $sub->tenant?->name ?? '—' }}</a></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $sub->plan?->name ?? '—' }} ({{ ucfirst($sub->plan?->interval ?? '') }})</td>
                                            <td>{{ $sub->ends_at?->format('d M Y') ?? '—' }}</td>
                                            <td>
                                                <span class="badge badge-sm badge-soft-danger d-inline-flex align-items-center">Expiré
                                                    <i class="isax isax-close-circle ms-1"></i>
                                                </span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Aucun abonnement expiré.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <!-- /Table List -->
                        </div>
                        <!-- card body end -->
                    </div>
                    <!-- card end  -->
                </div>

                <!-- recent invoices -->
                <div class="col-lg-5 d-flex">
                    <div class="card shadow-none w-100 rounded-3">
                        <div class="card-body">
                            <h6 class="mb-3">Dernières factures d'abonnement</h6>
                            <!-- Table List -->
                            <div class="table-responsive no-filter no-paginaion">
                                <table class="table table-nowrap">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>N°</th>
                                            <th>Entreprise</th>
                                            <th>Montant</th>
                                            <th class="no-sort">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentInvoices as $inv)
                                        <tr>
                                            <td><span class="link-default">#{{ substr($inv->id, 0, 8) }}</span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h6 class="fs-14 fw-medium mb-0">{{ $inv->subscription?->tenant?->name ?? '—' }}</h6>
                                                        <small class="text-muted">{{ $inv->subscription?->plan?->name ?? '' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ number_format($inv->amount, 2) }} MAD</td>
                                            <td>
                                                @if($inv->status === 'paid')
                                                    <span class="badge badge-sm badge-soft-success d-inline-flex align-items-center">Payée
                                                        <i class="isax isax-tick-circle ms-1"></i>
                                                    </span>
                                                @elseif($inv->status === 'pending')
                                                    <span class="badge badge-sm badge-soft-warning d-inline-flex align-items-center">En attente
                                                    </span>
                                                @else
                                                    <span class="badge badge-sm badge-soft-danger d-inline-flex align-items-center">{{ ucfirst($inv->status) }}
                                                        <i class="isax isax-close-circle ms-1"></i>
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Aucune facture trouvée.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <!-- /Table List -->
                        </div>
                        <!-- card body end -->
                    </div>
                    <!-- card end  -->
                </div>
            </div>
            <!-- row end -->
        </div>

        <!-- Start Footer-->
        <div class="footer d-sm-flex align-items-center justify-content-between bg-white py-2 px-4 border-top">
            <p class="text-dark mb-0">&copy; {{ date('Y') }} Facturation SaaS</p>
        </div>
        <!-- End Footer-->

    </div>

    <!-- ========================
            End Page Content
        ========================= -->
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ─── Earnings Chart (ApexCharts) ───
    var earningsData = @json($earningsTrend);
    var months = earningsData.map(function(item) { return item.month; });
    var totals = earningsData.map(function(item) { return parseFloat(item.total); });

    if (document.querySelector('#sa-earnings-chart')) {
        var options = {
            series: [{
                name: 'Revenus',
                data: totals
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '50%'
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: months,
                labels: {
                    formatter: function(val) {
                        if (!val) return '';
                        var parts = val.split('-');
                        var monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
                        return monthNames[parseInt(parts[1]) - 1] || val;
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return val.toLocaleString('fr-FR') + ' MAD';
                    }
                }
            },
            colors: ['#405189'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' MAD';
                    }
                }
            }
        };

        var chart = new ApexCharts(document.querySelector('#sa-earnings-chart'), options);
        chart.render();
    }
});
</script>
@endsection
