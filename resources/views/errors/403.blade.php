{{--
    Permission denied page (BQ-129, owner-portal.md §5.2 and §9): shown for every route a role
    may not open. It never repeats the exception message, so it does not reveal whether a
    record exists.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tidak punya akses — BookQu</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-bq-background font-sans text-bq-text antialiased">
    <main class="mx-auto flex min-h-screen max-w-md flex-col items-center justify-center px-6 text-center">
        <p class="text-sm font-medium text-bq-text-muted">403</p>
        <h1 class="mt-2 text-xl font-semibold">Tidak punya akses</h1>
        <p class="mt-2 text-sm text-bq-text-muted">Akun Anda tidak memiliki izin untuk membuka halaman atau menjalankan tindakan ini. Hubungi pemilik bisnis bila Anda memerlukannya.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-2">
            @auth
                <a href="{{ url('/owner/dashboard') }}" class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-medium text-white hover:bg-bq-primary-hover">Ke Beranda</a>
            @else
                <a href="{{ url('/') }}" class="rounded-lg bg-bq-primary px-4 py-2 text-sm font-medium text-white hover:bg-bq-primary-hover">Ke halaman utama</a>
            @endauth
        </div>
    </main>
</body>
</html>
