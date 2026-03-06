<?php $page = 'no-subscription'; ?>
@extends('backoffice.layout.mainlayout')
@section('content')
    <!-- ========================
                    Start Page Content
                ========================= -->

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
                                        <i class="isax isax-card-remove text-danger" style="font-size: 3rem;"></i>
                                    </div>

                                    <div class="text-center mb-4">
                                        <h3 class="mb-2">Aucun abonnement actif</h3>
                                        <p class="mb-0 text-muted">
                                            Votre entreprise ne dispose pas d'un abonnement actif.
                                            Veuillez souscrire un abonnement pour acceder a toutes les fonctionnalites.
                                        </p>
                                    </div>

                                    <div class="alert alert-danger" role="alert">
                                        <i class="isax isax-info-circle me-2"></i>
                                        <strong>Important :</strong> Sans abonnement actif, l'acces aux fonctionnalites
                                        de gestion est bloque.
                                    </div>

                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('bo.settings.plans-billings.index') }}"
                                            class="btn bg-primary-gradient text-white">
                                            <i class="isax isax-crown-1 me-1"></i> Voir les abonnements
                                        </a>
                                        <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                                            Retour a la connexion
                                        </a>
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
