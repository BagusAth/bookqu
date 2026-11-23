@extends('emails.layouts.customer')

@section('title', 'Reservasi terkonfirmasi')
@section('message')
    Reservasi Anda di {{ $businessName }} sudah terkonfirmasi. Simpan email ini untuk membuka detail reservasi kembali.
@endsection
