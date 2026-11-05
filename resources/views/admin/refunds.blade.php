<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Refund BookQu</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-slate-50 p-6 text-slate-900">
<main class="mx-auto max-w-4xl space-y-4">
    <a href="{{ route('admin.dashboard') }}" class="underline">Dasbor admin</a>
    <h1 class="text-2xl font-bold">Refund</h1>
    @if (session('sukses')) <p role="status">{{ session('sukses') }}</p> @endif
    @foreach ($errors->all() as $error) <p role="alert" class="text-red-700">{{ $error }}</p> @endforeach
    @forelse ($refunds as $refund)
        <article class="space-y-3 rounded border bg-white p-4">
            <h2 class="font-semibold">Refund #{{ $refund->id }} · {{ $refund->jumlah_formatted }}</h2>
            <p>Pembayaran #{{ $refund->payment_id }} · {{ match ($refund->status) { 'pending' => 'Menunggu transfer', 'processed' => 'Diproses', default => 'Gagal' } }}</p>
            @if ($refund->refund_reference) <p>Referensi transfer {{ $refund->refund_reference }}</p> @endif
            @if ($refund->status === 'failed' && $refund->catatan) <p>{{ $refund->catatan }}</p> @endif
            @if ($refund->refundRequest)
                <p>Rekening {{ $refund->refundRequest->maskedAccount() }}</p>
            @endif
            @if ($refund->status === 'pending')
                @if ($refund->refundRequest)
                    <form method="POST" action="{{ route('admin.refunds.transfer', $refund) }}">
                        @csrf
                        <button class="text-indigo-700 underline">Buka detail untuk transfer</button>
                    </form>
                @else
                    <p>Rekening belum tersedia. Hubungi pelanggan sebelum mentransfer.</p>
                @endif
                <form method="POST" action="{{ route('admin.refunds.update', $refund) }}" class="flex flex-wrap items-end gap-2">
                    @csrf @method('PATCH')
                    <label>Referensi transfer <input name="reference" required maxlength="100" class="block rounded border p-2"></label>
                    <button name="status" value="processed" class="rounded bg-indigo-700 px-3 py-2 text-white">Tandai diproses</button>
                </form>
                <form method="POST" action="{{ route('admin.refunds.update', $refund) }}" class="flex flex-wrap items-end gap-2">
                    @csrf @method('PATCH')
                    <label>Alasan gagal <input name="reason" required maxlength="500" class="block rounded border p-2"></label>
                    <button name="status" value="failed" class="text-red-700">Tandai gagal</button>
                </form>
            @elseif ($refund->status === 'failed')
                <form method="POST" action="{{ route('admin.refunds.update', $refund) }}">
                    @csrf @method('PATCH')
                    <button name="status" value="pending" class="text-indigo-700 underline">Siapkan percobaan ulang</button>
                </form>
            @endif
        </article>
    @empty
        <p>Belum ada refund.</p>
    @endforelse
    {{ $refunds->links() }}
</main>
</body>
</html>
