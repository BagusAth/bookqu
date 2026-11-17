@extends('customer.layouts.booking-shell')

@section('title', 'Pratinjau halaman depan')

{{--
    Owner preview of the saved halaman depan (BQ-154): the storefront partial with the draft, above a
    notice that customers do not see it until it is published.
--}}

@section('content')
    <div class="mx-auto mb-6 w-full max-w-5xl rounded-xl border border-bq-warning/30 bg-bq-warning-bg p-4 text-sm text-bq-warning" role="status">
        Pratinjau. Pelanggan hanya melihat halaman depan setelah Anda menerbitkannya.
        <a href="{{ route('owner.booking-page', ['section' => 'landing']) }}" class="font-semibold underline">Kembali ke pengaturan</a>
    </div>

    @if ($landing)
        @include('customer.booking.partials.landing', ['landing' => $landing])
    @else
        <p class="mx-auto w-full max-w-5xl text-sm text-bq-text-muted">Simpan halaman depan terlebih dahulu untuk melihat pratinjaunya.</p>
    @endif
@endsection
