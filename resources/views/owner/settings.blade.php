@extends('layouts.owner-layout')

@section('title', 'Pengaturan')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <x-owner.page-header title="Pengaturan" purpose="Kelola profil bisnis, kebijakan booking, dan akun Anda." />
    <x-owner.tabs :tabs="$tabs" :active="$tab" :base-url="isset($credential) ? route('owner.settings.business') : null" />

    @foreach ($tabs as $key => $tabLabel)
        @continue(in_array($key, ['tim', 'integrasi'], true) && $tab !== $key)
        <section role="tabpanel" aria-label="{{ $tabLabel }}" @if ($tab !== $key) hidden @endif>
            @include('owner.settings.' . $key)
        </section>
    @endforeach
</div>
@endsection
