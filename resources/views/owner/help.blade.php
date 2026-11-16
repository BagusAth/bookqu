@extends('layouts.owner-layout')

@section('title', 'Bantuan')

{{--
    Bantuan (BQ-145, owner-portal.md §8.17, FR-HELP-001): questions grouped by page, BookQu's contacts
    from configuration (AC-BNT-01, never written here) and a link to the new owner guide.
--}}

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <x-owner.page-header title="Bantuan" purpose="Jawaban cepat untuk pertanyaan umum, dan cara menghubungi tim BookQu." />

    <section aria-labelledby="contact-title" class="rounded-xl bg-bq-primary p-5 text-white sm:p-6">
        <h2 id="contact-title" class="text-lg font-bold text-white">Hubungi BookQu</h2>
        <p class="mt-1 text-sm text-white/80">Tim kami membantu lewat WhatsApp atau email.</p>
        @if ($contacts['whatsappUrl'] || $contacts['email'])
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($contacts['whatsappUrl'])
                    <a href="{{ $contacts['whatsappUrl'] }}" target="_blank" rel="noopener" class="bq-btn bq-btn-accent"><x-owner.icon name="whatsapp" class="h-4 w-4" />WhatsApp {{ $contacts['whatsapp'] }}</a>
                @endif
                @if ($contacts['email'])
                    <a href="mailto:{{ $contacts['email'] }}" class="bq-btn border border-white/40 text-white hover:bg-white/10">{{ $contacts['email'] }}</a>
                @endif
            </div>
            @if ($contacts['hours'] !== '')
                <p class="mt-2 text-xs text-white/80">{{ $contacts['hours'] }}</p>
            @endif
        @else
            <p class="mt-1 text-sm text-white/80">Kontak bantuan belum diatur.</p>
        @endif
        <p class="mt-4 text-sm text-white/80"><a href="{{ $guideUrl }}" class="font-semibold text-white underline underline-offset-2">Buka Beranda</a> untuk melihat langkah penyiapan atau jadwal hari ini.</p>
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
</div>
@endsection
