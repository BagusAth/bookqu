@extends('layouts.owner-layout')

@section('title', 'Bantuan')

{{--
    Bantuan (BQ-145, owner-portal.md §8.17, FR-HELP-001): questions grouped by page, BookQu's contacts
    from configuration (AC-BNT-01, never written here) and a link to the new owner guide.
--}}

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <x-owner.page-header title="Bantuan" purpose="Jawaban cepat, dan cara menghubungi kami." />

    <section aria-labelledby="contact-title" class="bq-panel p-4 sm:p-5">
        <h2 id="contact-title" class="text-lg font-semibold text-bq-ink">Hubungi BookQu</h2>
        <p class="mt-1 text-sm text-bq-text-muted">Tim kami membantu lewat WhatsApp atau email.</p>
        @if ($contacts['whatsappUrl'] || $contacts['email'])
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($contacts['whatsappUrl'])
                    <a href="{{ $contacts['whatsappUrl'] }}" target="_blank" rel="noopener" class="bq-btn bq-btn-primary"><x-owner.icon name="whatsapp" class="h-4 w-4" />WhatsApp {{ $contacts['whatsapp'] }}</a>
                @endif
                @if ($contacts['email'])
                    <a href="mailto:{{ $contacts['email'] }}" class="bq-btn bq-btn-secondary">{{ $contacts['email'] }}</a>
                @endif
            </div>
            @if ($contacts['hours'] !== '')
                <p class="mt-2 text-xs text-bq-text-muted">{{ $contacts['hours'] }}</p>
            @endif
        @else
            <p class="mt-1 text-sm text-bq-text-muted">Kontak bantuan belum diatur.</p>
        @endif
    </section>

    <section aria-labelledby="faq-title" class="space-y-4">
        <h2 id="faq-title" class="text-base font-semibold text-bq-text">Pertanyaan umum</h2>
        @foreach ($faq as $page => $questions)
            <div class="bq-panel">
                <h3 class="border-b border-bq-border px-4 py-3 text-sm font-semibold text-bq-text">{{ $page }}</h3>
                <div class="divide-y divide-bq-border">
                    @foreach ($questions as $item)
                        <details class="group px-4 py-3">
                            <summary class="cursor-pointer list-none text-sm font-medium text-bq-text marker:hidden">
                                <span class="flex items-center justify-between gap-3">
                                    {{ $item['q'] }}
                                    <x-owner.icon name="chevron-down" class="h-4 w-4 shrink-0 text-bq-text-muted transition-transform group-open:rotate-180" />
                                </span>
                            </summary>
                            <p class="mt-2 text-sm text-bq-text-muted">{{ $item['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>
    <section class="bq-panel flex flex-wrap items-center justify-between gap-3 p-4" aria-labelledby="help-guide">
        <div>
            <h2 id="help-guide" class="font-semibold">Panduan awal</h2>
            <p class="text-bq-small text-bq-text-muted">Langkah menyiapkan bisnis di BookQu.</p>
        </div>
        <a href="{{ $guideUrl }}" class="bq-btn bq-btn-secondary">Buka panduan</a>
    </section>
</div>
@endsection
