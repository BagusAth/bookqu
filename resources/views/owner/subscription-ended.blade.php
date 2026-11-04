@extends('layouts.owner-layout')
@section('title', 'Paket berakhir')
@section('content')
<section role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-6">
    <h1 class="text-xl font-semibold">Paket berakhir</h1>
    <p class="mt-2">Perpanjang paket untuk menambahkan data baru. Booking yang sudah ada tetap dapat dikelola.</p>
    <p class="mt-2">Hubungi Pemilik bisnis untuk memperpanjang paket.</p>
    <a href="{{ route('owner.dashboard') }}" class="mt-4 inline-block font-semibold underline">Kembali ke Beranda</a>
</section>
@endsection
