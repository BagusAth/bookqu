@extends('emails.layouts.customer')

@section('title', 'Konfirmasi reservasi dan bukti pembayaran')
@section('message')
    Pembayaran untuk reservasi Anda di {{ $businessName }} sudah diterima. Simpan email ini untuk melihat rincian reservasi.
@endsection
