@extends('errors.customer', [
    'code' => 403,
    'title' => 'Tidak punya akses',
    'owner' => true,
    'description' => 'Akun Anda tidak memiliki izin untuk membuka halaman atau menjalankan tindakan ini. Hubungi pemilik bisnis bila Anda memerlukannya.',
])

@section('title', 'Tidak punya akses')
