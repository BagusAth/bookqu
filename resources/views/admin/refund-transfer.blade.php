<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transfer refund BookQu</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-slate-50 p-6 text-slate-900">
<main class="mx-auto max-w-md space-y-3 rounded border bg-white p-4">
    <a href="{{ route('admin.refunds') }}" class="underline">Kembali ke refund</a>
    <h1 class="text-xl font-bold">Transfer refund #{{ $refund->id }}</h1>
    <p>{{ $refund->jumlah_formatted }}</p>
    <p>Bank {{ $account->bank_nama }}</p>
    <p>Nomor rekening {{ $account->nomor_rekening }}</p>
    <p>Atas nama {{ $account->nama_pemilik_rekening }}</p>
</main>
</body>
</html>
