@extends('layouts.owner-layout')

@section('title', 'Customer Reviews')

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="{
    search: '{{ addslashes($search ?? '') }}',
    ratingFilter: '{{ $ratingFilter ?? 'all' }}',
    replyModalOpen: false,
    activeReview: { id: null, customer: '', reply: '' },
    openReply(rev) {
        this.activeReview = { ...rev };
        this.replyModalOpen = true;
    }
}">

    {{-- ── Header ── --}}
    @include('components.owner.page-header', [
        'judul' => 'Customer Reviews & Feedback',
        'subjudul' => 'Pantau kepuasan pelanggan, kelola testimoni bintang, dan tanggapi ulasan customer.',
    ])

    {{-- ── Rating Overview & Distribution ── --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Big Score Card --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-xs flex flex-col items-center justify-center text-center">
            <p class="text-xs font-semibold text-bq-text-muted uppercase tracking-wider">Average Rating</p>
            <div class="mt-2 flex items-baseline gap-1">
                <span class="text-5xl font-black text-bq-text">{{ $avgRating }}</span>
                <span class="text-base font-semibold text-bq-text-muted">/ 5.0</span>
            </div>
            <div class="mt-2 flex items-center gap-1 text-amber-400">
                @for ($i = 1; $i <= 5; $i++)
                    @if ($i <= round($avgRating))
                        <svg class="h-5 w-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @else
                        <svg class="h-5 w-5 text-slate-200 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endif
                @endfor
            </div>
            <p class="mt-2 text-xs text-bq-text-muted">Berdasarkan {{ $totalReviews }} ulasan terverifikasi</p>
        </div>

        {{-- Rating Distribution Breakdown --}}
        <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-xs lg:col-span-2 space-y-2.5">
            <h4 class="text-xs font-bold uppercase tracking-wider text-bq-text-muted">Distribusi Rating</h4>
            
            <div class="space-y-2 text-xs">
                @for ($stars = 5; $stars >= 1; $stars--)
                    @php
                        $count = $ratingCounts[$stars] ?? 0;
                        $pct = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
                    @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-12 text-bq-text font-semibold flex items-center gap-1">{{ $stars }} ★</span>
                        <div class="h-2 flex-1 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-amber-400" style="width: {{ $pct }}%;"></div>
                        </div>
                        <span class="w-8 text-right text-bq-text-muted font-mono">{{ $count }}</span>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    {{-- ── Filters & Search ── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('owner.reviews') }}" class="relative w-full sm:max-w-xs">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bq-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari ulasan atau nama customer..." class="w-full rounded-xl border border-bq-border bg-bq-surface py-2.5 pl-10 pr-4 text-xs text-bq-text placeholder-bq-text-subtle transition focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20">
        </form>
        <div class="flex items-center gap-2">
            @foreach(['all' => 'Semua Rating', '5' => '5 Bintang', '4' => '4 Bintang', '3' => '3 Bintang', '2' => '2 Bintang', '1' => '1 Bintang'] as $val => $label)
                <a href="{{ route('owner.reviews', ['rating' => $val, 'search' => $search]) }}"
                    class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $ratingFilter === $val ? 'bg-bq-primary text-white shadow-xs' : 'border border-bq-border bg-bq-surface text-bq-text-muted hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- ── Reviews Feed List ── --}}
    <div class="space-y-4">
        @forelse($reviews as $rev)
            @php
                $customerName = $rev->booking?->namapelanggan ?: 'Customer';
                $serviceName = $rev->booking?->layanan?->namalayanan ?: 'Layanan Reservasi';
                $revPayload = [
                    'id'       => $rev->id,
                    'customer' => $customerName,
                    'reply'    => $rev->balasan ?? '',
                ];
            @endphp
            <div class="rounded-2xl border border-bq-border bg-bq-surface p-5 shadow-xs transition hover:border-bq-border-strong space-y-3 {{ $rev->is_hidden ? 'opacity-60 bg-slate-50/70' : '' }}">
                
                {{-- Card Top Row --}}
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50 font-bold text-indigo-700 text-xs">
                            <span>{{ strtoupper(substr($customerName, 0, 1)) }}</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-sm text-bq-text">{{ $customerName }}</h4>
                                <span class="rounded bg-slate-100 text-slate-700 px-2 py-0.5 text-[10px] font-medium">{{ $serviceName }}</span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5 text-xs text-bq-text-muted">
                                <div class="flex items-center text-amber-400">
                                    @for ($i = 0; $i < $rev->rating; $i++)
                                        <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    @endfor
                                </div>
                                <span>•</span>
                                <span class="font-mono text-[11px]">{{ $rev->created_at ? $rev->created_at->format('d M Y') : '-' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 self-end sm:self-center">
                        <form method="POST" action="{{ route('owner.reviews.toggle', $rev->id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="inline-flex items-center gap-1 rounded-xl px-2.5 py-1 text-xs font-semibold border border-bq-border hover:bg-slate-50 transition {{ $rev->is_hidden ? 'text-amber-700 bg-amber-50' : 'text-bq-text-muted' }}">
                                <span>{{ $rev->is_hidden ? 'Sembunyi' : 'Tampil Publik' }}</span>
                            </button>
                        </form>
                        <button type="button" @click="openReply(@json($revPayload))" class="inline-flex items-center gap-1 rounded-xl bg-indigo-50 text-indigo-700 px-3 py-1 text-xs font-semibold hover:bg-indigo-100 transition" id="btn-reply-review-{{ $rev->id }}">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                            <span>{{ $rev->balasan ? 'Edit Balasan' : 'Balas Ulasan' }}</span>
                        </button>
                    </div>
                </div>

                {{-- Review Text --}}
                <p class="text-xs text-bq-text leading-relaxed pl-12">{{ $rev->review }}</p>

                {{-- Owner Reply Box --}}
                @if($rev->balasan)
                    <div class="ml-12 rounded-xl bg-slate-50 border border-bq-border p-3.5 text-xs space-y-1">
                        <div class="flex items-center gap-1.5 font-bold text-bq-primary text-[11px]">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                            <span>Balasan Anda:</span>
                        </div>
                        <p class="text-bq-text text-xs leading-relaxed">{{ $rev->balasan }}</p>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-bq-border bg-bq-surface p-12 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
                </div>
                <h3 class="text-sm font-bold text-bq-text">Tidak ada ulasan ditemukan</h3>
                <p class="mt-1 text-xs text-bq-text-muted max-w-sm mx-auto">Customer yang telah menyelesaikan reservasi dapat memberikan penilaian dan testimoni untuk bisnis Anda.</p>
            </div>
        @endforelse
    </div>

    {{-- Reply Modal --}}
    <div x-show="replyModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs" style="display: none;" x-cloak>
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-bq-border" @click.outside="replyModalOpen = false">
            <h3 class="text-base font-bold text-bq-text">Balas Ulasan Customer</h3>
            <p class="text-xs text-bq-text-muted mt-1" x-text="'Memberi tanggapan kepada ' + activeReview.customer"></p>
            <form method="POST" :action="`/owner/reviews/${activeReview.id}/reply`" class="mt-4 space-y-4" id="form-reply-review">
                @csrf
                <div>
                    <label class="text-xs font-semibold text-bq-text">Pesan Balasan</label>
                    <textarea name="balasan" x-model="activeReview.reply" required rows="4" placeholder="Tuliskan ucapan terima kasih atau tanggapan ramah Anda..." class="mt-1.5 w-full rounded-xl border border-bq-border p-3 text-xs text-bq-text focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-bq-border">
                    <button type="button" @click="replyModalOpen = false" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-bq-text-muted hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover transition">Kirim Balasan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
