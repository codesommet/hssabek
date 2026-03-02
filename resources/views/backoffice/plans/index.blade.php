<?php $page = 'plans'; ?>
@extends('backoffice.layout.mainlayout')
@section('content')

    <div class="page-wrapper">

        <div class="content content-two">

            {{-- Page Header --}}
            <div class="d-flex d-block align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h6>Plans</h6>
                </div>
                <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
                    <a href="{{ route('sa.plans.create') }}" class="btn btn-primary d-flex align-items-center">
                        <i class="isax isax-add-circle5 me-1"></i>Nouveau Plan
                    </a>
                </div>
            </div>
            {{-- /Page Header --}}

            {{-- Filter Row --}}
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="isax isax-search-normal fs-12"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0 bg-white" placeholder="Rechercher...">
                    </div>
                </div>
            </div>
            {{-- /Filter Row --}}

            {{-- Table --}}
            @include('backoffice.plans.partials._table')
            {{-- /Table --}}

        </div>

        @component('backoffice.components.footer')
        @endcomponent

    </div>

@endsection
