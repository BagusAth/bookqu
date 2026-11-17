@extends('layouts.owner-layout')

@section('title', 'Pengaturan')

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <x-owner.page-header title="Pengaturan" purpose="Setiap tab disimpan sendiri." />
    <x-owner.tabs :tabs="$tabs" :active="$tab" label="Bagian pengaturan" :base-url="isset($credential) ? route('owner.settings.business') : null" />

    @foreach ($tabs as $key => $tabLabel)
        @continue(in_array($key, ['tim', 'integrasi'], true) && $tab !== $key)
        <section role="tabpanel" aria-label="{{ $tabLabel }}" @if ($tab !== $key) hidden @endif>
            <x-ui.panel flush>
                @include('owner.settings.' . $key)
            </x-ui.panel>
        </section>
    @endforeach
</div>
@endsection
