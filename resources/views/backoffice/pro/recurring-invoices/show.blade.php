<?php $page = 'recurring-invoices'; ?>
@extends('backoffice.layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-md-10 mx-auto">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6><a href="{{ route('bo.pro.recurring-invoices.index') }}"><i
                                        class="isax isax-arrow-left me-2"></i>Factures récurrentes</a></h6>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h5>Facture récurrente</h5>
                                    <a href="{{ route('bo.pro.recurring-invoices.edit', $recurringInvoice) }}"
                                        class="btn btn-sm btn-outline-primary"><i
                                            class="isax isax-edit me-1"></i>Modifier</a>
                                </div>
                                <div class="row gx-3 mb-4">
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-muted">Client</label>
                                        <p class="fw-medium mb-0">{{ $recurringInvoice->customer->name ?? '—' }}</p>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-muted">Facture modèle</label>
                                        <p class="fw-medium mb-0">{{ $recurringInvoice->templateInvoice->number ?? '—' }}
                                        </p>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-muted">Fréquence</label>
                                        <p class="fw-medium mb-0">Tous les {{ $recurringInvoice->every }}
                                            {{ $recurringInvoice->interval === 'month' ? 'mois' : ($recurringInvoice->interval === 'week' ? 'semaines' : 'ans') }}
                                        </p>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-muted">Prochaine exécution</label>
                                        <p class="fw-medium mb-0">
                                            {{ $recurringInvoice->next_run_at ? \Carbon\Carbon::parse($recurringInvoice->next_run_at)->format('d/m/Y') : '—' }}
                                        </p>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-muted">Statut</label>
                                        <p class="mb-0">
                                            @switch($recurringInvoice->status)
                                                @case('active')
                                                    <span class="badge badge-soft-success">Actif</span>
                                                @break

                                                @case('paused')
                                                    <span class="badge badge-soft-warning">En pause</span>
                                                @break

                                                @case('cancelled')
                                                    <span class="badge badge-soft-danger">Annulé</span>
                                                @break
                                            @endswitch
                                        </p>
                                    </div>
                                </div>
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
