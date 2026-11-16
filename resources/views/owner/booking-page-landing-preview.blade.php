@extends('customer.layouts.booking-shell')

@section('title', 'Pratinjau landing page')

{{--
    Owner preview of the saved landing page (BQ-154): the storefront partial with the draft, above a
    notice that customers do not see it until it is published.
--}}

@section('content')
    <div class="mx-auto mb-6 w-full max-w-5xl rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
        Pratinjau. Pelanggan hanya melihat landing page setelah Anda menerbitkannya.
        <a href="{{ route('owner.booking-page', ['section' => 'landing']) }}" class="font-semibold underline">Kembali ke pengaturan</a>
    </div>

    @if ($landing)
        @include('customer.booking.partials.landing', ['landing' => $landing])
    @else
        <p class="mx-auto w-full max-w-5xl text-sm text-slate-600">Simpan landing page terlebih dahulu untuk melihat pratinjaunya.</p>
    @endif
@endsection
