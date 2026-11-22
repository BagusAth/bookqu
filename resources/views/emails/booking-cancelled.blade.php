@extends('emails.layouts.customer')

@section('title', 'Reservasi dibatalkan')
@section('message')
    Reservasi Anda di {{ $businessName }} sudah dibatalkan. Hubungi bisnis jika Anda memerlukan bantuan.
@endsection

@section('extra')
    @if ($refundLabel)
        <p style="margin:12px 0;font-size:14px;line-height:21px;">
            Pengembalian dana <strong>{{ $refundLabel }}</strong> · {{ $refundStatus }}
        </p>
    @endif
@endsection
