@extends('layouts.owner-layout')
@section('title', 'Landing Page Builder')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-bq-text" id="page-title">Landing Page</h1>
            <p class="mt-1 text-sm text-bq-text-muted">Buat dan kelola landing page untuk bisnis Anda</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center rounded-full bg-violet-500/15 px-3 py-1 text-xs font-bold uppercase tracking-wide text-violet-400">
                <svg class="mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
                Pro Feature
            </span>
        </div>
    </div>

    {{-- Landing Page Preview Card --}}
    <div class="rounded-xl border border-bq-border bg-bq-surface p-6 shadow-sm">
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500/20 to-indigo-500/20">
                <svg class="h-10 w-10 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-bq-text">Landing Page Builder</h2>
            <p class="mt-2 max-w-md text-sm text-bq-text-muted">
                Buat landing page profesional untuk bisnis Anda. Sesuaikan tampilan, tambahkan layanan, dan bagikan ke pelanggan Anda.
            </p>

            <div class="mt-8 grid w-full max-w-2xl grid-cols-1 gap-4 sm:grid-cols-3">
                {{-- Feature 1 --}}
                <div class="rounded-lg border border-bq-border/50 bg-bq-background p-4 text-left">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/15">
                        <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-bq-text">Kustomisasi Desain</h3>
                    <p class="mt-1 text-xs text-bq-text-subtle">Atur warna, font, dan layout sesuai brand bisnis Anda</p>
                </div>

                {{-- Feature 2 --}}
                <div class="rounded-lg border border-bq-border/50 bg-bq-background p-4 text-left">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500/15">
                        <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-bq-text">Link Booking</h3>
                    <p class="mt-1 text-xs text-bq-text-subtle">Integrasikan langsung dengan sistem booking Anda</p>
                </div>

                {{-- Feature 3 --}}
                <div class="rounded-lg border border-bq-border/50 bg-bq-background p-4 text-left">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/15">
                        <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-bq-text">Statistik Visitor</h3>
                    <p class="mt-1 text-xs text-bq-text-subtle">Pantau jumlah pengunjung dan konversi halaman Anda</p>
                </div>
            </div>

            <p class="mt-8 text-xs text-bq-text-subtle">
                🚧 Fitur ini sedang dalam pengembangan. Segera hadir!
            </p>
        </div>
    </div>
</div>
@endsection
