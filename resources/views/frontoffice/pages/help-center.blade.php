@extends('frontoffice.layouts.app')

@section('title', 'Centre d\'aide')
@section('meta_description', 'Centre d\'aide ' . config('app.name') . '. Trouvez des réponses à vos questions et apprenez à utiliser la plateforme.')

@section('hero')
<!-- Hero Section -->
<section class="hero-section" id="index">
	<div class="container banner-hero">
		<div class="home-banner">
			<div class="row justify-content-center">
				<div class="col-lg-8 text-center">
					<div class="banner-content" data-aos="fade-up">
						<span class="info-badge fw-medium mb-3">Centre d'aide</span>
						<div class="banner-title">
							<h1 class="mb-2">Comment pouvons-nous <span class="head">vous aider ?</span></h1>
						</div>
						<p class="fw-medium">Parcourez nos guides, tutoriels et articles pour tirer le meilleur parti de {{ config('app.name') }}.</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- /Hero Section -->
@endsection

@section('content')

<!-- Help Categories -->
<section class="advance-module-sec">
	<div class="container">
		<div class="section-heading" data-aos="fade-up">
			<span class="title-badge">Catégories</span>
			<h2 class="mb-2">Explorez nos <span>guides par thème</span></h2>
			<p class="fw-medium">Sélectionnez une catégorie pour trouver rapidement les réponses à vos questions.</p>
		</div>
		<div class="row">
			<div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-down" data-aos-delay="200">
				<div class="module-card">
					<div class="module-icon">
						<img src="{{ url('build/img/icons/module-icon-01.svg') }}" alt="Démarrage">
					</div>
					<h6 class="mb-2">Prise en main</h6>
					<p>Créez votre compte, configurez votre entreprise et commencez à facturer en quelques minutes.</p>
				</div>
			</div>
			<div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-down" data-aos-delay="300">
				<div class="module-card">
					<div class="module-icon">
						<img src="{{ url('build/img/icons/module-icon-05.svg') }}" alt="Facturation">
					</div>
					<h6 class="mb-2">Facturation & Devis</h6>
					<p>Apprenez à créer, envoyer et gérer vos factures et devis comme un professionnel.</p>
				</div>
			</div>
			<div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-down" data-aos-delay="400">
				<div class="module-card">
					<div class="module-icon">
						<img src="{{ url('build/img/icons/module-icon-02.svg') }}" alt="Clients">
					</div>
					<h6 class="mb-2">Clients & Fournisseurs</h6>
					<p>Gérez vos contacts, adresses et historique de transactions efficacement.</p>
				</div>
			</div>
			<div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-down" data-aos-delay="500">
				<div class="module-card">
					<div class="module-icon">
						<img src="{{ url('build/img/icons/module-icon-04.svg') }}" alt="Stock">
					</div>
					<h6 class="mb-2">Produits & Stock</h6>
					<p>Cataloguez vos produits, suivez vos niveaux de stock et gérez vos entrepôts.</p>
				</div>
			</div>
			<div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-down" data-aos-delay="600">
				<div class="module-card">
					<div class="module-icon">
						<img src="{{ url('build/img/icons/module-icon-08.svg') }}" alt="Achats">
					</div>
					<h6 class="mb-2">Achats & Dépenses</h6>
					<p>Bons de commande, factures fournisseur, dépenses et suivi budgétaire.</p>
				</div>
			</div>
			<div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-down" data-aos-delay="700">
				<div class="module-card">
					<div class="module-icon">
						<img src="{{ url('build/img/icons/module-icon-09.svg') }}" alt="Rapports">
					</div>
					<h6 class="mb-2">Rapports & Finances</h6>
					<p>Comptes bancaires, rapports de vente, profits et analyses financières.</p>
				</div>
			</div>
			<div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-down" data-aos-delay="800">
				<div class="module-card">
					<div class="module-icon">
						<img src="{{ url('build/img/icons/management-icon-04.svg') }}" alt="Utilisateurs">
					</div>
					<h6 class="mb-2">Utilisateurs & Rôles</h6>
					<p>Invitez votre équipe, attribuez des rôles et gérez les permissions d'accès.</p>
				</div>
			</div>
			<div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-down" data-aos-delay="900">
				<div class="module-card">
					<div class="module-icon">
						<img src="{{ url('build/img/icons/management-icon-05.svg') }}" alt="Paramètres">
					</div>
					<h6 class="mb-2">Paramètres & Configuration</h6>
					<p>Personnalisez votre compte, modèles de factures, taxes et préférences.</p>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- /Help Categories -->

<!-- Still Need Help -->
<section class="faq-section bg-white">
	<div class="container">
		<div class="connect-with-us">
			<div class="section-title text-center" data-aos="fade-up">
				<h2 class="mb-2">Vous n'avez pas trouvé votre réponse ?</h2>
				<p class="mx-auto">Notre équipe de support est là pour vous aider. Contactez-nous et nous vous répondrons sous 24h.</p>
				<div class="d-flex flex-wrap justify-content-center gap-3">
					<a href="{{ route('contact') }}" class="btn btn-primary btn-lg d-inline-flex align-items-center">Nous contacter<i class="isax isax-arrow-right-3 ms-2"></i></a>
					<a href="{{ route('faq') }}" class="btn btn-dark btn-lg d-inline-flex align-items-center">Voir la FAQ<i class="isax isax-arrow-right-3 ms-2"></i></a>
				</div>
			</div>
		</div>
	</div>
</section>

@endsection
