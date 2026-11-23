{{--
    Early access page (BQ-175, FR-PUBLIC-009). Sections: hero, steps, benefits, sign-up form, FAQ, closing call.
    Every section is a <section> labelled by its heading; the page has one <h1>. The FAQ texts below also
    feed the FAQPage structured data.
--}}
@php
    $pageTitle = 'Early Access BookQu — Sistem Booking Online Gratis 1 Bulan';
    $pageDescription = 'Daftar early access BookQu: customer booking sendiri lewat satu link, jadwal tercatat otomatis, dan pengingat terkirim sebelum datang. Gratis 1 bulan, tanpa kartu kredit, setup dibantu.';
    $pageUrl = route('early-access');

    $faqs = [
        ['question' => 'Benar-benar gratis?', 'answer' => 'Iya, gratis 1 bulan penuh. Setelah itu kamu bebas memilih lanjut berlangganan atau berhenti.'],
        ['question' => 'Customer saya harus download aplikasi?', 'answer' => 'Nggak. Customer cukup buka link dari HP, pilih layanan dan jam, selesai.'],
        ['question' => 'Saya nggak terlalu ngerti teknologi. Bisa pakai?', 'answer' => 'Bisa. BookQu dibuat untuk owner yang sibuk, bukan untuk orang IT. Kami juga bantu setup dari awal.'],
        ['question' => 'Customer tetap bisa chat WhatsApp?', 'answer' => 'Tetap bisa. WhatsApp tetap untuk ngobrol, BookQu yang urus jadwalnya.'],
        ['question' => 'Bisa untuk bisnis selain barbershop?', 'answer' => 'Bisa, untuk salon, klinik kecantikan, studio foto, kursus privat, dan bisnis jasa lain yang pakai sistem janji temu.'],
    ];

    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebPage',
                '@id' => $pageUrl.'#webpage',
                'url' => $pageUrl,
                'name' => $pageTitle,
                'description' => $pageDescription,
                'inLanguage' => 'id-ID',
                'isPartOf' => ['@type' => 'WebSite', 'name' => 'BookQu', 'url' => url('/')],
            ],
            [
                '@type' => 'Organization',
                'name' => 'BookQu',
                'url' => url('/'),
                'logo' => asset('images/logo.png'),
                'email' => 'bookquind@gmail.com',
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn (array $faq): array => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
                ], $faqs),
            ],
        ],
    ];

    $whatsappUrl = $supportWhatsapp
        ? 'https://wa.me/'.preg_replace('/\D/', '', (string) $supportWhatsapp).'?text='.rawurlencode('Halo BookQu, saya mau tanya soal early access.')
        : null;
@endphp

<x-landing.layout :title="$pageTitle" :description="$pageDescription" :canonical="$pageUrl" :og-image="asset('images/booking.png')">
    <x-slot:head>
        {{-- Hide reveal targets before the first paint; without JavaScript everything stays visible --}}
        <script>document.documentElement.classList.add('js-reveal');</script>
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    <x-landing.navbar />

    <main class="font-owner text-bq-text">
        {{-- Hero --}}
        <section id="hero" aria-labelledby="hero-title" class="px-6 pt-28 pb-16 sm:pt-32 lg:pb-24">
            <div class="mx-auto max-w-3xl text-center">
                <p data-reveal data-reveal-delay="0" class="inline-flex items-center gap-2 rounded-full bg-bq-primary-light px-3 py-1 text-xs font-semibold text-bq-primary">
                    <x-owner.icon name="sparkle" class="size-3.5" />
                    Daftar sekarang, coba BookQu gratis 1 bulan
                </p>
                <h1 id="hero-title" data-reveal data-reveal-delay="120" class="mt-5 text-4xl font-bold leading-tight tracking-tight text-bq-ink sm:text-5xl">
                    Sistem booking yang kerjanya kayak punya Admin Sendiri
                </h1>
                <p data-reveal data-reveal-delay="240" class="mx-auto mt-5 max-w-xl text-base text-bq-text-muted sm:text-lg">
                    Customer bisa booking sendiri lewat satu link, jadwal tercatat otomatis, dan pengingat terkirim
                    sebelum mereka datang. Nggak perlu lagi bales "Kak, kosong?" satu-satu.
                </p>
                <div data-reveal data-reveal-delay="360" class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="#daftar" class="inline-flex items-center justify-center rounded-xl bg-bq-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-bq-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-bq-primary focus-visible:ring-offset-2">
                        Coba Gratis 1 Bulan
                    </a>
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl border border-bq-border bg-white px-6 py-3 text-sm font-semibold text-bq-ink transition hover:border-bq-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-bq-primary focus-visible:ring-offset-2">
                            <x-owner.icon name="whatsapp" class="size-4" />
                            Tanya via WhatsApp
                        </a>
                    @endif
                </div>
                <p data-reveal data-reveal-delay="460" class="mt-5 text-sm text-bq-text-muted">Gratis 1 bulan · Tanpa kartu kredit · Kami bantu setup</p>
            </div>

            <h2 class="sr-only">Kenapa BookQu</h2>
            <ul class="mx-auto mt-16 grid max-w-5xl gap-6 md:grid-cols-3 lg:mt-24 lg:gap-8">
                <x-early-access.feature-card eyebrow="Kami bikin gampang" tilt="left" data-reveal data-reveal-delay="0">
                    <x-slot:illustration>
                        <p class="text-xs text-bq-text-muted">Pilih jam</p>
                        <div class="mt-2 flex gap-2 text-xs font-semibold">
                            <span class="rounded-full bg-white px-3 py-1 text-bq-ink">18.00</span>
                            <span class="rounded-full bg-bq-primary px-3 py-1 text-white">19.00</span>
                            <span class="rounded-full bg-white px-3 py-1 text-bq-ink">20.00</span>
                        </div>
                        <div class="mt-3 rounded-full bg-bq-primary/70 py-1.5 text-center text-xs font-semibold text-white">Booking Sekarang</div>
                    </x-slot:illustration>
                    <strong>Bagikan link.</strong> Customer pilih jam. <strong>Booking langsung masuk.</strong>
                </x-early-access.feature-card>

                <x-early-access.feature-card eyebrow="Kami dengerin kamu" data-reveal data-reveal-delay="150">
                    <x-slot:illustration>
                        <div class="ml-auto w-4/5 rounded-xl bg-white p-2.5 text-xs shadow-sm">
                            <p class="text-bq-text-muted">Booking baru</p>
                            <p class="font-semibold text-bq-ink">Fade + Cuci · 19.00</p>
                        </div>
                        <div class="mt-2 w-4/5 rounded-xl bg-white p-2.5 text-xs shadow-sm">
                            <p class="text-bq-text-muted">Otomatis</p>
                            <p class="font-semibold text-bq-ink">Pengingat terkirim ke customer</p>
                        </div>
                    </x-slot:illustration>
                    Dibangun dari obrolan langsung dengan <strong>owner bisnis di Semarang</strong>
                </x-early-access.feature-card>

                <x-early-access.feature-card eyebrow="Kami kasih datanya" tilt="right" data-reveal data-reveal-delay="300">
                    <x-slot:illustration>
                        <p class="text-xs text-bq-text-muted">Jam paling ramai</p>
                        <div class="mt-2 flex h-16 items-end gap-1.5">
                            @foreach ([30, 45, 60, 90, 100, 70, 40] as $barHeight)
                                <span @class(['w-full rounded-t', 'bg-bq-accent' => $barHeight === 100, 'bg-bq-primary/40' => $barHeight !== 100]) style="height: {{ $barHeight }}%"></span>
                            @endforeach
                        </div>
                    </x-slot:illustration>
                    Biar kamu tahu <strong>pelanggan setia &amp; jam paling ramai</strong>
                </x-early-access.feature-card>
            </ul>
        </section>

        {{-- Steps --}}
        <section id="langkah" aria-labelledby="langkah-title" class="px-6 py-16 lg:py-24">
            <div class="mx-auto grid max-w-6xl items-center gap-10 lg:grid-cols-2">
                <x-early-access.section-heading id="langkah-title" align="left" data-reveal="fade-left" title="Mulai dalam tiga langkah"
                    lead="Gabung bersama BookQu dengan 3 langkah mudah, cepat, dan simpel." />
                <ol class="grid gap-5">
                    <x-early-access.step-card :number="1" title="Daftar early access" data-reveal="fade-right" data-reveal-delay="0">Isi form di bawah, cuma 1 menit.</x-early-access.step-card>
                    <x-early-access.step-card :number="2" title="Kami bantu setup" data-reveal="fade-right" data-reveal-delay="140">Layanan, jam buka, dan staff kamu kami masukkan bareng-bareng.</x-early-access.step-card>
                    <x-early-access.step-card :number="3" title="Bagikan link-nya" accent data-reveal="fade-right" data-reveal-delay="280">Pasang di bio Instagram atau status WhatsApp, booking langsung masuk.</x-early-access.step-card>
                </ol>
            </div>
        </section>

        {{-- Benefits --}}
        <section id="benefit" aria-labelledby="benefit-title" class="px-6 py-16 lg:py-24">
            <div class="mx-auto grid max-w-6xl items-center gap-10 lg:grid-cols-2">
                <x-early-access.section-heading id="benefit-title" align="left" data-reveal="fade-left" title="Yang kamu dapatkan sebagai Early Access" />
                <ul class="grid gap-4">
                    <x-early-access.benefit-card :number="1" data-reveal="fade-right" data-reveal-delay="0" title="Gratis 1 bulan penuh">Coba semua fitur tanpa bayar.</x-early-access.benefit-card>
                    <x-early-access.benefit-card :number="2" data-reveal="fade-right" data-reveal-delay="110" title="Setup dibantu langsung">Kami bantu bikin halaman booking kamu sampai siap pakai.</x-early-access.benefit-card>
                    <x-early-access.benefit-card :number="3" data-reveal="fade-right" data-reveal-delay="220" title="Suaramu didengar">Masukan kamu langsung jadi bahan perbaikan fitur.</x-early-access.benefit-card>
                    <x-early-access.benefit-card :number="4" data-reveal="fade-right" data-reveal-delay="330" title="Harga spesial setelah uji coba">Khusus untuk pendaftar early access.</x-early-access.benefit-card>
                </ul>
            </div>
        </section>

        {{-- Sign-up form --}}
        <section id="daftar" aria-labelledby="daftar-title" class="scroll-mt-24 px-6 py-16 lg:py-24">
            <x-early-access.section-heading id="daftar-title" data-reveal title="Jadi yang Pertama Coba BookQu"
                lead="Isi data di bawah. Tim BookQu akan menghubungi kamu lewat WhatsApp untuk mulai setup." />

            <div data-reveal="zoom" data-reveal-delay="120" class="mx-auto mt-10 max-w-2xl">
                    @if ($errors->any())
                        <div role="alert" class="mb-6 rounded-2xl border border-bq-danger/30 bg-bq-danger-bg px-5 py-4 text-sm text-bq-ink">
                            <p class="font-semibold">Ada {{ $errors->count() }} data yang perlu diperbaiki.</p>
                            <p class="mt-0.5 text-bq-text-muted">Periksa pesan di bawah tiap kolom, lalu kirim lagi.</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('early-access.store') }}" class="relative grid gap-5">
                        @csrf
                        <x-early-access.form-field name="full_name" label="Nama Lengkap" placeholder="Contoh: Bowo"
                            autocomplete="name" minlength="3" maxlength="100" />
                        <x-early-access.form-field name="business_name" label="Nama Bisnis" placeholder="Contoh: Bowo Barbershop"
                            autocomplete="organization" minlength="2" maxlength="150" />
                        <x-early-access.form-field name="business_type" label="Jenis Bisnis" placeholder="Contoh: Barbershop"
                            minlength="2" maxlength="100" />
                        <x-early-access.form-field name="email" type="email" label="Email" placeholder="nama@email.com"
                            autocomplete="email" maxlength="150" />
                        <x-early-access.form-field name="whatsapp_number" type="tel" label="Nomor WhatsApp" placeholder="0812 3456 7890"
                            autocomplete="tel" inputmode="tel" maxlength="20" hint="Nomor HP Indonesia yang aktif di WhatsApp." />

                        {{-- Honeypot: hidden from people and assistive technology, filled only by bots --}}
                        <div class="absolute -left-[9999px]" aria-hidden="true">
                            <label for="early-access-website">Website</label>
                            <input id="early-access-website" type="text" name="{{ \App\Http\Requests\EarlyAccess\StoreEarlyAccessSignupRequest::HONEYPOT }}" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="flex flex-col-reverse gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-bq-text-muted">
                                Data kamu hanya dipakai untuk menghubungi soal early access.
                                <a href="{{ route('privacy-policy') }}" class="font-medium text-bq-primary underline">Kebijakan Privasi</a>
                            </p>
                            <button type="submit" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-bq-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-bq-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-bq-primary focus-visible:ring-offset-2">
                                Daftar &amp; Klaim Gratis 1 Bulan
                            </button>
                        </div>
                    </form>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" aria-labelledby="faq-title" class="px-6 py-16 lg:py-24">
            <x-early-access.section-heading id="faq-title" title="Yang Sering Ditanyakan" data-reveal />
            <div class="mx-auto mt-10 grid max-w-2xl gap-4">
                @foreach ($faqs as $faq)
                    <x-early-access.faq-item :question="$faq['question']" data-reveal :data-reveal-delay="$loop->index * 90">{{ $faq['answer'] }}</x-early-access.faq-item>
                @endforeach
            </div>
        </section>

        {{-- Closing call --}}
        <section id="cta" aria-labelledby="cta-title" class="px-6 pb-20">
            <div data-reveal="zoom" class="mx-auto max-w-5xl rounded-3xl bg-bq-primary px-6 py-14 text-center text-white shadow-lg">
                <h2 id="cta-title" class="text-3xl font-bold tracking-tight sm:text-4xl">Udah saatnya Bookingmu Rapi</h2>
                <p class="mx-auto mt-3 max-w-xl text-white/80">Gabung bersama BookQu dengan 3 langkah mudah, cepat, dan simpel.</p>
                <a href="#daftar" class="mt-8 inline-flex items-center justify-center rounded-xl bg-bq-accent px-6 py-3 text-sm font-semibold text-bq-ink transition hover:brightness-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-bq-primary">
                    Daftar Early Access
                </a>
            </div>
        </section>
    </main>

    @if (session('early_access_registered'))
        {{-- Success popup, opened as a centered modal by resources/js/landing/early-access.js --}}
        <dialog data-early-access-dialog aria-labelledby="early-access-success-title" aria-describedby="early-access-success-message"
            class="ea-dialog m-auto w-[calc(100%-2rem)] max-w-md rounded-3xl border-0 bg-white p-0 font-owner text-bq-text shadow-2xl">
            <div class="flex flex-col items-center px-6 pt-8 pb-6 text-center sm:px-8">
                <span class="ea-dialog-icon flex size-16 items-center justify-center rounded-full bg-bq-success-bg text-bq-success">
                    <x-owner.icon name="check" class="h-8 w-8" stroke-width="2.25" />
                </span>
                <h2 id="early-access-success-title" class="mt-5 text-2xl font-bold tracking-tight text-bq-ink">Pendaftaran berhasil!</h2>
                <p id="early-access-success-message" class="mt-2 text-bq-text-muted">{{ session('early_access_registered') }}</p>
                <button type="button" data-dialog-close autofocus
                    class="mt-7 inline-flex w-full items-center justify-center rounded-xl bg-bq-primary px-6 py-3 text-sm font-semibold text-white transition hover:bg-bq-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-bq-primary focus-visible:ring-offset-2">
                    Oke, mengerti
                </button>
            </div>
        </dialog>
        <noscript>
            <p role="status" class="fixed inset-x-4 top-24 z-50 mx-auto max-w-md rounded-2xl bg-white p-5 text-center text-bq-ink shadow-2xl">{{ session('early_access_registered') }}</p>
        </noscript>
    @endif

    <x-early-access.footer />
</x-landing.layout>
