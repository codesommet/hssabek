<?php $page = 'expense-report'; ?>
@extends('backoffice.layout.mainlayout')
@section('content')
    <!-- Based on expense-report.blade.php layout -->

    <div class="page-wrapper">
        <!-- Start Content -->
        <div class="content content-two">

            <!-- Breadcrumb -->
            <div class="d-flex d-block align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h6 class="mb-0">Rapport financier</h6>
                </div>
                <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
                    <div class="dropdown me-1">
                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="isax isax-export-1 me-1"></i>Exporter
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <form method="POST" action="{{ route('bo.reports.finance.export') }}">
                                    @csrf
                                    <input type="hidden" name="from" value="{{ $from }}">
                                    <input type="hidden" name="to" value="{{ $to }}">
                                    <button class="dropdown-item" type="submit">Télécharger en CSV</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- /Breadcrumb -->

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <p class="mb-1">Total revenus</p>
                                    <h6 class="fs-16 fw-semibold mb-0">{{ number_format($totalIncome, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</h6>
                                </div>
                                <div>
                                    <span class="badge badge-soft-success report-icon avatar-md border border-success rounded p-2 d-inline-flex align-items-center justify-content-center">
                                        <i class="isax isax-dollar-circle fs-16"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="bg-light py-2 px-3 rounded">
                                <p class="fs-13 mb-0">
                                    <span class="text-muted">{{ $from }} - {{ $to }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <p class="mb-1">Total dépenses</p>
                                    <h6 class="fs-16 fw-semibold mb-0">{{ number_format($totalExpenses, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</h6>
                                </div>
                                <div>
                                    <span class="badge badge-soft-danger report-icon avatar-md border border-danger rounded p-2 d-inline-flex align-items-center justify-content-center">
                                        <i class="isax isax-dollar-circle fs-16"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="bg-light py-2 px-3 rounded">
                                <p class="fs-13 mb-0">
                                    <span class="text-muted">Période sélectionnée</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <p class="mb-1">Bénéfice net</p>
                                    <h6 class="fs-16 fw-semibold mb-0 {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($netProfit, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</h6>
                                </div>
                                <div>
                                    <span class="badge badge-soft-{{ $netProfit >= 0 ? 'primary' : 'warning' }} report-icon avatar-md border border-{{ $netProfit >= 0 ? 'primary' : 'warning' }} rounded p-2 d-inline-flex align-items-center justify-content-center">
                                        <i class="isax isax-chart fs-16"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="bg-light py-2 px-3 rounded">
                                <p class="fs-13 mb-0">
                                    <span class="text-muted">Revenus - Dépenses</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card position-relative">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <p class="mb-1">Catégories dépenses</p>
                                    <h6 class="fs-16 fw-semibold mb-0">{{ $expensesByCategory->count() }}</h6>
                                </div>
                                <div>
                                    <span class="badge badge-soft-info report-icon avatar-md border border-info rounded p-2 d-inline-flex align-items-center justify-content-center">
                                        <i class="isax isax-grid-3 fs-16"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="bg-light py-2 px-3 rounded">
                                <p class="fs-13 mb-0">
                                    <span class="text-muted">Catégories actives</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Date Range Filter -->
            <div class="mb-3">
                <form method="GET" action="{{ route('bo.reports.finance') }}" class="d-flex align-items-center gap-2 flex-wrap">
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
                        <a href="{{ route('bo.reports.finance') }}" class="btn btn-outline-white">Réinitialiser</a>
                    </div>
                </form>
            </div>

            <!-- Expenses by Category -->
            @if($expensesByCategory->count() > 0)
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Dépenses par catégorie</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th>Total</th>
                                    <th>% du total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expensesByCategory as $cat)
                                <tr>
                                    <td>{{ $cat->category?->name ?? 'Non catégorisé' }}</td>
                                    <td class="text-dark">{{ number_format($cat->total, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</td>
                                    <td>{{ $totalExpenses > 0 ? number_format(($cat->total / $totalExpenses) * 100, 1) : 0 }}%</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Incomes by Category -->
            @if($incomesByCategory->count() > 0)
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Revenus par catégorie</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th>Total</th>
                                    <th>% du total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($incomesByCategory as $cat)
                                <tr>
                                    <td>{{ $cat->category?->name ?? 'Non catégorisé' }}</td>
                                    <td class="text-dark">{{ number_format($cat->total, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</td>
                                    <td>{{ $totalIncome > 0 ? number_format(($cat->total / $totalIncome) * 100, 1) : 0 }}%</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Expense Detail Table -->
            <div class="table-responsive">
                <table class="table table-nowrap datatable">
                    <thead>
                        <tr>
                            <th class="no-sort">
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th>N° Dépense</th>
                            <th>Catégorie</th>
                            <th>Fournisseur</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th class="no-sort">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <a href="javascript:void(0);" class="text-default">{{ $expense->expense_number }}</a>
                                </td>
                                <td>{{ $expense->category?->name ?? '-' }}</td>
                                <td>{{ $expense->supplier?->name ?? '-' }}</td>
                                <td>{{ $expense->expense_date?->format('d/m/Y') }}</td>
                                <td class="text-dark">{{ number_format($expense->amount, 2, ',', ' ') }} {{ App\Services\Tenancy\TenantContext::get()?->default_currency ?? 'MAD' }}</td>
                                <td>
                                    @switch($expense->payment_status)
                                        @case('paid')
                                            <span class="badge badge-soft-success d-inline-flex align-items-center">Payée <i class="isax isax-tick-circle ms-1"></i></span>
                                            @break
                                        @case('pending')
                                            <span class="badge badge-soft-warning d-inline-flex align-items-center">En attente <i class="isax isax-timer ms-1"></i></span>
                                            @break
                                        @default
                                            <span class="badge badge-soft-secondary d-inline-flex align-items-center">{{ ucfirst($expense->payment_status) }}</span>
                                    @endswitch
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Aucune dépense trouvée pour cette période.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($expenses->hasPages())
                <div class="mt-3">
                    {{ $expenses->links() }}
                </div>
            @endif

            @component('backoffice.components.footer')
            @endcomponent
        </div>
    </div>
@endsection
