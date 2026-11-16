{{--
    Upgrade prompt (BQ-129, owner-portal.md §3 rule 6 and §6): the one way a feature above the
    viewer's plan is shown. The page decides through EntitlementRules whether to render it; this
    component never decides access.
--}}
@props([
    'feature',
    'plan',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-bq-border bg-bq-surface p-5']) }} role="note">
    <p class="text-sm font-semibold text-bq-text">{{ $feature }} tersedia di paket {{ $plan }}</p>
    <p class="mt-1 text-sm text-bq-text-muted">
        {{ $description ?? 'Naikkan paket untuk memakai fitur ini. Data dan pengaturan Anda yang lain tidak berubah.' }}
    </p>
    <a href="{{ route('owner.subscription') }}" class="bq-btn bq-btn-primary mt-3">Lihat paket</a>
</div>
