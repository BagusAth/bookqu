@extends('customer.layouts.booking-shell')

@section('minimal', '1')

@section('head')
    <meta name="robots" content="noindex,nofollow">
@endsection

@section('content')
    <x-ui.panel class="mx-auto max-w-lg">
        <x-customer.page-header :title="$title" :description="$description" />
        <x-ui.button class="mt-5 min-h-11" variant="primary" :href="$recoveryUrl ?? url('/')">
            Kembali
        </x-ui.button>
    </x-ui.panel>
@endsection
