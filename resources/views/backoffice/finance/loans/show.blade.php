<?php $page = 'loans'; ?>
@extends('backoffice.layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content">
            <div class="row">
                <div class="col-md-10 mx-auto">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6><a href="{{ route('bo.finance.loans.index') }}"><i
                                        class="isax isax-arrow-left me-2"></i>Prêts</a></h6>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h5>Prêt — {{ $loan->reference_number }}</h5>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('bo.finance.loans.edit', $loan) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="isax isax-edit me-1"></i>Modifier
                                        </a>
                                    </div>
                                </div>

                                <div class="row gx-3 mb-4">
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-muted">Prêteur</label>
                                        <p class="fw-medium mb-0">{{ $loan->lender_name }}
                                            <small
                                                class="text-muted">({{ $loan->lender_type === 'bank' ? 'Banque' : ($loan->lender_type === 'institution' ? 'Institution' : 'Particulier') }})</small>
                                        </p>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-muted">Montant principal</label>
                                        <p class="fw-semibold mb-0">
                                            {{ number_format($loan->principal_amount, 2, ',', ' ') }}</p>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-muted">Taux d'intérêt</label>
                                        <p class="fw-medium mb-0">{{ $loan->interest_rate }}%
                                            ({{ $loan->interest_type === 'fixed' ? 'Fixe' : 'Variable' }})</p>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-muted">Montant total</label>
                                        <p class="fw-semibold mb-0">{{ number_format($loan->total_amount, 2, ',', ' ') }}
                                        </p>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-muted">Solde restant</label>
                                        <p class="fw-semibold mb-0 text-danger">
                                            {{ number_format($loan->remaining_balance, 2, ',', ' ') }}</p>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-muted">Fréquence de paiement</label>
                                        <p class="fw-medium mb-0">
                                            @switch($loan->payment_frequency)
                                                @case('monthly')
                                                    Mensuel
                                                @break

                                                @case('quarterly')
                                                    Trimestriel
                                                @break

                                                @case('semi_annual')
                                                    Semestriel
                                                @break

                                                @case('annual')
                                                    Annuel
                                                @break

                                                @default
                                                    —
                                            @endswitch
                                        </p>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-muted">Date de début</label>
                                        <p class="fw-medium mb-0">
                                            {{ \Carbon\Carbon::parse($loan->start_date)->format('d/m/Y') }}</p>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-muted">Date de fin</label>
                                        <p class="fw-medium mb-0">
                                            {{ $loan->end_date ? \Carbon\Carbon::parse($loan->end_date)->format('d/m/Y') : '—' }}
                                        </p>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-muted">Statut</label>
                                        <p class="mb-0">
                                            @switch($loan->status)
                                                @case('active')
                                                    <span class="badge badge-soft-success">Actif</span>
                                                @break

                                                @case('completed')
                                                    <span class="badge badge-soft-info">Terminé</span>
                                                @break

                                                @case('defaulted')
                                                    <span class="badge badge-soft-danger">Défaut</span>
                                                @break
                                            @endswitch
                                        </p>
                                    </div>
                                </div>

                                @if ($loan->notes)
                                    <div class="mb-4">
                                        <label class="form-label text-muted">Notes</label>
                                        <p>{{ $loan->notes }}</p>
                                    </div>
                                @endif

                                @if ($loan->installments->count())
                                    <h6 class="mb-3">Échéancier</h6>
                                    <div class="table-responsive">
                                        <table class="table table-nowrap">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>N°</th>
                                                    <th>Date d'échéance</th>
                                                    <th>Principal</th>
                                                    <th>Intérêts</th>
                                                    <th>Total</th>
                                                    <th>Payé</th>
                                                    <th>Restant</th>
                                                    <th>Statut</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($loan->installments as $installment)
                                                    <tr>
                                                        <td>{{ $installment->installment_number }}</td>
                                                        <td>{{ \Carbon\Carbon::parse($installment->due_date)->format('d/m/Y') }}
                                                        </td>
                                                        <td>{{ number_format($installment->principal_amount, 2, ',', ' ') }}
                                                        </td>
                                                        <td>{{ number_format($installment->interest_amount, 2, ',', ' ') }}
                                                        </td>
                                                        <td class="fw-semibold">
                                                            {{ number_format($installment->total_amount, 2, ',', ' ') }}
                                                        </td>
                                                        <td>{{ number_format($installment->paid_amount, 2, ',', ' ') }}
                                                        </td>
                                                        <td>{{ number_format($installment->remaining_amount, 2, ',', ' ') }}
                                                        </td>
                                                        <td>
                                                            @switch($installment->status)
                                                                @case('paid')
                                                                    <span class="badge badge-soft-success">Payée</span>
                                                                @break

                                                                @case('pending')
                                                                    <span class="badge badge-soft-warning">En attente</span>
                                                                @break

                                                                @case('overdue')
                                                                    <span class="badge badge-soft-danger">En retard</span>
                                                                @break
                                                            @endswitch
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
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
