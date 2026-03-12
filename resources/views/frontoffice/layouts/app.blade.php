<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="@yield('meta_description', config('app.name') . ' — Logiciel de facturation et gestion commerciale en ligne')">
		<meta name="author" content="{{ config('app.name', 'Facturation') }}">
		<meta name="csrf-token" content="{{ csrf_token() }}">
		<title>@yield('title', 'Accueil') | {{ config('app.name', 'Facturation') }}</title>

		<!-- Favicon -->
		<link rel="shortcut icon" href="{{ url('build/img/favicon.png') }}">
		<link rel="apple-touch-icon" href="{{ url('build/img/apple-touch-icon.png') }}">

		<!-- Bootstrap CSS -->
		<link rel="stylesheet" href="{{ url('build/css/bootstrap.min.css') }}">

		<!-- Fontawesome CSS -->
		<link rel="stylesheet" href="{{ url('build/plugins/fontawesome/css/fontawesome.min.css') }}">
		<link rel="stylesheet" href="{{ url('build/plugins/fontawesome/css/all.min.css') }}">

		<!-- Aos CSS -->
		<link rel="stylesheet" href="{{ url('build/plugins/aos/aos.css') }}">

		<!-- Owl carousel CSS -->
		<link rel="stylesheet" href="{{ url('build/css/owl.carousel.min.css') }}">

		<!-- Feather CSS -->
		<link rel="stylesheet" href="{{ url('build/css/feather.css') }}">

		<!-- Iconsax CSS -->
		<link rel="stylesheet" href="{{ url('build/css/iconsax.css') }}">

		<!-- Landing CSS -->
		<link rel="stylesheet" href="{{ url('build/css/landing.css') }}">

		@stack('styles')
	</head>

<body>

	<div class="main-wrapper">

		@hasSection('hero')
		<div class="main-banner">
			@include('frontoffice.components.navbar')
			@yield('hero')
		</div>
		@else
			@include('frontoffice.components.navbar')
		@endif

		<div data-bs-spy="scroll" data-bs-target="#scroll-nav" class="scrollspy-example" tabindex="0">
			@yield('content')
			@include('frontoffice.components.footer')
		</div>

		<div class="mouse-cursor cursor-outer"></div>
		<div class="mouse-cursor cursor-inner"></div>

		<div class="back-to-top">
			<a class="back-to-top-icon align-items-center justify-content-center d-flex" href="#top"><i class="fa fa-chevron-up" aria-hidden="true"></i></a>
		</div>
	</div>

	<!-- jQuery -->
	<script src="{{ url('build/js/jquery-3.7.1.min.js') }}"></script>

	<!-- Bootstrap Core JS -->
	<script src="{{ url('build/js/bootstrap.bundle.min.js') }}"></script>
	<script src="{{ url('build/js/bootstrap-scrollspy.js') }}"></script>

	<!-- Feather JS -->
	<script src="{{ url('build/js/feather.min.js') }}"></script>

	<!-- Aos -->
	<script src="{{ url('build/plugins/aos/aos.js') }}"></script>

	<!-- counterup JS -->
	<script src="{{ url('build/js/jquery.waypoints.js') }}"></script>
	<script src="{{ url('build/js/jquery.counterup.min.js') }}"></script>

	<!-- Owl Carousel JS -->
	<script src="{{ url('build/js/owl.carousel.min.js') }}"></script>

	<!-- Custom JS -->
	<script src="{{ url('build/js/landing-script.js') }}"></script>

	@stack('scripts')
</body>
</html>
