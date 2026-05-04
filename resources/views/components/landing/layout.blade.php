<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BookQu - Kelola Booking Bisnis Anda</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes float-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        @keyframes fade-up {
            0% { opacity: 0; transform: translateY(16px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased overflow-x-clip" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    <div class="relative">
        <div class="pointer-events-none absolute -top-24 right-0 h-80 w-80 rounded-full bg-blue-200/40 blur-3xl"></div>
        <div class="pointer-events-none absolute top-64 -left-16 h-72 w-72 rounded-full bg-cyan-200/40 blur-3xl"></div>

        {{ $slot }}
    </div>
</body>
</html>
