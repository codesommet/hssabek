<?php $page = 'bank-accounts'; ?>
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
                <div class="col-md-10 mx-auto">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6><a href="{{ route('bo.finance.bank-accounts.index') }}"><i class="isax isax-arrow-left me-2"></i>Comptes bancaires</a></h6>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3">Modifier le compte bancaire</h5>

                                @if($errors->any())
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <ul class="mb-0">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @endif

                                <form action="{{ route('bo.finance.bank-accounts.update', $bankAccount) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3">
                                        <h6 class="text-gray-9 fw-bold mb-2 d-flex">Informations du compte</h6>
                                    </div>
                                    <div class="row gx-3">
                                        <div class="col-lg-4 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Titulaire du compte <span class="text-danger ms-1">*</span></label>
                                                <input type="text" class="form-control @error('account_holder_name') is-invalid @enderror" name="account_holder_name" value="{{ old('account_holder_name', $bankAccount->account_holder_name) }}">
                                                @error('account_holder_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Numéro de compte <span class="text-danger ms-1">*</span></label>
                                                <input type="text" class="form-control @error('account_number') is-invalid @enderror" name="account_number" value="{{ old('account_number', $bankAccount->account_number) }}">
                                                @error('account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Nom de la banque <span class="text-danger ms-1">*</span></label>
                                                <input type="text" class="form-control @error('bank_name') is-invalid @enderror" name="bank_name" value="{{ old('bank_name', $bankAccount->bank_name) }}">
                                                @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Code IFSC / SWIFT</label>
                                                <input type="text" class="form-control @error('ifsc_code') is-invalid @enderror" name="ifsc_code" value="{{ old('ifsc_code', $bankAccount->ifsc_code) }}">
                                                @error('ifsc_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Agence</label>
                                                <input type="text" class="form-control @error('branch') is-invalid @enderror" name="branch" value="{{ old('branch', $bankAccount->branch) }}">
                                                @error('branch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Type de compte <span class="text-danger ms-1">*</span></label>
                                                <select class="form-select @error('account_type') is-invalid @enderror" name="account_type">
                                                    <option value="">— Sélectionner —</option>
                                                    <option value="current" {{ old('account_type', $bankAccount->account_type) === 'current' ? 'selected' : '' }}>Courant</option>
                                                    <option value="savings" {{ old('account_type', $bankAccount->account_type) === 'savings' ? 'selected' : '' }}>Épargne</option>
                                                    <option value="business" {{ old('account_type', $bankAccount->account_type) === 'business' ? 'selected' : '' }}>Professionnel</option>
                                                    <option value="other" {{ old('account_type', $bankAccount->account_type) === 'other' ? 'selected' : '' }}>Autre</option>
                                                </select>
                                                @error('account_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3 mt-2">
                                        <h6 class="text-gray-9 fw-bold mb-2 d-flex">Solde & devise</h6>
                                    </div>
                                    <div class="row gx-3">
                                        <div class="col-lg-4 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Devise</label>
                                                <input type="text" class="form-control"
                                                    value="{{ $bankAccount->currency }}"
                                                    readonly disabled>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Solde d'ouverture</label>
                                                <input type="number" step="0.01" class="form-control" value="{{ number_format($bankAccount->opening_balance, 2, '.', '') }}" disabled>
                                                <small class="text-muted">Le solde d'ouverture ne peut pas être modifié.</small>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Statut <span class="text-danger ms-1">*</span></label>
                                                <select class="form-select @error('is_active') is-invalid @enderror" name="is_active">
                                                    <option value="1" {{ old('is_active', $bankAccount->is_active ? '1' : '0') === '1' ? 'selected' : '' }}>Actif</option>
                                                    <option value="0" {{ old('is_active', $bankAccount->is_active ? '1' : '0') === '0' ? 'selected' : '' }}>Inactif</option>
                                                </select>
                                                @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="mb-3">
                                                <label class="form-label">Notes</label>
                                                <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="3">{{ old('notes', $bankAccount->notes) }}</textarea>
                                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between pt-4 border-top">
                                        <a href="{{ route('bo.finance.bank-accounts.index') }}" class="btn btn-outline-white">Annuler</a>
                                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    </div>
                                </form>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div>
                </div><!-- end col -->
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
