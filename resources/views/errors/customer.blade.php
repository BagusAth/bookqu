@extends('customer.layouts.booking-shell')

@section('minimal', '1')

@section('head')
    <meta name="robots" content="noindex,nofollow">
@endsection

{{-- Error page (design A §7.9): icon, small code, title, explanation and two actions. Never shows internal details. --}}
@php
    $errorCode = (int) ($code ?? 0);
    $canRetry = in_array($errorCode, [419, 500, 503], true);
@endphp

@section('content')
    <x-customer.empty-state class="mx-auto mt-6 max-w-lg" :tone="$errorCode >= 500 ? 'danger' : 'warning'" :icon="$errorCode >= 500 ? 'alert' : 'search-off'"
        :code="$errorCode ? 'Kode '.$errorCode : null" :title="$title" :description="$description" heading-level="1">
        <x-slot:actions>
            <a href="{{ $recoveryUrl ?? url('/') }}" class="sf-btn">Kembali</a>
            @if ($canRetry)
                <button type="button" class="sf-btn-secondary" onclick="window.location.reload()">Muat ulang</button>
            @else
                <button type="button" class="sf-btn-secondary" onclick="history.length > 1 ? history.back() : window.location.assign(@js($recoveryUrl ?? url('/')))">Halaman sebelumnya</button>
            @endif
        </x-slot:actions>
    </x-customer.empty-state>
@endsection
