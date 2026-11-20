@extends('emails.layouts.customer')

@section('title', 'Jadwal reservasi diubah')
@section('message')
    Jadwal Anda di {{ $businessName }} sudah diubah. Periksa tanggal dan jam baru di bawah.
@endsection

@section('previous')
    @if ($previousSession)
        <p style="margin:0 0 12px;font-size:14px;line-height:21px;">Jadwal sebelumnya<br>{{ $previousSession }}</p>
    @endif
@endsection
