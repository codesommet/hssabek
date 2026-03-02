<?php $page = 'subscriptions'; ?>
@extends('backoffice.layout.mainlayout')
@section('content')

    <div class="page-wrapper">

        <div class="content content-two">

            {{-- Page Header --}}
            <div class="d-flex d-block align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h6>
                        <a href="#">
                            <i class="isax isax-arrow-left me-1"></i>
                            Abonnements
                        </a>
                    </h6>
                </div>
                <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
                    <a href="#" class="btn btn-outline-white d-inline-flex align-items-center">
                        <i class="isax isax-edit me-1"></i>Modifier
                    </a>
                </div>
            </div>
            {{-- /Page Header --}}

            {{-- Placeholder – UI structure aligned with theme. --}}
            <div class="table-responsive table-nowrap">
                <table class="table border mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Champ</th>
                            <th>Valeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Tenant</td>
                            <td>—</td>
                        </tr>
                        <tr>
                            <td>Plan</td>
                            <td>—</td>
                        </tr>
                        <tr>
                            <td>Date de début</td>
                            <td>—</td>
                        </tr>
                        <tr>
                            <td>Date de fin</td>
                            <td>—</td>
                        </tr>
                        <tr>
                            <td>Statut</td>
                            <td>—</td>
                        </tr>
                        <tr>
                            <td>Créé le</td>
                            <td>—</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        @component('backoffice.components.footer')
        @endcomponent

    </div>

@endsection
