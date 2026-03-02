<?php $page = 'tenants'; ?>
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
                            Tenants
                        </a>
                    </h6>
                </div>
                <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
                    <span class="badge bg-secondary">Nouveau Tenant</span>
                </div>
            </div>
            {{-- /Page Header --}}

            {{-- Form Card --}}
            <div class="table-responsive table-nowrap">
                <div class="table border mb-0 p-0">
                    <div class="p-4">
                        @include('backoffice.tenants.partials._form')
                    </div>
                </div>
            </div>
            {{-- /Form Card --}}

        </div>

        @component('backoffice.components.footer')
        @endcomponent

    </div>

@endsection
