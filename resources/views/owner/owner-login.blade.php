@extends('layouts.owner-auth')

@section('title', 'Owner Login')

@section('content')
<div class="w-full max-w-md">
    <div class="mb-6 text-center">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-bq-primary text-white shadow-md shadow-bq-primary/30">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-bq-text">Owner Login</h1>
        <p class="mt-1 text-sm text-bq-text-muted">Masuk untuk mengelola bisnis booking Anda.</p>
    </div>

    <div class="rounded-2xl border border-bq-border bg-bq-surface p-6 shadow-sm">
        @if (session('gagal'))
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {{ session('gagal') }}
            </div>
        @endif

        <form method="POST" action="{{ route('owner.login.submit') }}" class="space-y-4">
            @csrf
            <div>
                <label for="input-email" class="mb-1.5 block text-sm font-medium text-bq-text">Email</label>
                <input
                    type="email"
                    name="email"
                    id="input-email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    placeholder="owner@bisnis.com"
                    class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                >
                @error('email')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="input-password" class="mb-1.5 block text-sm font-medium text-bq-text">Password</label>
                <input
                    type="password"
                    name="password"
                    id="input-password"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan password"
                    class="w-full rounded-lg border border-bq-border bg-bq-surface px-4 py-2.5 text-sm text-bq-text placeholder-bq-text-subtle transition-all focus:border-bq-primary focus:outline-none focus:ring-2 focus:ring-bq-primary/20"
                >
                @error('password')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-bq-text-muted">
                    <input type="checkbox" name="ingat" class="h-4 w-4 rounded border-bq-border text-bq-primary focus:ring-bq-primary/30">
                    Ingat saya
                </label>
                <span class="text-xs text-bq-text-subtle">Lupa password? Hubungi admin.</span>
            </div>

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-bq-primary px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5"
            >
                Masuk
            </button>
        </form>
    </div>

    <p class="mt-4 text-center text-sm text-bq-text-muted">
        Belum punya akun owner?
        <a href="{{ route('owner.register') }}" class="font-semibold text-bq-primary hover:text-bq-primary-hover">Daftar di sini</a>
    </p>
</div>
@endsection
