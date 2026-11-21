{{-- Brand header of the payment, status and invoice pages (design A §7.4, §7.5, §7.8): logo, business name, title, help. --}}
@php($headerTitle = $title ?? 'Selesaikan pembayaran')
<header class="sf-hero no-print">
    <x-customer.hero-ornament class="-right-[120px] -top-[150px] size-[300px]" />
    <div class="mx-auto flex max-w-[1120px] items-center gap-3 px-4 pt-3 md:px-8 md:pt-[18px] lg:px-14 lg:pt-5">
        @isset($backUrl)
            <a href="{{ $backUrl }}" aria-label="{{ $backLabel ?? 'Kembali' }}" class="sf-btn-on-primary sf-btn-icon">
                <x-customer.icon name="chevron-left" :stroke="2" />
            </a>
        @endisset
        <a href="{{ $storefrontUrl ?? '#' }}" class="shrink-0 rounded-sf-card-small" aria-label="Beranda {{ $tenant->namabisnis }}">
            @if ($businessLogo ?? null)
                <img src="{{ $businessLogo }}" alt="" class="size-10 rounded-sf-card-small bg-sf-surface object-cover">
            @else
                <span aria-hidden="true" class="grid size-10 place-items-center rounded-sf-card-small bg-sf-surface text-[15px] font-bold text-sf-primary-text">{{ $businessInitials ?? 'BQ' }}</span>
            @endif
        </a>
        <div class="min-w-0 grow">
            <p class="truncate text-xs font-semibold text-sf-hero-soft">{{ $tenant->namabisnis }}</p>
            <h1 id="page-title" class="text-lg font-bold md:text-2xl">{{ $headerTitle }}</h1>
        </div>
        {{ $headerActions ?? '' }}
        @if ($merchantWaUrl ?? null)
            <a href="{{ $merchantWaUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Bantuan via WhatsApp" class="sf-btn-on-primary sf-btn-icon">
                <x-customer.icon name="whatsapp" />
            </a>
        @endif
    </div>
    <div class="h-10 md:h-16"></div>
</header>
