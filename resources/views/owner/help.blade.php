@extends('layouts.owner-layout')

@section('title', 'Bantuan')

{{--
    Bantuan (BQ-145, owner-portal.md §8.17, FR-HELP-001): questions grouped by page, BookQu's contacts
    from configuration (AC-BNT-01, never written here) and a link to the new owner guide.
--}}

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <x-owner.page-header title="Bantuan" purpose="Jawaban cepat untuk pertanyaan umum, dan cara menghubungi tim BookQu." />

    <section aria-labelledby="contact-title" class="rounded-xl border border-bq-border bg-bq-surface p-4">
        <h2 id="contact-title" class="text-base font-semibold text-bq-text">Hubungi BookQu</h2>
        @if ($contacts['whatsappUrl'] || $contacts['email'])
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($contacts['whatsappUrl'])
                    <a href="{{ $contacts['whatsappUrl'] }}" target="_blank" rel="noopener" class="inline-flex items-center rounded-lg bg-bq-primary px-4 py-2 text-sm font-medium text-white hover:bg-bq-primary-hover">WhatsApp {{ $contacts['whatsapp'] }}</a>
                @endif
                @if ($contacts['email'])
                    <a href="mailto:{{ $contacts['email'] }}" class="inline-flex items-center rounded-lg border border-bq-border bg-bq-surface px-4 py-2 text-sm font-medium text-bq-text hover:bg-bq-background">{{ $contacts['email'] }}</a>
                @endif
            </div>
            @if ($contacts['hours'] !== '')
                <p class="mt-2 text-xs text-bq-text-muted">{{ $contacts['hours'] }}</p>
            @endif
        @else
            <p class="mt-1 text-sm text-bq-text-muted">Kontak bantuan belum diatur.</p>
        @endif
        <p class="mt-3 text-sm text-bq-text-muted">Baru mulai? <a href="{{ $guideUrl }}" class="text-bq-primary hover:text-bq-primary-hover">Buka panduan awal di Beranda</a>.</p>
    </section>

    <section aria-labelledby="faq-title" class="space-y-4">
        <h2 id="faq-title" class="text-base font-semibold text-bq-text">Pertanyaan umum</h2>
        @foreach ($faq as $page => $questions)
            <div class="rounded-xl border border-bq-border bg-bq-surface">
                <h3 class="border-b border-bq-border px-4 py-3 text-sm font-semibold text-bq-text">{{ $page }}</h3>
                <div class="divide-y divide-bq-border">
                    @foreach ($questions as $item)
                        <details class="group px-4 py-3">
                            <summary class="cursor-pointer list-none text-sm font-medium text-bq-text marker:hidden">
                                <span class="flex items-center justify-between gap-3">
                                    {{ $item['q'] }}
                                    <span class="text-bq-text-muted transition group-open:rotate-45" aria-hidden="true">+</span>
                                </span>
                            </summary>
                            <p class="mt-2 text-sm text-bq-text-muted">{{ $item['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>
</div>
@endsection
