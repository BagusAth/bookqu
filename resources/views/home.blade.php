<!doctype html>
<html lang="id">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>BookQu - Booking Platform</title>
		@vite('resources/css/app.css')
		<link rel="stylesheet" href="{{ asset('css/home.css') }}" />
	</head>
	<body class="bg-white text-dark antialiased">
		<div class="page-wrap">
			<header class="sticky top-0 z-50 border-b border-base bg-white/90 backdrop-blur">
				<nav class="mx-auto flex w-full max-w-[1280px] items-center justify-between px-6 py-4">
					<a href="#" class="text-lg font-extrabold text-primary">BookQu</a>
					<div class="hidden items-center gap-8 text-sm font-medium lg:flex">
						<a class="nav-link" href="#features">Features</a>
						<a class="nav-link" href="#solutions">Solutions</a>
						<a class="nav-link" href="#pricing">Pricing</a>
						<a class="nav-link" href="#about">About</a>
					</div>
					<div class="hidden items-center gap-4 lg:flex">
						<a
							class="text-sm font-semibold text-muted transition hover:text-primary"
							href="#"
							data-modal-open="login"
							aria-haspopup="dialog"
						>
							Login
						</a>
						<a class="btn-primary" href="#pricing">Get Started</a>
					</div>
					<button
						id="nav-toggle"
						class="inline-flex items-center justify-center rounded-xl border border-base p-2 text-dark transition hover:border-primary hover:text-primary lg:hidden"
						type="button"
						aria-controls="mobile-menu"
						aria-expanded="false"
						aria-label="Toggle navigation"
					>
						<svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
						</svg>
					</button>
				</nav>
				<div id="mobile-menu" class="hidden border-t border-base bg-white lg:hidden">
					<div class="mx-auto flex w-full max-w-[1280px] flex-col gap-4 px-6 py-4 text-sm font-medium">
						<a class="nav-link" href="#features">Features</a>
						<a class="nav-link" href="#solutions">Solutions</a>
						<a class="nav-link" href="#pricing">Pricing</a>
						<a class="nav-link" href="#about">About</a>
						<div class="flex items-center gap-3 pt-2">
							<a class="btn-outline w-full" href="#" data-modal-open="login" aria-haspopup="dialog">Login</a>
							<a class="btn-primary w-full" href="#pricing">Get Started</a>
						</div>
					</div>
				</div>
			</header>

			<main>
				<section class="hero section reveal" id="top">
					<div class="mx-auto flex w-full max-w-[1280px] flex-col items-center px-6 text-center">
						<span class="badge">BARU: INTEGRASI PEMBAYARAN OTOMATIS</span>
						<h1 class="hero-title mt-6">Kelola Booking Bisnis Anda Lebih Mudah</h1>
						<p class="hero-subtitle mt-4">
							Sistem reservasi all-in-one untuk salon, klinik, studio, dan jasa profesional lainnya.
							Tingkatkan efisiensi dan kurangi no-show pelanggan.
						</p>
						<div class="mt-8 flex w-full flex-col items-center justify-center gap-4 sm:flex-row">
							<a class="btn-primary" href="#pricing">Mulai Gratis 7 Hari</a>
							<a class="btn-outline" href="#demo">Lihat Demo</a>
						</div>
					</div>
					<div class="mx-auto mt-12 w-full max-w-[1100px] px-6" id="demo">
						<div class="mockup-shell">
							<div class="mockup-card">
								<div class="mockup-topbar">
									<div class="mockup-dots">
										<span></span>
										<span></span>
										<span></span>
									</div>
									<div class="mockup-title">BookQu Dashboard</div>
									<div class="mockup-tabs">
										<span>Overview</span>
										<span>Sales</span>
										<span>Bookings</span>
									</div>
								</div>
								<div class="mockup-body">
									<div class="mockup-left">
										<div class="mockup-filter">Quick Insight</div>
										<div class="mockup-chart">
											<svg viewBox="0 0 400 200" aria-hidden="true">
												<defs>
													<linearGradient id="line" x1="0" y1="0" x2="1" y2="1">
														<stop offset="0" stop-color="#4F46E5" />
														<stop offset="1" stop-color="#A5B4FC" />
													</linearGradient>
												</defs>
												<polyline
													fill="none"
													stroke="url(#line)"
													stroke-width="4"
													points="10,140 60,110 110,120 160,90 210,100 260,70 310,80 360,60"
												/>
												<polyline
													fill="none"
													stroke="#CBD5F5"
													stroke-width="3"
													points="10,160 60,150 110,155 160,140 210,145 260,130 310,135 360,120"
												/>
												<g fill="#93C5FD" opacity="0.7">
													<rect x="30" y="150" width="16" height="40" rx="4" />
													<rect x="80" y="130" width="16" height="60" rx="4" />
													<rect x="130" y="140" width="16" height="50" rx="4" />
													<rect x="180" y="110" width="16" height="80" rx="4" />
													<rect x="230" y="120" width="16" height="70" rx="4" />
													<rect x="280" y="100" width="16" height="90" rx="4" />
													<rect x="330" y="90" width="16" height="100" rx="4" />
												</g>
											</svg>
										</div>
									</div>
									<div class="mockup-right">
										<div class="mockup-card-sm">
											<span>Booking Masuk</span>
											<strong>128</strong>
										</div>
										<div class="mockup-card-sm">
											<span>Konfirmasi</span>
											<strong>98%</strong>
										</div>
										<div class="mockup-card-sm">
											<span>Top Service</span>
											<strong>Studio A</strong>
										</div>
										<div class="mockup-card-sm">
											<span>Revenue</span>
											<strong>Rp24.8jt</strong>
										</div>
										<div class="mockup-card-sm">
											<span>Occupancy</span>
											<strong>84%</strong>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</section>

				<section class="section reveal" id="features">
					<div class="mx-auto w-full max-w-[1280px] px-6">
						<div class="text-center">
							<h2 class="section-title">Fitur Utama Untuk Bisnis Anda</h2>
							<p class="section-subtitle mt-3">
								Dirancang untuk kecepatan dan kemudahan penggunaan, BookQu memberikan kontrol penuh atas
								operasional harian Anda.
							</p>
						</div>
						<div class="mt-12 grid gap-6 lg:grid-cols-2" id="solutions">
							<article class="feature-card feature-card--media">
								<div class="feature-header">
									<span class="icon-wrap">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 12h8M8 17h6" />
											<rect x="4" y="4" width="16" height="16" rx="2" />
										</svg>
									</span>
									<div>
										<h3>Booking Management</h3>
										<p>
											Terima pesanan 24/7 secara otomatis. Biarkan pelanggan memilih jadwal tanpa perlu
											chat manual satu per satu.
										</p>
									</div>
								</div>
								<div class="feature-media feature-media--booking">
									<div class="feature-screen"></div>
								</div>
							</article>

							<article class="feature-card">
								<div class="feature-header">
									<span class="icon-wrap">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
											<rect x="3" y="4" width="18" height="18" rx="2" />
											<path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18" />
											<path stroke-linecap="round" stroke-linejoin="round" d="M8 14h2M14 14h2M8 18h2" />
										</svg>
									</span>
									<div>
										<h3>Real-time Schedule</h3>
										<p>
											Sinkronisasi instan di semua perangkat. Hindari double-booking dengan kalender pintar
											kami.
										</p>
									</div>
								</div>
							</article>

							<article class="feature-card">
								<div class="feature-header">
									<span class="icon-wrap">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
											<rect x="2" y="5" width="20" height="14" rx="2" />
											<path stroke-linecap="round" stroke-linejoin="round" d="M2 10h20" />
											<path stroke-linecap="round" stroke-linejoin="round" d="M6 15h2" />
										</svg>
									</span>
									<div>
										<h3>Payment Integration</h3>
										<p>
											Terima pembayaran DP atau pelunasan langsung lewat sistem dengan berbagai metode
											pembayaran lokal.
										</p>
									</div>
								</div>
							</article>

							<article class="feature-card feature-card--media">
								<div class="feature-header">
									<span class="icon-wrap">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5" />
											<path stroke-linecap="round" stroke-linejoin="round" d="M8 19V9" />
											<path stroke-linecap="round" stroke-linejoin="round" d="M12 19V13" />
											<path stroke-linecap="round" stroke-linejoin="round" d="M16 19V7" />
											<path stroke-linecap="round" stroke-linejoin="round" d="M20 19V11" />
										</svg>
									</span>
									<div>
										<h3>Analytics Dashboard</h3>
										<p>
											Pantau performa bisnis, pendapatan harian, hingga slot paling produktif melalui data
											visual yang intuitif.
										</p>
									</div>
								</div>
								<div class="feature-media feature-media--analytics">
									<div class="feature-screen"></div>
								</div>
							</article>
						</div>
					</div>
				</section>

				<section class="section reveal" id="pricing">
					<div class="mx-auto w-full max-w-[1280px] px-6">
						<div class="text-center">
							<h2 class="section-title">Pilih Paket Sesuai Kebutuhan</h2>
							<p class="section-subtitle mt-3">
								Transparan tanpa biaya tersembunyi. Tingkatkan paket Anda seiring berkembangnya bisnis.
							</p>
						</div>
						<div class="mt-12 grid gap-6 lg:grid-cols-3">
							<article class="pricing-card">
								<div class="pricing-header">
									<p class="pricing-name">Small</p>
									<h3 class="pricing-price">Rp49K<span>/bulan</span></h3>
								</div>
								<ul class="pricing-list">
									<li>Halaman booking dengan URL</li>
									<li>Maksimal 2 layanan/program</li>
									<li>Maksimal 50 booking/bulan</li>
									<li>Manajemen jadwal &amp; slot</li>
									<li>Notifikasi email</li>
									<li>Dashboard sederhana</li>
									<li>Pembayaran via Midtrans</li>
								</ul>
								<a class="btn-outline mt-6 w-full" href="#">Pilih Paket</a>
							</article>

							<article class="pricing-card pricing-card--highlight">
								<div class="pricing-badge">POPULAR</div>
								<div class="pricing-header">
									<p class="pricing-name">Medium</p>
									<h3 class="pricing-price">Rp129K<span>/bulan</span></h3>
								</div>
								<ul class="pricing-list">
									<li>Semua fitur Small</li>
									<li>Maksimal 10 layanan/program</li>
									<li>Maksimal 300 booking/bulan</li>
									<li>Notifikasi email + WhatsApp</li>
									<li>Dashboard lengkap</li>
									<li>Sistem review &amp; rating</li>
									<li>Pengaturan harga fleksibel</li>
									<li>Prioritas support</li>
								</ul>
								<a class="btn-primary mt-6 w-full" href="#">Mulai Sekarang</a>
							</article>

							<article class="pricing-card">
								<div class="pricing-header">
									<p class="pricing-name">Pro</p>
									<h3 class="pricing-price">Rp299K<span>/bulan</span></h3>
								</div>
								<ul class="pricing-list">
									<li>Semua fitur Medium</li>
									<li>Unlimited layanan &amp; booking</li>
									<li>Custom landing page</li>
									<li>Custom domain</li>
									<li>Multi-admin</li>
									<li>Advanced analytics</li>
									<li>Export data</li>
									<li>Dedicated support</li>
								</ul>
								<a class="btn-outline mt-6 w-full" href="#">Hubungi Sales</a>
							</article>
						</div>
					</div>
				</section>

				<section class="cta section reveal" id="about">
					<div class="mx-auto flex w-full max-w-[1280px] flex-col items-center px-6 text-center">
						<h2 class="cta-title">Siap Mengembangkan Bisnis Anda?</h2>
						<p class="cta-subtitle mt-3">
							Bergabunglah dengan 2,000+ pemilik bisnis yang telah beralih ke BookQu dan hemat hingga 15 jam
							kerja setiap minggunya.
						</p>
						<a class="btn-primary mt-8" href="#pricing">Daftar Sekarang - Gratis 7 Hari</a>
					</div>
				</section>
			</main>

			<footer class="footer">
				<div class="mx-auto w-full max-w-[1280px] px-6">
					<div class="grid gap-10 py-16 md:grid-cols-2 lg:grid-cols-4">
						<div>
							<h3 class="footer-logo">BookQu</h3>
							<p class="footer-text mt-4">
								Platform manajemen booking terbaik di Indonesia untuk membantu digitalisasi bisnis jasa dan
								profesional.
							</p>
							<div class="mt-6 flex items-center gap-3">
								<a class="social-icon" href="#" aria-label="Instagram">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<rect x="3" y="3" width="18" height="18" rx="5" />
										<circle cx="12" cy="12" r="4" />
										<circle cx="17.5" cy="6.5" r="1" />
									</svg>
								</a>
								<a class="social-icon" href="#" aria-label="LinkedIn">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M7 10v8M7 7h.01" />
										<path stroke-linecap="round" stroke-linejoin="round" d="M11 10v8" />
										<path stroke-linecap="round" stroke-linejoin="round" d="M11 13c0-2 2-3 4-3s4 1 4 3v5" />
									</svg>
								</a>
								<a class="social-icon" href="#" aria-label="Facebook">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
									</svg>
								</a>
							</div>
						</div>
						<div>
							<h4 class="footer-title">Produk</h4>
							<ul class="footer-links">
								<li><a href="#features">Fitur Utama</a></li>
								<li><a href="#features">Integrasi Pembayaran</a></li>
								<li><a href="#">Mobile App</a></li>
								<li><a href="#">API Dokumentasi</a></li>
							</ul>
						</div>
						<div>
							<h4 class="footer-title">Solusi</h4>
							<ul class="footer-links">
								<li><a href="#solutions">Salon &amp; Spa</a></li>
								<li><a href="#solutions">Klinik Kesehatan</a></li>
								<li><a href="#solutions">Studio Foto</a></li>
								<li><a href="#solutions">Konsultan Jasa</a></li>
							</ul>
						</div>
						<div>
							<h4 class="footer-title">Kontak</h4>
							<ul class="footer-links">
								<li>support@bookqu.com</li>
								<li>+62 21 4567 8910</li>
								<li>Sudirman CBD, Jakarta Selatan</li>
							</ul>
						</div>
					</div>
					<div class="footer-bottom">
						<p>Copyright 2026 BookQu. Hak Cipta Dilindungi Undang-undang.</p>
						<div class="footer-meta">
							<a href="#">Kebijakan Privasi</a>
							<a href="#">Syarat &amp; Ketentuan</a>
						</div>
					</div>
				</div>
			</footer>

			<div id="login-modal" class="modal-overlay" aria-hidden="true">
				<div
					class="modal-card"
					role="dialog"
					aria-modal="true"
					aria-labelledby="login-modal-title"
					aria-describedby="login-modal-desc"
				>
					<button class="modal-close" type="button" data-modal-close aria-label="Tutup modal">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12" />
						</svg>
					</button>
					<div class="modal-header">
						<h2 id="login-modal-title">Masuk ke Akun Anda</h2>
						<p id="login-modal-desc">
							Kelola booking bisnis Anda dengan mudah melalui dashboard BookQu.
						</p>
					</div>
					<form class="modal-form" method="post" action="{{ url('/login') }}">
						@csrf
						<div class="modal-field-group">
							<label class="modal-label" for="login-email">Email</label>
							<div class="modal-field">
								<span class="modal-icon" aria-hidden="true">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path
												stroke-linecap="round"
												stroke-linejoin="round"
												d="M4 6h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"
										/>
										<path stroke-linecap="round" stroke-linejoin="round" d="m22 8-10 6L2 8" />
									</svg>
								</span>
								<input
									id="login-email"
									class="modal-input"
									type="email"
									name="email"
									placeholder="nama@email.com"
									autocomplete="email"
									required
								/>
							</div>
						</div>
						<div class="modal-field-group">
							<label class="modal-label" for="login-password">Password</label>
							<div class="modal-field">
								<span class="modal-icon" aria-hidden="true">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<rect x="4" y="10" width="16" height="10" rx="2" />
										<path stroke-linecap="round" stroke-linejoin="round" d="M8 10V7a4 4 0 0 1 8 0v3" />
									</svg>
								</span>
								<input
									id="login-password"
									class="modal-input"
									type="password"
									name="password"
									placeholder="Masukkan password"
									autocomplete="current-password"
									required
								/>
								<button
									class="modal-toggle"
									type="button"
									data-toggle-password
									aria-label="Tampilkan password"
									aria-pressed="false"
								>
									<svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"
										/>
										<circle cx="12" cy="12" r="3" />
									</svg>
									<svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											d="M10.6 10.6a2 2 0 0 0 2.8 2.8"
										/>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											d="M9.5 4.4A10.5 10.5 0 0 1 12 4c6 0 10 8 10 8a19.7 19.7 0 0 1-4.5 5.5"
										/>
										<path
											stroke-linecap="round"
											stroke-linejoin="round"
											d="M6.1 6.1C3.7 8.2 2 12 2 12s4 7 10 7a9.6 9.6 0 0 0 3.8-.8"
										/>
									</svg>
								</button>
							</div>
						</div>
						<div class="modal-row">
							<label class="modal-checkbox">
								<input type="checkbox" name="remember" />
								<span>Ingat saya</span>
							</label>
							<a class="modal-link" href="#">Lupa password?</a>
						</div>
						<button class="btn-primary modal-submit" type="submit">
							<span class="btn-label">Masuk</span>
							<span class="btn-spinner" aria-hidden="true"></span>
						</button>
						<div class="modal-divider"><span>atau</span></div>
						<a class="btn-outline w-full" href="#">Daftar Akun Baru</a>
					</form>
				</div>
			</div>
		</div>

		<script src="{{ asset('js/home.js') }}" defer></script>
	</body>
</html>
