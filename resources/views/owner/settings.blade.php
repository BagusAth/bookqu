@extends('layouts.owner-layout')

@section('title', 'Pengaturan')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <x-owner.page-header title="Pengaturan" purpose="Kelola profil bisnis, kebijakan booking, dan akun Anda." />
    <x-owner.tabs :tabs="$tabs" :active="$tab" />

    @foreach ($tabs as $key => $label)
        <section role="tabpanel" aria-label="{{ $label }}" @if ($tab !== $key) hidden @endif>
            @include('owner.settings.' . $key)
        </section>
    @endforeach
</div>
@endsection
