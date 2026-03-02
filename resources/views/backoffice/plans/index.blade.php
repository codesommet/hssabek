<?php $page = 'plans'; ?>
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
                    <h6>Plans</h6>
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
                            data-bs-toggle="modal" data-bs-target="#add_plan"><i class="isax isax-add-circle5 me-1"></i>Nouveau Plan</a>
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
                                    <p class="fs-14 mb-1 text-truncate">Total Plans</p>
                                    <h4 class="fs-16 fw-semibold">{{ $totalPlans }}</h4>
                                </div>
                            </div>
                            <div>
                                <span class="avatar avatar-lg bg-warning flex-shrink-0">
                                    <i class="isax isax-box5 fs-32"></i>
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
                                    <p class="fs-14 mb-1 text-truncate">Plans Actifs</p>
                                    <h4 class="fs-16 fw-semibold">{{ $activePlans }}</h4>
                                </div>
                            </div>
                            <div>
                                <span class="avatar avatar-lg bg-success flex-shrink-0">
                                    <i class="isax isax-box-tick5 fs-32"></i>
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
                                    <p class="fs-14 mb-1 text-truncate">Plans Inactifs</p>
                                    <h4 class="fs-16 fw-semibold">{{ $inactivePlans }}</h4>
                                </div>
                            </div>
                            <div>
                                <span class="avatar avatar-lg bg-danger flex-shrink-0">
                                    <i class="isax isax-box-remove5 fs-32"></i>
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
                                    <p class="fs-14 mb-1 text-truncate">Total Abonnés Actifs</p>
                                    <h4 class="fs-16 fw-semibold">{{ $totalSubscribers }}</h4>
                                </div>
                            </div>
                            <div>
                                <span class="avatar avatar-lg bg-info flex-shrink-0">
                                    <i class="isax isax-box-25 fs-32"></i>
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
                        <div class="dropdown me-2">
                            <a href="javascript:void(0);"
                                class="dropdown-toggle btn btn-outline-white d-inline-flex align-items-center fw-medium"
                                data-bs-toggle="dropdown">
                                <i class="isax isax-sort me-1"></i>Trier par : <span class="fw-normal ms-1">Plus récent</span>
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

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif

            <!-- Start Table List -->
            @include('backoffice.plans.partials._table')
            <!-- End Table List -->

        </div>
        <!-- End Content -->

        @component('backoffice.components.footer')
        @endcomponent

    </div>

    <!-- Modals -->
    @include('backoffice.plans.partials._modals')

    @if($errors->any() && old('_modal') === 'add_plan')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('add_plan'));
                modal.show();
            });
        </script>
    @endif

    <!-- ========================
           End Page Content
          ========================= -->
@endsection
