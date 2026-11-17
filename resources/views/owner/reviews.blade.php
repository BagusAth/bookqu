@extends('layouts.owner-layout')

@section('title', 'Ulasan')

{{--
    Ulasan (BQ-143, owner-portal.md §8.11): average and distribution, tabs Belum dibalas, Semua and
    Disembunyikan, search, reply with suggested sentences, hide or show on the booking page.
    Data comes from App\Actions\Review\GetReviewsPage; this view only renders it.
--}}

@php
    $replyUrl = route('owner.reviews.reply', ['id' => '__ID__']);
    $buttonSecondary = 'bq-btn bq-btn-secondary bq-btn-sm';
    $buttonPrimary = 'bq-btn bq-btn-primary';
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

    <x-ui.panel aria-label="Ringkasan ulasan">
        <x-ui.figures :value="number_format($summary['average'], 1, ',', '.')" label="rating rata-rata" :context="'dari '.$summary['total'].' ulasan'" class="!items-start md:!grid-cols-[minmax(0,1fr)_minmax(260px,35%)]">
            <dl aria-label="Sebaran rating" class="grid gap-2">
                @foreach ($summary['distribution'] as $stars => $count)
                    @php $share = $summary['total'] > 0 ? (int) round($count / $summary['total'] * 100) : 0; @endphp
                    <div class="flex items-center gap-3 text-bq-small">
                        <dt class="w-16 shrink-0 whitespace-nowrap">{{ $stars }} bintang</dt>
                        <dd class="flex flex-1 items-center gap-3">
                            <span class="h-2 flex-1 overflow-hidden rounded-full bg-bq-surface-2" aria-hidden="true">
                                <span class="block h-full rounded-full bg-bq-primary" style="width: {{ $share }}%"></span>
                            </span>
                            <span class="w-8 shrink-0 text-right bq-num text-bq-text-muted">{{ $count }}</span>
                        </dd>
                    </div>
                @endforeach
            </dl>
        </x-ui.figures>
    </x-ui.panel>

    <x-owner.tabs :tabs="\App\Actions\Review\GetReviewsPage::TABS" :counts="$tabCounts" :attention-tabs="['belum']" :active="$activeTab" label="Daftar ulasan" />

    <form method="GET" action="{{ route('owner.reviews') }}" role="search" class="bq-toolbar !flex-nowrap">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <x-ui.search id="review-search" name="search" :value="$search" label="Cari ulasan" />
        <x-ui.button type="submit" variant="secondary" size="sm">Cari</x-ui.button>
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
        <x-ui.panel flush>
            <ul class="divide-y divide-bq-border" aria-label="Ulasan">
                @foreach ($reviews as $review)
                    @php
                        $formId = 'review-inline-reply-'.$review['id'];
                        $replyFieldId = $formId.'-text';
                        $showReplyError = (string) old('reply_review') === (string) $review['id'] && $errors->has('balasan');
                    @endphp
                    <li class="grid gap-3 p-4 sm:px-5">
                        <div class="flex flex-wrap items-center gap-3">
                            <x-ui.avatar :name="$review['customer']" />
                            <div class="min-w-0 flex-1 basis-32">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="break-words font-semibold text-bq-text">{{ $review['customer'] }}</p>
                                    @if ($review['hidden'])
                                        <x-ui.badge tone="neutral">Disembunyikan</x-ui.badge>
                                    @endif
                                </div>
                                <p class="break-words text-bq-small text-bq-text-muted">
                                    @if ($review['service'] !== '') {{ $review['service'] }} · @endif{{ $review['createdAt'] }}
                                </p>
                            </div>
                            <div class="ml-auto flex shrink-0 items-center gap-1">
                                <span class="flex gap-0.5" aria-hidden="true">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="h-3.5 w-3.5 {{ $i <= $review['rating'] ? 'text-bq-warning' : 'text-bq-border' }}" viewBox="0 0 20 20" fill="currentColor"><path d="{{ $star }}"/></svg>
                                    @endfor
                                </span>
                                <span class="bq-num text-bq-small text-bq-text-muted">{{ $review['rating'] }}/5</span>
                            </div>
                        </div>

                        <p class="whitespace-pre-line break-words text-bq-body {{ $review['comment'] === '' ? 'text-bq-text-muted' : 'text-bq-text' }}">{{ $review['comment'] !== '' ? $review['comment'] : 'Tanpa komentar.' }}</p>

                        @if ($review['reply'] !== '')
                            <div class="rounded-r-lg border-l-2 border-bq-primary-border bg-bq-primary-light px-3 py-2">
                                <p class="text-bq-small font-semibold text-bq-text">Balasan Anda</p>
                                @if ($review['repliedAt'])
                                    <p class="text-bq-small text-bq-text-muted">{{ $review['repliedAt'] }}</p>
                                @endif
                                <p class="whitespace-pre-line break-words text-bq-body text-bq-text">{{ $review['reply'] }}</p>
                            </div>
                        @endif

                        @if ($review['reply'] === '' || $showReplyError)
                            <form id="{{ $formId }}" method="POST" action="{{ route('owner.reviews.reply', ['id' => $review['id']]) }}" class="grid gap-2">
                                @csrf
                                <input type="hidden" name="reply_review" value="{{ $review['id'] }}">
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($suggestions[$review['tone']] as $sentence)
                                        <button type="button" class="bq-btn bq-btn-secondary bq-btn-sm max-w-full whitespace-normal text-left"
                                            x-on:click="$refs['{{ $replyFieldId }}'].value = @js($sentence); $refs['{{ $replyFieldId }}'].focus()">{{ $sentence }}</button>
                                    @endforeach
                                </div>
                                <label for="{{ $replyFieldId }}" class="sr-only">Balasan untuk {{ $review['customer'] }}</label>
                                <x-ui.textarea :id="$replyFieldId" x-ref="{{ $replyFieldId }}" name="balasan" rows="3" required
                                    :maxlength="\App\Actions\Review\ReplyToReview::MAX_LENGTH"
                                    :aria-invalid="$showReplyError ? 'true' : 'false'"
                                    placeholder="Tulis balasan, paling banyak 1000 karakter">{{ $showReplyError ? old('balasan') : '' }}</x-ui.textarea>
                                @if ($showReplyError)
                                    <p class="text-bq-small text-bq-danger" role="alert">{{ $errors->first('balasan') }}</p>
                                @endif
                            </form>
                        @endif

                        <div class="flex flex-wrap items-center justify-end gap-2">
                            @if ($review['reply'] === '' || $showReplyError)
                                <x-ui.button type="submit" :form="$formId" variant="primary" size="sm">Kirim balasan</x-ui.button>
                            @else
                                <button type="button" class="bq-btn bq-btn-ghost bq-btn-sm" x-on:click="openReply(@js(['id' => $review['id'], 'customer' => $review['customer'], 'reply' => $review['reply'], 'tone' => $review['tone'], 'rating' => $review['rating'], 'comment' => $review['comment']]), $el)">Ubah balasan</button>
                            @endif
                            <form method="POST" action="{{ route('owner.reviews.toggle', ['id' => $review['id']]) }}">
                                @csrf
                                <input type="hidden" name="_method" value="PATCH">
                                <x-ui.button type="submit" variant="ghost" size="sm">{{ $review['hidden'] ? 'Tampilkan lagi' : 'Sembunyikan' }}</x-ui.button>
                            </form>
                            @if ($review['bookingCode'] !== '')
                                <a href="{{ $review['bookingUrl'] }}" title="{{ $review['bookingCode'] }}" class="bq-btn bq-btn-ghost bq-btn-sm"
                                    @if ($review['panelUrl']) x-on:click.prevent="$dispatch('open-booking-panel', { url: @js($review['panelUrl']), trigger: $el })" @endif
                                >Lihat booking</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <x-ui.pager :paginator="$reviews" noun="ulasan" />
        </x-ui.panel>
    @endif

    {{-- Reply dialog (BQ-101 finding 3: shared accessible dialog, named route, one submit) --}}
    <x-owner.dialog id="review-reply-dialog" title="Balas ulasan">
        <form id="review-reply-form" method="POST" x-bind:action="replyAction()" x-on:submit="saving = true" class="space-y-3">
            @csrf
            <input type="hidden" name="reply_review" x-bind:value="review.id">
            <p class="text-sm text-bq-text-muted">Balasan tampil untuk <span class="font-medium text-bq-text" x-text="review.customer"></span> dan pengunjung halaman booking.</p>
            <blockquote class="rounded-lg border border-bq-border bg-bq-background p-3">
                <p class="font-semibold" x-text="review.rating + ' dari 5 bintang'"></p>
                <p class="whitespace-pre-line break-words text-bq-text-muted" x-text="review.comment || 'Ulasan tanpa komentar.'"></p>
            </blockquote>
            <div>
                <p class="mb-1.5 text-sm font-medium text-bq-text">Saran kalimat</p>
                <div class="flex flex-col gap-2">
                    <template x-for="(sentence, index) in suggestions[review.tone] || []" x-bind:key="index">
                        <button type="button" x-on:click="review.reply = sentence" class="bq-btn bq-btn-secondary text-left" x-text="sentence"></button>
                    </template>
                </div>
            </div>
            <div>
                <label for="review-reply-text" class="block text-sm font-medium text-bq-text">Balasan</label>
                <textarea id="review-reply-text" name="balasan" rows="4" required maxlength="{{ \App\Actions\Review\ReplyToReview::MAX_LENGTH }}" x-model="review.reply" class="bq-input mt-1 block w-full"></textarea>
                <p class="mt-1 text-right text-xs bq-num text-bq-text-muted" x-text="(review.reply || '').length + '/{{ \App\Actions\Review\ReplyToReview::MAX_LENGTH }}'"></p>
            </div>
        </form>
        <x-slot:footer>
            <button type="button" class="{{ $buttonSecondary }} py-2" x-on:click="$dispatch('close-dialog', { id: 'review-reply-dialog' })">Batal</button>
            <button type="submit" form="review-reply-form" class="{{ $buttonPrimary }}" x-bind:disabled="saving" x-text="saving ? 'Menyimpan…' : 'Kirim balasan'">Kirim balasan</button>
        </x-slot:footer>
    </x-owner.dialog>
</div>
@endsection
