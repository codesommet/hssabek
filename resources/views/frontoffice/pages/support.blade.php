@extends('frontoffice.layouts.app')

@section('title', 'Support')
@section('meta_description', 'Support technique ' . config('app.name') . '. Notre équipe est là pour vous aider.')

@section('hero')
<!-- Hero Section -->
<section class="hero-section" id="index">
	<div class="container banner-hero">
		<div class="home-banner">
			<div class="row justify-content-center">
				<div class="col-lg-8 text-center">
					<div class="banner-content" data-aos="fade-up">
						<span class="info-badge fw-medium mb-3">Support technique</span>
						<div class="banner-title">
							<h1 class="mb-2">Notre équipe est <span class="head">à votre écoute</span></h1>
						</div>
						<p class="fw-medium">Un problème technique ou une question ? Nous sommes là pour vous accompagner et vous aider à réussir.</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- /Hero Section -->
@endsection

@section('content')

<!-- Support Options -->
<section class="saas-app-section">
	<div class="container">
		<div class="section-heading aos" data-aos="fade-up">
			<span class="title-badge">Nos canaux</span>
			<h2 class="mb-2">Comment nous <span>contacter ?</span></h2>
			<p class="fw-medium">Choisissez le canal qui vous convient le mieux. Nous sommes disponibles du lundi au vendredi, de 9h à 18h.</p>
		</div>
		<div class="row">
			<div class="col-lg-4 col-md-6" data-aos="fade-up">
				<div class="app-card">
					<div class="app-icon">
						<img src="{{ url('build/img/icons/app-icon-01.svg') }}" alt="Email">
					</div>
					<div class="app-content">
						<h6 class="mb-1">Support par email</h6>
						<p>Envoyez-nous un email et recevez une réponse sous 24h ouvrées. Idéal pour les questions détaillées.</p>
						<a href="{{ route('contact') }}" class="btn btn-dark btn-sm d-inline-flex align-items-center mt-2">Envoyer un email<i class="isax isax-arrow-right-3 ms-2"></i></a>
					</div>
				</div>
			</div>
			<div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
				<div class="app-card">
					<div class="app-icon">
						<img src="{{ url('build/img/icons/app-icon-02.svg') }}" alt="Centre d'aide">
					</div>
					<div class="app-content">
						<h6 class="mb-1">Centre d'aide</h6>
						<p>Parcourez nos guides et tutoriels pour trouver des réponses instantanées à vos questions.</p>
						<a href="{{ route('help-center') }}" class="btn btn-dark btn-sm d-inline-flex align-items-center mt-2">Consulter les guides<i class="isax isax-arrow-right-3 ms-2"></i></a>
					</div>
				</div>
			</div>
			<div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
				<div class="app-card">
					<div class="app-icon">
						<img src="{{ url('build/img/icons/app-icon-03.svg') }}" alt="FAQ">
					</div>
					<div class="app-content">
						<h6 class="mb-1">FAQ</h6>
						<p>Les réponses aux questions les plus fréquemment posées par nos utilisateurs.</p>
						<a href="{{ route('faq') }}" class="btn btn-dark btn-sm d-inline-flex align-items-center mt-2">Voir la FAQ<i class="isax isax-arrow-right-3 ms-2"></i></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- /Support Options -->

<!-- Support Levels -->
<section class="invoice-temp-sec">
	<div class="container">
		<div class="section-heading" data-aos="fade-up">
			<span class="title-badge">Niveaux de support</span>
			<h2>Un support adapté <span>à chaque plan</span></h2>
			<p class="fw-medium">Plus votre plan est avancé, plus votre support est prioritaire.</p>
		</div>
		<div class="row justify-content-center">
			<div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
				<div class="packages-card">
					<div class="package-header d-flex justify-content-between">
						<div class="d-flex justify-content-between w-100">
							<div>
								<h6>Support</h6>
								<h4>Gratuit</h4>
							</div>
							<span class="icon-frame d-flex align-items-center justify-content-center"><img src="{{ url('build/img/icons/price-01.svg') }}" alt="icône"></span>
						</div>
					</div>
					<p>Support communautaire et documentation en ligne.</p>
					<h5>Inclus</h5>
					<ul class="plan-features">
						<li><i class="fa-solid fa-circle-check"></i>Centre d'aide</li>
						<li><i class="fa-solid fa-circle-check"></i>FAQ complète</li>
						<li><i class="fa-solid fa-circle-check"></i>Réponse sous 48h</li>
					</ul>
				</div>
			</div>
			<div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
				<div class="packages-card">
					<div class="package-header d-flex justify-content-between">
						<div class="d-flex justify-content-between w-100">
							<div>
								<h6>Support</h6>
								<h4>Standard</h4>
							</div>
							<span class="icon-frame d-flex align-items-center justify-content-center"><img src="{{ url('build/img/icons/price-02.svg') }}" alt="icône"></span>
						</div>
					</div>
					<p>Support par email avec temps de réponse garanti.</p>
					<h5>Inclus</h5>
					<ul class="plan-features">
						<li><i class="fa-solid fa-circle-check"></i>Tout du plan Gratuit</li>
						<li><i class="fa-solid fa-circle-check"></i>Support email dédié</li>
						<li><i class="fa-solid fa-circle-check"></i>Réponse sous 24h</li>
					</ul>
				</div>
			</div>
			<div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="700">
				<div class="packages-card">
					<div class="package-header d-flex justify-content-between">
						<div class="d-flex justify-content-between w-100">
							<div>
								<h6>Support</h6>
								<h4>Prioritaire</h4>
							</div>
							<span class="icon-frame d-flex align-items-center justify-content-center"><img src="{{ url('build/img/icons/price-03.svg') }}" alt="icône"></span>
						</div>
					</div>
					<p>Support prioritaire avec assistance personnalisée.</p>
					<h5>Inclus</h5>
					<ul class="plan-features">
						<li><i class="fa-solid fa-circle-check"></i>Tout du plan Standard</li>
						<li><i class="fa-solid fa-circle-check"></i>Support prioritaire</li>
						<li><i class="fa-solid fa-circle-check"></i>Réponse sous 4h</li>
						<li><i class="fa-solid fa-circle-check"></i>Appel d'onboarding</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- /Support Levels -->

<!-- CTA -->
<section class="faq-section bg-white">
	<div class="container">
		<div class="connect-with-us">
			<div class="section-title text-center" data-aos="fade-up">
				<h2 class="mb-2">Besoin d'aide maintenant ?</h2>
				<p class="mx-auto">Contactez notre équipe de support. Nous nous engageons à vous répondre dans les meilleurs délais.</p>
				<a href="{{ route('contact') }}" class="btn btn-primary btn-lg d-inline-flex align-items-center">Contacter le support<i class="isax isax-arrow-right-3 ms-2"></i></a>
			</div>
		</div>
	</div>
</section>

@endsection
