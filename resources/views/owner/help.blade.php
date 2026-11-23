@extends('layouts.owner-layout')

@section('title', 'Bantuan')

{{--
    Bantuan (BQ-145, owner-portal.md §8.17, FR-HELP-001): questions grouped by page, BookQu's contacts
    from configuration (AC-BNT-01, never written here) and a link to the new owner guide.
--}}

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <x-owner.page-header title="Bantuan" purpose="Jawaban cepat, dan cara menghubungi kami." />

    <x-ui.panel title="Hubungi BookQu" :description="$contacts['hours'] !== '' ? $contacts['hours'] : null">
        @if ($contacts['whatsappUrl'] || $contacts['email'])
            <div class="flex flex-wrap gap-2">
                @if ($contacts['whatsappUrl'])
                    <x-ui.button variant="primary" icon="whatsapp" :href="$contacts['whatsappUrl']" target="_blank" rel="noopener">
                        WhatsApp {{ $contacts['whatsapp'] }}
                    </x-ui.button>
                @endif
                @if ($contacts['email'])
                    <x-ui.button icon="external" :href="'mailto:'.$contacts['email']" class="max-w-full break-all">
                        {{ $contacts['email'] }}
                    </x-ui.button>
                @endif
            </div>
        @else
            <p class="text-sm text-bq-text-muted">Tim kami membantu lewat WhatsApp atau email. Kontak bantuan belum diatur.</p>
        @endif
    </x-ui.panel>

    <section aria-labelledby="faq-title" class="space-y-4">
        <h2 id="faq-title" class="sr-only">Pertanyaan umum</h2>
        @foreach ($faq as $page => $questions)
            <x-ui.panel :title="$page" flush>
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
            </x-ui.panel>
        @endforeach
    </section>
    <x-ui.panel aria-labelledby="help-guide" flush>
        <x-ui.list-row class="flex-wrap">
            <x-slot:leading><x-owner.icon name="check" class="size-5 text-bq-primary" /></x-slot:leading>
            <h2 id="help-guide" class="font-semibold">Panduan awal</h2>
            <p class="text-bq-small text-bq-text-muted">Langkah menyiapkan bisnis di BookQu.</p>
            <x-slot:trailing><x-ui.button :href="$guideUrl" size="sm">Buka panduan</x-ui.button></x-slot:trailing>
        </x-ui.list-row>
    </x-ui.panel>
</div>
@endsection
