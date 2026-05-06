document.addEventListener('DOMContentLoaded', () => {
	const navToggle = document.getElementById('nav-toggle');
	const mobileMenu = document.getElementById('mobile-menu');

	if (navToggle && mobileMenu) {
		navToggle.addEventListener('click', () => {
			const isHidden = mobileMenu.classList.contains('hidden');
			mobileMenu.classList.toggle('hidden');
			navToggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
		});

		mobileMenu.querySelectorAll('a').forEach((link) => {
			link.addEventListener('click', () => {
				mobileMenu.classList.add('hidden');
				navToggle.setAttribute('aria-expanded', 'false');
			});
		});
	}

	const modalOverlay = document.getElementById('login-modal');
	const modalCard = modalOverlay?.querySelector('.modal-card');
	const openButtons = document.querySelectorAll('[data-modal-open="login"]');
	const closeButtons = modalOverlay?.querySelectorAll('[data-modal-close]') ?? [];
	const passwordToggle = modalOverlay?.querySelector('[data-toggle-password]');
	const passwordInput = modalOverlay?.querySelector('#login-password');
	const emailInput = modalOverlay?.querySelector('#login-email');
	let lastActiveElement = null;

	const openModal = () => {
		if (!modalOverlay) {
			return;
		}
		lastActiveElement = document.activeElement;
		modalOverlay.classList.add('is-active');
		modalOverlay.setAttribute('aria-hidden', 'false');
		document.body.classList.add('modal-open');
		setTimeout(() => {
			emailInput?.focus();
		}, 50);
	};

	const closeModal = () => {
		if (!modalOverlay) {
			return;
		}
		modalOverlay.classList.remove('is-active');
		modalOverlay.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('modal-open');
		if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
			lastActiveElement.focus();
		}
	};

	openButtons.forEach((button) => {
		button.addEventListener('click', (event) => {
			event.preventDefault();
			openModal();
		});
	});

	closeButtons.forEach((button) => {
		button.addEventListener('click', closeModal);
	});

	modalOverlay?.addEventListener('click', (event) => {
		if (event.target === modalOverlay) {
			closeModal();
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && modalOverlay?.classList.contains('is-active')) {
			closeModal();
		}
	});

	passwordToggle?.addEventListener('click', () => {
		if (!passwordInput) {
			return;
		}
		const isHidden = passwordInput.type === 'password';
		passwordInput.type = isHidden ? 'text' : 'password';
		passwordToggle.classList.toggle('is-active', isHidden);
		passwordToggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
		passwordToggle.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
	});

	const revealItems = document.querySelectorAll('.reveal');
	if (!revealItems.length) {
		return;
	}

	const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (prefersReduced) {
		revealItems.forEach((item) => item.classList.add('is-visible'));
		return;
	}

	const observer = new IntersectionObserver(
		(entries, obs) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-visible');
					obs.unobserve(entry.target);
				}
			});
		},
		{ threshold: 0.2 }
	);

	revealItems.forEach((item) => observer.observe(item));
});
