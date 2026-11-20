@extends('errors.customer', [
    'title' => 'Halaman tidak tersedia.',
    'publicNotFound' => !($private ?? false),
    'description' => ($private ?? false) ? 'Buka kembali tautan dari email konfirmasi reservasi Anda.' : 'Periksa alamat halaman atau kembali ke halaman utama.',
])

@section('title', 'Halaman tidak tersedia.')
