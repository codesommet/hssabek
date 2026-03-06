<?php $page = 'error-403'; ?>
@extends('backoffice.layout.mainlayout')
@section('content')
    <!-- ========================
                    Start Page Content
                ========================= -->

    @php
        $message = $exception->getMessage();
        $isPlanLimit = str_contains($message, 'limite');
        $title = $isPlanLimit ? 'Limite du plan atteinte' : 'Accès refusé';
        $defaultMessage = $isPlanLimit
            ? 'Vous avez atteint la limite de votre plan actuel. Veuillez mettre à niveau votre plan pour continuer.'
            : 'Vous n\'avez pas la permission d\'accéder à cette ressource.';
        $icon = $isPlanLimit ? 'isax-crown-1' : 'isax-lock5';
        $iconBg = $isPlanLimit ? 'bg-warning' : 'bg-danger';
    @endphp

    <div class="container-fuild">
        <div class="w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
            <!-- row start -->
            <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap ">
                <div class="col-lg-6 col-md-8 mx-auto">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="d-flex flex-column justify-content-lg-center p-4 p-lg-0 pb-0 flex-fill text-center">
                            <div class=" mx-auto mb-4 text-center">
                                <img src="{{ URL::asset('build/img/logo.svg') }}" class="img-fluid" alt="Logo">
                            </div>

                            <div class="card border-0 p-lg-3 shadow-lg rounded-2">
                                <div class="card-body">
                                    <div class="mb-4 text-center">
                                        <span class="avatar avatar-xxl {{ $iconBg }} rounded-circle d-flex align-items-center justify-content-center mx-auto">
                                            <i class="isax {{ $icon }} fs-40 text-white"></i>
                                        </span>
                                    </div>

                                    <div class="text-center mb-4">
                                        <h3 class="mb-2">{{ $title }}</h3>
                                        <p class="mb-0 text-muted">{{ $message ?: $defaultMessage }}</p>
                                    </div>

                                    <div class="text-center">
                                        <a href="javascript:history.back()" class="btn btn-outline-primary me-2">
                                            <i class="isax isax-arrow-left me-1"></i>Retour
                                        </a>
                                        @auth
                                            @if($isPlanLimit)
                                                <a href="{{ route('bo.settings.plans-billings.index') }}" class="btn bg-primary-gradient text-white">
                                                    <i class="isax isax-crown-1 me-1"></i>Mettre à niveau
                                                </a>
                                            @else
                                                <a href="{{ route('bo.dashboard') }}" class="btn bg-primary-gradient text-white">
                                                    <i class="isax isax-home me-1"></i>Tableau de bord
                                                </a>
                                            @endif
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- row end -->
        </div>
    </div>

    <!-- ========================
                    End Page Content
                ========================= -->
@endsection
