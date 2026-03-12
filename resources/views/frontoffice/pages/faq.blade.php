@extends('frontoffice.layouts.app')

@section('title', 'FAQ')
@section('meta_description', 'Foire aux questions ' . config('app.name') . '. Trouvez les réponses aux questions les plus fréquentes.')

@section('hero')
<!-- Hero Section -->
<section class="hero-section" id="index">
	<div class="container banner-hero">
		<div class="home-banner">
			<div class="row justify-content-center">
				<div class="col-lg-8 text-center">
					<div class="banner-content" data-aos="fade-up">
						<span class="info-badge fw-medium mb-3">FAQ</span>
						<div class="banner-title">
							<h1 class="mb-2">Foire aux <span class="head">questions</span></h1>
						</div>
						<p class="fw-medium">Retrouvez les réponses aux questions les plus fréquemment posées par nos utilisateurs.</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- /Hero Section -->
@endsection

@section('content')

<!-- FAQ General -->
<section class="faq-section bg-white">
	<div class="container">
		<div class="sec-bg-img">
			<img src="{{ url('build/img/bg/sec-bg-11.png') }}" class="faq-bg-one" alt="Bg">
			<img src="{{ url('build/img/bg/sec-bg-12.png') }}" class="faq-bg-two" alt="Bg">
			<img src="{{ url('build/img/bg/sec-bg-13.png') }}" class="faq-bg-three" alt="Bg">
			<img src="{{ url('build/img/icons/faq-bg.svg') }}" class="faq-bg-four" alt="Bg">
		</div>
		<div class="row align-items-start">
			<div class="col-lg-5">
				<div class="section-heading" data-aos="fade-up">
					<span class="title-badge">Général</span>
					<h2 class="mb-2">Questions <span>générales</span></h2>
					<p class="fw-medium">Tout ce que vous devez savoir sur {{ config('app.name') }} et son fonctionnement.</p>
				</div>
			</div>
			<div class="col-lg-7">
				<div class="faq-set" id="accordionGeneral">
					<div class="faq-card aos" data-aos="fade-up" data-aos-delay="600">
						<h4 class="faq-title">
							<a data-bs-toggle="collapse" href="#gFaq1" aria-expanded="false">Qu'est-ce que {{ config('app.name') }} ?</a>
						</h4>
						<div id="gFaq1" class="card-collapse collapse show" data-bs-parent="#accordionGeneral">
							<p>{{ config('app.name') }} est une solution SaaS complète de gestion commerciale : facturation, devis, gestion des clients et fournisseurs, stock, achats, dépenses et rapports. Tout est centralisé dans une seule plateforme accessible depuis votre navigateur.</p>
						</div>
					</div>
					<div class="faq-card aos" data-aos="fade-up">
						<h4 class="faq-title">
							<a class="collapsed" data-bs-toggle="collapse" href="#gFaq2" aria-expanded="false">À qui s'adresse {{ config('app.name') }} ?</a>
						</h4>
						<div id="gFaq2" class="card-collapse collapse" data-bs-parent="#accordionGeneral">
							<p>Notre plateforme est conçue pour les PME, auto-entrepreneurs, freelances et toute entreprise qui souhaite simplifier sa gestion quotidienne : facturation, suivi des paiements, gestion des stocks et bien plus encore.</p>
						</div>
					</div>
					<div class="faq-card aos" data-aos="fade-up">
						<h4 class="faq-title">
							<a class="collapsed" data-bs-toggle="collapse" href="#gFaq3" aria-expanded="false">Y a-t-il un essai gratuit ?</a>
						</h4>
						<div id="gFaq3" class="card-collapse collapse" data-bs-parent="#accordionGeneral">
							<p>Oui ! Vous pouvez tester {{ config('app.name') }} gratuitement pendant 7 jours sans carte bancaire. Cela vous permet de découvrir toutes les fonctionnalités avant de vous engager.</p>
						</div>
					</div>
					<div class="faq-card aos" data-aos="fade-up">
						<h4 class="faq-title">
							<a class="collapsed" data-bs-toggle="collapse" href="#gFaq4" aria-expanded="false">Ai-je besoin d'installer un logiciel ?</a>
						</h4>
						<div id="gFaq4" class="card-collapse collapse" data-bs-parent="#accordionGeneral">
							<p>Non. {{ config('app.name') }} est 100% en ligne (SaaS). Il suffit d'un navigateur web et d'une connexion internet. Aucune installation requise.</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- FAQ Facturation -->
<section class="faq-section" style="background: #f8f9fc;">
	<div class="container">
		<div class="row align-items-start">
			<div class="col-lg-5">
				<div class="section-heading" data-aos="fade-up">
					<span class="title-badge">Facturation</span>
					<h2 class="mb-2">Factures <span>& Devis</span></h2>
					<p class="fw-medium">Questions sur la création et la gestion de vos factures et devis.</p>
				</div>
			</div>
			<div class="col-lg-7">
				<div class="faq-set" id="accordionBilling">
					<div class="faq-card aos" data-aos="fade-up" data-aos-delay="600">
						<h4 class="faq-title">
							<a data-bs-toggle="collapse" href="#bFaq1" aria-expanded="false">Combien de factures puis-je créer ?</a>
						</h4>
						<div id="bFaq1" class="card-collapse collapse show" data-bs-parent="#accordionBilling">
							<p>Cela dépend de votre plan. Le plan gratuit inclut un nombre limité de factures par mois, tandis que les plans Premium et Entreprise offrent des factures illimitées. Consultez notre <a href="{{ route('pricing') }}" class="text-primary">page tarifs</a> pour les détails.</p>
						</div>
					</div>
					<div class="faq-card aos" data-aos="fade-up">
						<h4 class="faq-title">
							<a class="collapsed" data-bs-toggle="collapse" href="#bFaq2" aria-expanded="false">Puis-je personnaliser mes modèles de facture ?</a>
						</h4>
						<div id="bFaq2" class="card-collapse collapse" data-bs-parent="#accordionBilling">
							<p>Oui ! Nous proposons plus de 40 modèles de factures personnalisables. Vous pouvez y ajouter votre logo, vos couleurs, vos conditions de paiement et toute information supplémentaire.</p>
						</div>
					</div>
					<div class="faq-card aos" data-aos="fade-up">
						<h4 class="faq-title">
							<a class="collapsed" data-bs-toggle="collapse" href="#bFaq3" aria-expanded="false">Puis-je convertir un devis en facture ?</a>
						</h4>
						<div id="bFaq3" class="card-collapse collapse" data-bs-parent="#accordionBilling">
							<p>Absolument ! Un simple clic suffit pour transformer un devis accepté en facture. Toutes les informations sont automatiquement reprises.</p>
						</div>
					</div>
					<div class="faq-card aos" data-aos="fade-up">
						<h4 class="faq-title">
							<a class="collapsed" data-bs-toggle="collapse" href="#bFaq4" aria-expanded="false">Les rappels de paiement sont-ils automatiques ?</a>
						</h4>
						<div id="bFaq4" class="card-collapse collapse" data-bs-parent="#accordionBilling">
							<p>Oui, vous pouvez configurer des rappels automatiques pour les factures impayées. Définissez la fréquence et le contenu des rappels depuis vos paramètres.</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- FAQ Abonnement -->
<section class="faq-section bg-white">
	<div class="container">
		<div class="row align-items-start">
			<div class="col-lg-5">
				<div class="section-heading" data-aos="fade-up">
					<span class="title-badge">Abonnement</span>
					<h2 class="mb-2">Plans <span>& Paiement</span></h2>
					<p class="fw-medium">Tout sur les abonnements, les prix et les modalités de paiement.</p>
				</div>
			</div>
			<div class="col-lg-7">
				<div class="faq-set" id="accordionPlans">
					<div class="faq-card aos" data-aos="fade-up" data-aos-delay="600">
						<h4 class="faq-title">
							<a data-bs-toggle="collapse" href="#pFaq1" aria-expanded="false">Puis-je changer de plan à tout moment ?</a>
						</h4>
						<div id="pFaq1" class="card-collapse collapse show" data-bs-parent="#accordionPlans">
							<p>Oui, vous pouvez passer à un plan supérieur ou inférieur à tout moment depuis votre espace de gestion. Le changement prend effet immédiatement.</p>
						</div>
					</div>
					<div class="faq-card aos" data-aos="fade-up">
						<h4 class="faq-title">
							<a class="collapsed" data-bs-toggle="collapse" href="#pFaq2" aria-expanded="false">Quelle est votre politique d'annulation ?</a>
						</h4>
						<div id="pFaq2" class="card-collapse collapse" data-bs-parent="#accordionPlans">
							<p>Vous pouvez annuler votre abonnement à tout moment. L'annulation prend effet à la fin de la période de facturation en cours. Aucun frais supplémentaire ne sera appliqué.</p>
						</div>
					</div>
					<div class="faq-card aos" data-aos="fade-up">
						<h4 class="faq-title">
							<a class="collapsed" data-bs-toggle="collapse" href="#pFaq3" aria-expanded="false">Quels moyens de paiement acceptez-vous ?</a>
						</h4>
						<div id="pFaq3" class="card-collapse collapse" data-bs-parent="#accordionPlans">
							<p>Nous acceptons les cartes bancaires (Visa, Mastercard, American Express) via notre prestataire de paiement sécurisé. Les virements bancaires sont disponibles pour les plans Entreprise.</p>
						</div>
					</div>
					<div class="faq-card aos" data-aos="fade-up">
						<h4 class="faq-title">
							<a class="collapsed" data-bs-toggle="collapse" href="#pFaq4" aria-expanded="false">Puis-je exporter mes données si je quitte ?</a>
						</h4>
						<div id="pFaq4" class="card-collapse collapse" data-bs-parent="#accordionPlans">
							<p>Oui, vos données vous appartiennent. Vous pouvez exporter toutes vos factures, clients, produits et rapports en PDF ou CSV à tout moment, même après annulation.</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- FAQ Sécurité -->
<section class="faq-section" style="background: #f8f9fc;">
	<div class="container">
		<div class="row align-items-start">
			<div class="col-lg-5">
				<div class="section-heading" data-aos="fade-up">
					<span class="title-badge">Sécurité</span>
					<h2 class="mb-2">Données <span>& Sécurité</span></h2>
					<p class="fw-medium">La protection de vos données est notre priorité absolue.</p>
				</div>
			</div>
			<div class="col-lg-7">
				<div class="faq-set" id="accordionSecurity">
					<div class="faq-card aos" data-aos="fade-up" data-aos-delay="600">
						<h4 class="faq-title">
							<a data-bs-toggle="collapse" href="#sFaq1" aria-expanded="false">Mes données sont-elles sécurisées ?</a>
						</h4>
						<div id="sFaq1" class="card-collapse collapse show" data-bs-parent="#accordionSecurity">
							<p>Absolument. Chaque entreprise dispose de son propre espace totalement isolé (multi-tenant). Vos données sont chiffrées en transit (HTTPS/TLS) et sauvegardées régulièrement.</p>
						</div>
					</div>
					<div class="faq-card aos" data-aos="fade-up">
						<h4 class="faq-title">
							<a class="collapsed" data-bs-toggle="collapse" href="#sFaq2" aria-expanded="false">Êtes-vous conforme au RGPD ?</a>
						</h4>
						<div id="sFaq2" class="card-collapse collapse" data-bs-parent="#accordionSecurity">
							<p>Oui, nous sommes entièrement conformes au RGPD. Consultez notre <a href="{{ route('privacy') }}" class="text-primary">Politique de Confidentialité</a> pour en savoir plus sur la collecte et le traitement de vos données.</p>
						</div>
					</div>
					<div class="faq-card aos" data-aos="fade-up">
						<h4 class="faq-title">
							<a class="collapsed" data-bs-toggle="collapse" href="#sFaq3" aria-expanded="false">Qui a accès à mes données ?</a>
						</h4>
						<div id="sFaq3" class="card-collapse collapse" data-bs-parent="#accordionSecurity">
							<p>Seuls vous et les membres de votre équipe que vous avez invités ont accès à vos données. Notre système de rôles et permissions vous permet de contrôler précisément qui voit quoi.</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- CTA -->
<section class="faq-section bg-white">
	<div class="container">
		<div class="connect-with-us">
			<div class="section-title text-center" data-aos="fade-up">
				<h2 class="mb-2">Vous avez d'autres questions ?</h2>
				<p class="mx-auto">Notre équipe est là pour vous aider. N'hésitez pas à nous contacter.</p>
				<div class="d-flex flex-wrap justify-content-center gap-3">
					<a href="{{ route('contact') }}" class="btn btn-primary btn-lg d-inline-flex align-items-center">Nous contacter<i class="isax isax-arrow-right-3 ms-2"></i></a>
					<a href="{{ route('help-center') }}" class="btn btn-dark btn-lg d-inline-flex align-items-center">Centre d'aide<i class="isax isax-arrow-right-3 ms-2"></i></a>
				</div>
			</div>
		</div>
	</div>
</section>

@endsection
