@extends('layouts.owner-layout')

@section('title', 'Integrations')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">

    {{-- ── Header ── --}}
    @include('components.owner.page-header', [
        'judul' => 'Integrations & Third-Party Services',
        'subjudul' => 'Hubungkan BookQu dengan Payment Gateway, WhatsApp, Google Calendar, Email, dan Analytics.',
    ])

    {{-- ── Integration Cards Grid ── --}}
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($integrations as $item)
            <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-xs flex flex-col justify-between hover:border-bq-border-strong hover:shadow-md transition">
                <div>
                    {{-- Top Status Pill --}}
                    <div class="flex items-center justify-between">
                        @if($item['status'] === 'connected')
                            <span class="rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20">
                                Connected
                            </span>
                        @elseif($item['status'] === 'coming_soon')
                            <span class="rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wider bg-amber-50 text-amber-700 ring-1 ring-amber-600/20">
                                Coming Soon
                            </span>
                        @else
                            <span class="rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-500">
                                Disconnected
                            </span>
                        @endif
                        <span class="text-xs text-bq-text-muted font-medium">{{ $item['category'] }}</span>
                    </div>

                    {{-- Icon & Title --}}
                    <div class="mt-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 font-bold">
                            @if($item['id'] === 'midtrans')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            @elseif($item['id'] === 'email')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            @elseif($item['id'] === 'whatsapp')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            @elseif($item['id'] === 'gcal')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            @else
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            @endif
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-bq-text">{{ $item['name'] }}</h3>
                            <span class="text-[11px] text-bq-text-subtle font-mono">
                                {{ $item['status'] === 'connected' ? 'Sistem Aktif' : ($item['status'] === 'coming_soon' ? 'Fitur Tahap Lanjutan' : 'Belum Terhubung') }}
                            </span>
                        </div>
                    </div>

                    {{-- Description --}}
                    <p class="mt-3 text-xs text-bq-text-muted leading-relaxed">
                        {{ $item['description'] }}
                    </p>
                </div>

                {{-- Action Button --}}
                <div class="mt-6 pt-4 border-t border-bq-border flex items-center justify-between">
                    @if($item['action_url'])
                        <a href="{{ $item['action_url'] }}"
                           class="w-full text-center rounded-xl bg-bq-primary px-4 py-2 text-xs font-semibold text-white hover:bg-bq-primary-hover shadow-xs transition">
                            {{ $item['action_label'] }} &rarr;
                        </a>
                    @elseif($item['status'] === 'connected')
                        <span class="w-full text-center rounded-xl bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700">
                            ✓ {{ $item['action_label'] }}
                        </span>
                    @else
                        <button type="button" disabled
                                class="w-full text-center rounded-xl bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-400 cursor-not-allowed">
                            {{ $item['action_label'] }}
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
