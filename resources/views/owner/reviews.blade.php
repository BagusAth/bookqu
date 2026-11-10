@extends('layouts.owner-layout')

@section('title', 'Ulasan')

{{--
    Ulasan (BQ-143, owner-portal.md §8.11): average and distribution, tabs Belum dibalas, Semua and
    Disembunyikan, search, reply with suggested sentences, hide or show on the booking page.
    Data comes from App\Actions\Review\GetReviewsPage; this view only renders it.
--}}

@php
    $replyUrl = route('owner.reviews.reply', ['id' => '__ID__']);
    $tabLabels = collect(\App\Actions\Review\GetReviewsPage::TABS)
        ->map(fn (string $label, string $key): string => $label.' ('.$tabCounts[$key].')')
        ->all();
    $buttonSecondary = 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-bq-border bg-bq-surface px-3 py-1.5 text-sm font-medium text-bq-text hover:bg-bq-background';
    $buttonPrimary = 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-bq-primary px-4 py-2 text-sm font-medium text-white hover:bg-bq-primary-hover disabled:opacity-60';
    $star = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
@endphp

@section('content')
<div
    class="mx-auto max-w-7xl space-y-6"
    x-data="{
        review: { id: null, customer: '', reply: '', tone: 'positive' },
        suggestions: @js($suggestions),
        saving: false,
        openReply(data, trigger) {
            this.review = Object.assign({ id: null, customer: '', reply: '', tone: 'positive' }, data);
            this.saving = false;
            $dispatch('open-dialog', { id: 'review-reply-dialog', trigger });
        },
        replyAction() {
            return @js($replyUrl).replace('__ID__', encodeURIComponent(this.review.id));
        },
    }"
>
    <x-owner.page-header title="Ulasan" purpose="Apa kata pelanggan, dan apakah sudah Anda balas." />

    {{-- ── Average and distribution ── --}}
    <section aria-label="Ringkasan ulasan" class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-bq-border bg-bq-surface p-5">
            <p class="text-sm text-bq-text-muted">Rata-rata rating</p>
            <p class="mt-1 flex items-baseline gap-1">
                <span class="text-3xl font-semibold tabular-nums text-bq-text">{{ number_format($summary['average'], 1, ',', '.') }}</span>
                <span class="text-sm text-bq-text-muted">/ 5</span>
            </p>
            <div class="mt-1 flex items-center gap-0.5" aria-hidden="true">
                @for ($i = 1; $i <= 5; $i++)
                    <svg class="h-4 w-4 {{ $i <= round($summary['average']) ? 'text-bq-warning' : 'text-bq-border' }}" viewBox="0 0 20 20" fill="currentColor"><path d="{{ $star }}"/></svg>
                @endfor
            </div>
            <p class="mt-1 text-sm text-bq-text-muted">Dari {{ $summary['total'] }} ulasan</p>
        </div>

        <div class="rounded-xl border border-bq-border bg-bq-surface p-5 lg:col-span-2">
            <p class="text-sm text-bq-text-muted">Sebaran rating</p>
            <dl class="mt-3 space-y-2">
                @foreach ($summary['distribution'] as $stars => $count)
                    @php $share = $summary['total'] > 0 ? (int) round($count / $summary['total'] * 100) : 0; @endphp
                    <div class="flex items-center gap-3 text-sm">
                        <dt class="w-14 shrink-0 tabular-nums text-bq-text">{{ $stars }} bintang</dt>
                        <dd class="flex flex-1 items-center gap-3">
                            <span class="h-2 flex-1 overflow-hidden rounded-full bg-bq-background" aria-hidden="true">
                                <span class="block h-full rounded-full bg-bq-warning" style="width: {{ $share }}%"></span>
                            </span>
                            <span class="w-8 shrink-0 text-right tabular-nums text-bq-text-muted">{{ $count }}</span>
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <x-owner.tabs :tabs="$tabLabels" :active="$activeTab" label="Daftar ulasan" />

    <form method="GET" action="{{ route('owner.reviews') }}" role="search" class="flex gap-2">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <label for="review-search" class="sr-only">Cari ulasan</label>
        <input id="review-search" type="search" name="search" value="{{ $search }}" placeholder="Cari komentar, balasan, nama pelanggan atau kode booking" class="w-full rounded-lg border border-bq-border bg-bq-surface px-3 py-2 text-sm text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/30">
        <button type="submit" class="{{ $buttonSecondary }} py-2">Cari</button>
    </form>

    @if ($reviews->total() === 0)
        @if ($search !== '')
            <x-owner.empty-state title="Tidak ada ulasan yang cocok" description="Ubah kata kunci untuk melihat ulasan lain." action-label="Tampilkan semua" :action-href="route('owner.reviews', ['tab' => $activeTab])" />
        @elseif ($activeTab === 'belum' && $tabCounts['semua'] > 0)
            <x-owner.empty-state title="Semua ulasan sudah dibalas" description="Ulasan baru yang belum dibalas akan muncul di sini." />
        @elseif ($activeTab === 'disembunyikan' && $tabCounts['semua'] > 0)
            <x-owner.empty-state title="Tidak ada ulasan yang disembunyikan" description="Ulasan yang Anda sembunyikan tidak tampil di halaman booking dan muncul di sini." />
        @else
            <x-owner.empty-state title="Belum ada ulasan" description="Pelanggan memberi ulasan lewat link akses booking mereka setelah booking selesai." />
        @endif
    @else
        <ul class="space-y-3" aria-label="Ulasan">
            @foreach ($reviews as $review)
                <li class="rounded-xl border border-bq-border bg-bq-surface p-4 {{ $review['hidden'] ? 'opacity-75' : '' }}">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-medium text-bq-text">{{ $review['customer'] }}</p>
                                <span class="flex items-center gap-0.5" role="img" aria-label="{{ $review['rating'] }} dari 5 bintang">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="h-3.5 w-3.5 {{ $i <= $review['rating'] ? 'text-bq-warning' : 'text-bq-border' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="{{ $star }}"/></svg>
                                    @endfor
                                </span>
                                @if ($review['hidden'])
                                    <span class="inline-flex items-center rounded-full bg-bq-background px-2 py-0.5 text-xs font-medium text-bq-text-muted ring-1 ring-inset ring-bq-border">Disembunyikan</span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-xs text-bq-text-muted">
                                {{ $review['createdAt'] }}
                                @if ($review['service'] !== '') · {{ $review['service'] }} @endif
                                @if ($review['bookingCode'] !== '')
                                    · <a
                                        href="{{ $review['bookingUrl'] }}"
                                        @if ($review['panelUrl']) x-on:click.prevent="$dispatch('open-booking-panel', { url: @js($review['panelUrl']), trigger: $el })" @endif
                                        class="text-bq-primary hover:text-bq-primary-hover"
                                    >{{ $review['bookingCode'] }}</a>
                                @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <button type="button" class="{{ $buttonSecondary }}" x-on:click="openReply(@js(['id' => $review['id'], 'customer' => $review['customer'], 'reply' => $review['reply'], 'tone' => $review['tone']]), $el)">
                                {{ $review['reply'] === '' ? 'Balas' : 'Ubah balasan' }}
                            </button>
                            <form method="POST" action="{{ route('owner.reviews.toggle', ['id' => $review['id']]) }}">
                                @csrf
                                <input type="hidden" name="_method" value="PATCH">
                                <button type="submit" class="{{ $buttonSecondary }}">{{ $review['hidden'] ? 'Tampilkan' : 'Sembunyikan' }}</button>
                            </form>
                        </div>
                    </div>

                    @if ($review['comment'] !== '')
                        <p class="mt-3 whitespace-pre-line break-words text-sm text-bq-text">{{ $review['comment'] }}</p>
                    @else
                        <p class="mt-3 text-sm text-bq-text-muted">Tanpa komentar.</p>
                    @endif

                    @if ($review['reply'] !== '')
                        <div class="mt-3 rounded-lg bg-bq-background px-3 py-2">
                            <p class="text-xs text-bq-text-muted">Balasan Anda{{ $review['repliedAt'] ? ' · '.$review['repliedAt'] : '' }}</p>
                            <p class="mt-0.5 whitespace-pre-line break-words text-sm text-bq-text">{{ $review['reply'] }}</p>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>

        <div>{{ $reviews->links() }}</div>
    @endif

    {{-- Reply dialog (BQ-101 finding 3: shared accessible dialog, named route, one submit) --}}
    <x-owner.dialog id="review-reply-dialog" title="Balas ulasan">
        <form id="review-reply-form" method="POST" x-bind:action="replyAction()" x-on:submit="saving = true" class="space-y-3">
            @csrf
            <p class="text-sm text-bq-text-muted">Balasan tampil untuk <span class="font-medium text-bq-text" x-text="review.customer"></span> dan pengunjung halaman booking.</p>
            <div>
                <p class="mb-1.5 text-sm font-medium text-bq-text">Saran kalimat</p>
                <div class="flex flex-col gap-2">
                    <template x-for="(sentence, index) in suggestions[review.tone] || []" x-bind:key="index">
                        <button type="button" x-on:click="review.reply = sentence" class="rounded-lg border border-bq-border px-3 py-2 text-left text-sm text-bq-text hover:bg-bq-background" x-text="sentence"></button>
                    </template>
                </div>
            </div>
            <div>
                <label for="review-reply-text" class="block text-sm font-medium text-bq-text">Balasan</label>
                <textarea id="review-reply-text" name="balasan" rows="4" required maxlength="{{ \App\Actions\Review\ReplyToReview::MAX_LENGTH }}" x-model="review.reply" class="mt-1 block w-full rounded-lg border border-bq-border bg-bq-surface px-3 py-2 text-sm text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/30"></textarea>
                <p class="mt-1 text-right text-xs tabular-nums text-bq-text-muted" x-text="(review.reply || '').length + '/{{ \App\Actions\Review\ReplyToReview::MAX_LENGTH }}'"></p>
            </div>
        </form>
        <x-slot:footer>
            <button type="button" class="{{ $buttonSecondary }} py-2" x-on:click="$dispatch('close-dialog', { id: 'review-reply-dialog' })">Batal</button>
            <button type="submit" form="review-reply-form" class="{{ $buttonPrimary }}" x-bind:disabled="saving" x-text="saving ? 'Menyimpan…' : 'Simpan balasan'">Simpan balasan</button>
        </x-slot:footer>
    </x-owner.dialog>
</div>
@endsection
