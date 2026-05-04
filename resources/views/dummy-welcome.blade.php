<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome - BookQu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f7fb;
            color: #1f2937;
            margin: 0;
            padding: 40px 16px;
        }
        .card {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        h1 {
            margin: 0 0 12px;
            font-size: 22px;
        }
        .url {
            margin: 12px 0 20px;
            font-size: 14px;
            color: #4b5563;
        }
        a.button {
            display: inline-block;
            padding: 10px 16px;
            background: #111827;
            color: #ffffff;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        .backlink {
            display: inline-block;
            margin-top: 16px;
            font-size: 14px;
            color: #111827;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Berhasil! Selamat datang di sistem booking: {{ $tenant->namabisnis }}</h1>
        <div class="url">URL bisnis: <strong>www.bookqu.com/{{ $tenant->slug }}</strong></div>
        <a class="button" href="{{ url('/' . $tenant->slug) }}">Buka Halaman</a>
        <div>
            <a class="backlink" href="/dummy-register">Kembali bikin URL lain</a>
        </div>
    </div>
</body>
</html>
